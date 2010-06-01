<?php
	session_start();
	if ( ! isset($_SESSION['user']) ) // we are not logged yet
	{
		header("Location: login.php") ;
		exit;
	}
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=Edge" >
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="jquery-timers.js"></script>
		<title>EChat</title>
		<link href="reset-min.css" rel="stylesheet" type="text/css" />
		<link href="default.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="engine.js"></script>
		<script type="text/javascript" src="ext_js/utf8_encode.js"></script>
		<script type="text/javascript" src="ext_js/crc32.js"></script>
		<script type="text/javascript" src="ext_js/json_encode.js"></script>
		<script type="text/javascript" src="ext_js/i18n_loc_get_default.js"></script>
		<script type="text/javascript" src="ext_js/sort.js"></script>
		<link rel="Shortcut Icon" href="http://www.ushrakhan.com/favicon.ico">
	</head>
	<body>
		<table id='tbody'>
		<tr id='title_box'><td colspan='2'>EChat - Development<div id='clock'>--.--.--</div><div id='server_status'>Tranquility: Offline</div></td></tr>
		<tr><td id='tab_container'>
		</td>
		<td rowspan='2' id='user_container'>
		</td>
		</tr>
		<tr><td id='channel_container'>
		</td>
		</tr>
                <tr><td colspan='2' id='write_container'><table style='width: 100%;'><td style='width: 2em;'><input class='button' type=button id=send value=send /></td><td><input id='msg' type=text style='width: 100%' /></td></table></td></tr>
		</table>
		<div id='toggle_title'>_</div>
		<div id='toggle_userlist'>_</div>
	</body>
</html>

