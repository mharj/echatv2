<?php
	$current_users=array();
	session_start();
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	if (! isset( $_SESSION['user'] ) ) {
		echo "SESSION LOST";
		exit;
	}
	$channels=$_SESSION['my_channels'];
	$session_crc32='';
	if ( isset( $_SESSION['ucrc'] ) )
		$session_crc32=$_SESSION['ucrc'];
	$u=$_SESSION['user'];
	
	// online variables // delete this when all users latest version
	if ( ! isset($u['status']) )	$u['status']='';
	if ( ! isset($u['led']) )	$u['led']='green';

	$my_channels=array();
	$force_update=false;
	foreach ( $channels AS $k => $v )
		$my_channels[]=strtolower($v);
	if ( isset($_REQUEST['init']) && $_REQUEST['init']=='true')
	{
		unset($_SESSION);
		$_SESSION=array();
	}

	/**
	 * Get server status
	 */ 
	function ServerStatus() {
		$url='http://api.eve-online.com/Server/ServerStatus.xml.aspx';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$output = curl_exec($ch);
		if ( preg_match("/<serverOpen>(.*?)<\/serverOpen>/",$output,$match) )
		{
			if ( $match[1] == "True" )
				return("true");
			return("false");
		}
		return("false");
	}

	function read_channel($ch) {
		global $memcache;
		global $json;
		global $my_channels;
		$id=$memcache->get($ch.'_counter');
		if ( empty($id) ) {
			$id=0;
			$memcache->set($ch.'_counter',0,0,86400);
		}
		// in init we calculate "low" from memcache as we don't have correct val in session yet
		if ( isset($_REQUEST['init']) && $_REQUEST['init']=='true') {
			$low=$id-100;
			if ( $low < 0 )
				$low=0;
		} else 
			$low=$_SESSION[$ch.'_counter'];
		
		for ($i=$low;$i<$id;$i++) {
			$data=$memcache->get($ch.'_'.$i);
			$data['ch']=$ch;
			if ($_REQUEST['init']=='false') { // update userinfo when user sent msg
				$rdata=$memcache->get('ec_charID_'.$data['cid']);
				if ( ! empty($rdata) ) {
					foreach ( $rdata['ch'] AS $k=>$v ) {			// drop channels which I'm not currently
						if (! in_array($v,$my_channels) )
							unset($rdata['ch'][$k]);
					}
					sort($rdata['ch']);
					$json['usr']['m'][]=$rdata;
				}
			}
			$json['msg'][]=$data;
		}
		$_SESSION[$ch.'_counter']=$id;
		$memcache->set($ch.'_counter',$id,0,86400);
	}

	/*
	 *	self add to userlist and delete old ones
	 */
	function self_check() {
		global $memcache;
		global $current_users;
		global $u;
		global $my_channels;
		$users=$memcache->get('ec_users');
		if ( ! empty($users) ) 
			$current_users=$users;
		
		if ( ! in_array($u['charID'],$current_users) )
		{
			$current_users[]=$u['charID'];
			sort($current_users);
			$memcache->set('ec_users',$current_users,0,86400);
		}
		// auto afk hook
/*		if ( isset($_REQUEST['i']) && $_REQUEST['i'] == 'true' && $u['led'] != 'yellow' ) {
			$u['status']="is on autopilot since ".date('H:i:s');
			$u['led']='yellow';
			$_SESSION['user']['led']=$u['led'];
			$_SESSION['user']['status']=$u['status'];
		}*/

		$public=array(
			'charID'=>$u['charID'],
			'username'=>$u['username'],
			'corp_name'=>$u['corp_name'],
			'ally_name'=>$u['ally_name'],
			'b'=>$u['b'],
			'ch'=>$my_channels,
			'status'=>$u['status'],
			'led'=>$u['led'],
		);
		$memcache->set('ec_charID_'.$u['charID'],$public,0,60);
		// drop old members
		$mod=false;
		foreach ( $users AS $k=>$lu ) {
			if ( ! empty($lu) && is_numeric($lu) ) {
				$test=$memcache->get('ec_charID_'.$lu);
				if ( empty($test) ) {
					$mod=true;
					unset($users[$k]);
				}
			} else {
				unset($users[$k]);
				$mod=true;
			}
		}
		if ( $mod ) {
			sort($users);
			$current_users=$users;
			$memcache->set('ec_users',$current_users,0,86400);
		}
	}
	
	function check_users() {
		global $memcache;
		global $current_users;
		global $json;
		global $my_channels;
		global $force_update;
		global $u;
		if ( ! isset($_SESSION['ec_users']) ) {
			$_SESSION['ec_users']=array();
		}
			
		// crc32 
		$user_data=array();
		foreach ( $current_users AS $user ) {
			$data=$memcache->get('ec_charID_'.$user);
			if ( ! empty($data) ) {
				foreach ( $data['ch'] AS $k=>$v ) {			// drop channels which I'm not currently
					if (! in_array($v,$my_channels) )
						unset($data['ch'][$k]);
				}
				sort($data['ch']);			
				$user_data[]=$data;
				$session_data[$user]=$data;
			}
		}
		sort($user_data);
		$current_crc32=crc32(json_encode($user_data));
		$_SESSION['ucrc']=$current_crc32;
		// js new crc32 not match
		if ( $current_crc32 != $_REQUEST['ucrc'] ) {
			if ( $force_update ) {
				$_SESSION['ec_users']=array();
				$json['usr']['clear']='true';
			}
			$add=array_diff($current_users,$_SESSION['ec_users']);
			$del=array_diff($_SESSION['ec_users'],$current_users);
			$update_test=false;
			foreach ( $add AS $user ) {
				$data=$memcache->get('ec_charID_'.$user);
				if ( ! empty($data) ) {
					foreach ( $data['ch'] AS $k=>$v ) {			// drop channels which I'm not currently
						if (! in_array($v,$my_channels) )
							unset($data['ch'][$k]);
					}
					sort($data['ch']);
					$json['usr']['a'][]=$data;
				}
			}
			if ( count($del) > 0 ) {
				sort($del);
				$json['usr']['d']=$del;
			}

			if ( isset($json['usr']) )
				$json['usr']['ts']=date('H:i:s');
			else {
				// some sub elements have changed!
				// create this!!!
				foreach ($current_users AS $user ) {
					$data=$memcache->get('ec_charID_'.$user);
					if ( ! empty($data) ) {
						foreach ( $data['ch'] AS $k=>$v ) {			// drop channels which I'm not currently
							if (! in_array($v,$my_channels) )
								unset($data['ch'][$k]);
						}
						sort($data['ch']);
						if (! isset($_SESSION['user_data'][$user]) ||  json_encode($data) != json_encode($_SESSION['user_data'][$user]) ) 
							$json['usr']['m'][]=$data;
					}
				}				
			}
			if ( isset($json['usr']))
				$json['usr']['self']=$u;
				
			$_SESSION['user_data']=$session_data;
		}	
	}

	// settings and connections
	date_default_timezone_set('UTC');
	$memcache = new Memcache;
	$memcache->pconnect('localhost', 11211) or die ("Could not connect");

	// Server Status
	$ss=$memcache->get('eve_server_status');
	if ( empty($ss) )
	{
		$memcache->set('eve_server_status','load',0,180); 
		$ss=ServerStatus();
		$memcache->set('eve_server_status',$ss,0,180);	// 3 min cache
	}
	if (! isset($_SESSION['eve_server_status']) ||  $_SESSION['eve_server_status'] != $ss )
	{
		$json['ss']=$ss;
		$_SESSION['eve_server_status']=$ss;
	}
	
	// full update?
	if ( $session_crc32 != $_REQUEST['ucrc'] ) {
		$json['debug']=$session_crc32." vs ". $_REQUEST['ucrc'] ;
		$force_update=true;	
	}
	
	// self check
	self_check();
	// push channel list in Init
	if ( isset($_REQUEST['init']) && $_REQUEST['init']=='true') {
		$json['chl']=$my_channels;
	}
	// check users
	check_users();

	// add my charID to listen channel
	if ( isset($_SESSION['user']['charID']) ) {
		$channels['priv']=$_SESSION['user']['charID'];
	}
	// read channels
	foreach ( $channels AS $k=>$ch )
		read_channel(strtolower($ch));
	// save userlist to session
	$_SESSION['ec_users']=$current_users;	

	// output
	if ( isset($json) ) 
		echo json_encode($json);
	
?>
