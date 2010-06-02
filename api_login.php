<?php
	session_start();
	require('db_settings.php');
	function stmt_bind_assoc (&$stmt, &$out) {
		$data = mysqli_stmt_result_metadata($stmt);
		$fields = array();
		$out = array();

		$fields[0] = $stmt;
		$count = 1;

		while($field = mysqli_fetch_field($data)) {
			$fields[$count] = &$out[$field->name];
			$count++;
		}   
		call_user_func_array('mysqli_stmt_bind_result', $fields);
	}
	// mysql
	$mysqli = new mysqli($db_server,$db_user,$db_pass,$database);
	if (mysqli_connect_error()) {
		die('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());	
	}
	$stmt = $mysqli->prepare("SELECT * FROM user WHERE username=?");
	$stmt->bind_param('s',$_POST['username']);
	$stmt->execute();
	$user = array();
	stmt_bind_assoc($stmt, $user);
	$stmt->fetch();
	// basic tests
	if ( empty($user['username']) ){
		echo json_encode(array('status'=>'error','error'=>'account not found'));
		exit;
	}	
	if ( $user['password'] != md5($_POST['password']) )
	{
		echo json_encode(array('status'=>'error','error'=>'wrong password'));
		exit;
	}
	// user auth ok, save session and browser
	$_SESSION['my_channels']=array();
	$_SESSION['my_channels']['public']='public';
	if ( isset($user['corp_name']) && ! empty($user['corp_name']) )
		$_SESSION['my_channels']['corporation']=$user['corp_name'];

	if ( isset($user['ally_name']) && ! empty($user['ally_name']) )
		$_SESSION['my_channels']['alliance']=$user['ally_name'];
	$_SESSION['my_channels']['dev']='dev';
#	$_SESSION['my_channels']['priv']=$user['charID'];

	$_SESSION['user']=$user;
	if ( isset($_SERVER['HTTP_EVE_TRUSTED']) ) // i=IGB // o=OOG browser
		$_SESSION['user']['b']='i';
	else
		$_SESSION['user']['b']='o';
	$_SESSION['user']['led']='green';	
	echo json_encode(array('status'=>'ok') );
?>
