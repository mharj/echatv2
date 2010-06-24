var touch=false;

function send_auth() {
	var pwd=$("input[name=password]").val();
	// we md5 if not md5 already
	if ( pwd.length != 32 ) {
		pwd=md5(pwd);
	}

	if ( $("#save_cookie").is(':checked') ) {
		$.cookie("user_login", $("input[name=username]").val() );
		$.cookie("user_password", pwd );
	}
	$("#login_window").fadeOut('fast',function() {
		$.ajax({
			type: "POST",
			url: "api_login.php",
			data: "username="+$("input[name=username]").val()+"&password="+pwd,
			dataType: "json",
			timeout: 10000,
			success: function(json){
				if ( json.status )
				{
					if (  json.status == 'error' ) {
						$("#login_window").html("<table>"
							+"<tr><td style='font-size: 11px;text-align: center;'><b style='color: red'>"+json.error+"</b></td><td><input type='button' class='button' value='Ok' onClick='send_api()'></td></tr>"
							+"</table>");
						$("#login_window").fadeIn('fast');				
					} else {
						window.location='index.php#public';
					}
				}
			},
			error: function(x,t,thro){
				alert('error: '+t);
			}		
		});
	});
}

function to_create() {
	$("#login_window").fadeOut('fast',function() {
		window.location='create.php';
	});
}

$(document).ready(function() {
	$("input[name=username]").focus( function() {
		touch=true;
		$("input[name=username]").val('');
	});
	$("#login_window").hide();
	// test what we really have working JQuery
	$("#login_window").html("<input type='text' name='username' value='Char name' style='width:80px' onFocus='$(this).val(\"\");'/> <input type='password' name='password' value='' style='width: 80px' /> <input class='button' type='button' value='Login'style='width: 40px' onClick='send_auth()' /><br/><input id='save_cookie' type=checkbox>:Save <input class='button' type=button value='Create Account' onClick='to_create();'/>");
	$("#login_window").fadeIn('fast');
	if ( $.cookie("user_login") ) {
		$("input[name=username]").val($.cookie("user_login"));
	}
	if ( $.cookie("user_password") ) {
		$("input[name=password]").val($.cookie("user_password"));
	}
});
