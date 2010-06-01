<?php
	session_start();
	session_destroy(); // clear old data
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="jquery-timers.js"></script>
		<title>EChat</title>
		<link href="reset-min.css" rel="stylesheet" type="text/css" />
		<link href="default.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="login.js"></script>
		<link rel="Shortcut Icon" href="http://www.ushrakhan.com/favicon.ico">
	</head>
	<body>
		<table id='tbody'>
		<tr id='title_box'><td colspan='2'>EChat - Login</td></tr>
		</table>
		<div id='toggle_title'>_</div>
		<div id='full_container'>
			<div id='login_window'class='box'>
				Javascript or JQuery not working!
			</div>
		</div>
	</body>
</html>

