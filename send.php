<?php
	session_start();
	if (! isset( $_SESSION['user'] ) )
		exit;
	$u=$_SESSION['user'];
	$my_channels=array();
	foreach ( $_SESSION['my_channels'] AS $k => $v )
		$my_channels[]=strtolower($v);	
	date_default_timezone_set('UTC');
	$memcache = new Memcache;
	$memcache->pconnect('localhost', 11211) or die ("Could not connect");

	if ( ! isset($_REQUEST['msg']) || ! isset($_REQUEST['chan'])  )
		exit;
	if ( in_array($_REQUEST['chan'],$my_channels) )
	{
		// slash commands
		$backend=$memcache->get('ec_charID_'.$u['charID']);
		$update=false;
		if ( $backend['led'] != 'green' ) { 
			$backend['status']='';
			$backend['led']='green';
			$update=true;
		}		
		// afk
		if ( preg_match("/^\/afk(.*?)$/",$_REQUEST['msg'],$match) ) {
			$backend['status']=htmlspecialchars(trim($match[1]));
			$backend['led']='yellow';
			$update=true;
		}

		if ( preg_match("/^\/sleep.*?$/",$_REQUEST['msg']) ) {
			$backend['status']='ZZZzzz...';
			$backend['led']='blue';
			$update=true;
		}

		// update backend
		if ( $update ) {
			$memcache->set('ec_charID_'.$u['charID'],$backend,0,60);
		}
		
		// priv msg (send msg to self and taget "channels")
		if ( preg_match("/^\/msg\ /",$_REQUEST['msg']) ) {
			$id=$memcache->increment($u['charID'].'_counter');
			if ( empty($id) )
			{
				$memcache->set($u['charID'].'_counter',0,0,86400);
				$id=$memcache->increment($u['charID'].'_counter');
			}
			$setup=array('f'=>$u['username'],'c'=>$u['corp_name'],'a'=>$u['ally_name'],'cid'=>$u['charID'],'m'=>htmlspecialchars($_REQUEST['msg']),'t'=>'m','d'=>date('H:i:s'));			
			$memcache->set($u['charID'].'_'.($id-1),$setup,MEMCACHE_COMPRESSED,86400);
		} else {
		// public msg
			$id=$memcache->increment($_REQUEST['chan'].'_counter');
			if ( empty($id) )
			{
				$memcache->set($_REQUEST['chan'].'_counter',0,0,86400);
				$id=$memcache->increment($_REQUEST['chan'].'_counter');
			}
			$setup=array('f'=>$u['username'],'c'=>$u['corp_name'],'a'=>$u['ally_name'],'cid'=>$u['charID'],'m'=>htmlspecialchars($_REQUEST['msg']),'t'=>'m','d'=>date('H:i:s'));
			$memcache->set($_REQUEST['chan'].'_'.($id-1),$setup,MEMCACHE_COMPRESSED,86400);
		}
	}
?>
