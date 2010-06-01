<?php
	require('db_settings.php');
	if ( isset($_REQUEST['apiuser']) && ! empty($_REQUEST['apiuser'])  && isset($_REQUEST['apikey']) && ! empty($_REQUEST['apikey']) ) {
		$api_id=trim($_REQUEST['apiuser']);
		$api_key=trim($_REQUEST['apikey']);
		
		// build corp_id to ally shortname list
		$alliance_list=file('AllianceList.xml.aspx');
		foreach ($alliance_list AS $line ) {
			if ( preg_match("/allianceID=/",$line) ) {
				$allyShort='';
				if ( preg_match ("/shortName=\"(.*?)\"/",$line,$match) )
					$allyShort=$match[1];
			}
			if ( preg_match("/<row\ corporationID=\"(.*?)\"/",$line,$match) )
				$corpId_in_ally[$match[1]]=$allyShort;
		}
		
		// load char list
		$url='http://api.eve-online.com/account/Characters.xml.aspx?userID='.$api_id.'&apiKey='.$api_key.'';
		$ch = curl_init($url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$output = curl_exec($ch);
		if ( preg_match_all("/<row\ name=\"(.*?)\"\ characterID=\"(.*?)\"/",$output,$match ) )
		{
			foreach ( $match[2] AS $k=>$v )
				$chars[$v]=array('name'=>$match[1][$k]);
		}		
		foreach ( $chars AS $k => $v )
		{
			$url='http://api.eve-online.com/char/CharacterSheet.xml.aspx?userID='.$api_id.'&apiKey='.$api_key.'&characterID='.$k;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			$corpID=0;
			$corpName='';

			if ( preg_match ("/<corporationID>(.*)<\/corporationID>/",$output,$match) )
                                $corpID=$match[1];
			if ( isset($corpId_in_ally[$corpID]) )
				$chars[$k]['allyShort']=$corpId_in_ally[$corpID];
			else
				$chars[$k]['allyShort']='';
			// get corp shortname
			$url='http://api.eve-online.com/corp/CorporationSheet.xml.aspx?userID='.$api_id.'&apiKey='.$api_id.'&corporationID='.$corpID;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			$chars[$k]['corpID']=$corpID;
			$chars[$k]['charID']=$k;
			$chars[$k]['corpShort']='';
			if ( preg_match ("/<ticker>(.*)<\/ticker>/",$output,$match) )
				$chars[$k]['corpShort']=$match[1];
			if ( preg_match ("/<allianceID>(.*)<\/allianceID>/",$output,$match) )
				$chars[$k]['allyID']=$match[1];					
		}
		$out=array();
		foreach ( $chars AS $k=>$v)
			$out[]=array('charID'=>$k,'name'=>$v['name']);
		if ( isset( $_REQUEST['charID'] ) && ! empty($_REQUEST['charID']) && isset( $_REQUEST['password'] ) && ! empty($_REQUEST['password']) )
		{
			if ( strlen($_REQUEST['password']) < 7 )
			{
				echo json_encode(array('status'=>'error','error'=>'too short password'));	
				exit;
			}
			$ok=false;
			$mysqli = new mysqli($db_server,$db_user,$db_pass,$database);
			if (mysqli_connect_error()) {
				die('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
			}
			// if user have entry already
			$stmt = $mysqli->prepare("SELECT charID FROM user WHERE charID=?");
			$stmt->bind_param("i",$_REQUEST['charID']);
			$stmt->execute();
			$stmt->store_result();
			$ecount=$stmt->num_rows;
			$stmt->close();
			// delete old data
			if ( $ecount > 0 )
			{
				$stmt = $mysqli->prepare("DELETE FROM user WHERE charID=?");
				$stmt->bind_param("i",$_REQUEST['charID']);
				$stmt->execute();
				$stmt->close();
			}
			$cu=$chars[$_REQUEST['charID']];
			if ( $stmt = $mysqli->prepare("INSERT INTO user (username,password,apiuser,apikey,corp_name,ally_name,charID,corpID,allyID) VALUES (?,?,?,?,?,?,?,?,?)") ) {
				$stmt->bind_param("ssisssiii",
					$cu['name'],
					md5($_REQUEST['password']),
					$_REQUEST['apiuser'],
					$_REQUEST['apikey'],
					$cu['corpShort'],
					$cu['allyShort'],
					$cu['charID'],
					$cu['corpID'],
					$cu['allyID'] );
				$ok=$stmt->execute();
				$stmt->close();
			} else {
				echo $mysqli->error;
			}
			if ( ! $ok )
				echo json_encode(array('status'=>'error','error'=>'database error'));
			else
				echo json_encode(array('status'=>'ok'));
			exit;
		}
		else		
			echo json_encode($out);
	} else {
		echo json_encode(array('status'=>'error','error'=>'data error'));
	}
?>
