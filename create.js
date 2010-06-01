var apiuser='';
var apikey='';

function to_login() {
	$("#login_window").fadeOut('fast',function() {
		window.location='login.php';
	});
}

function send_reg () {
	$("#login_window").fadeOut('fast');
	$.ajax({
		type: "POST",
		url: "api_check.php",
		data: "apiuser="+apiuser+"&apikey="+apikey+"&charID="+$("#charID").val()+"&password="+$("input[name=password]").val(),
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
					$("#login_window").html("<table>"
						+"<tr><td style='font-size: 11px;text-align: center;'>Account created!</td></tr>"
						+"<tr><td><input type=button class='button' value='Login' onClick='to_login()' /></td></tr>"
						+"</table>");
					$("#login_window").fadeIn('fast');
				}
			}
		},
		error: function(x,t,thro){
			alert('error: '+t);
		}		
	});
}

function send_api () {
	if ( $("input[name=apiuser]").length )
		apiuser=$("input[name=apiuser]").val();
	if ( $("input[name=apikey]").length )
		apikey=$("input[name=apikey]").val();
	$("#login_window").fadeOut('fast');
	$.ajax({
		type: "POST",
		url: "api_check.php",
		data: "apiuser="+apiuser+"&apikey="+apikey,
		dataType: "json",
		timeout: 10000,
		success: function(json){
			if ( json.status )
			{
				alert(  json.error );
				$("#status_bar").show();
				if (  json.status == 'error' )
					$("#status_text").html( '<b style="color: red">' + json.error+'<\/b>' );
				else
				{
					$("#status_text").html('<b style="color: red">Account created<\/b>');
					$(".reg").hide();
				}
				return;
			}
			$("#login_window").html("<table>"
				+"<tr><td colspan=3 style='font-size: 11px;text-align: center;'>Account creation</td></tr>"
				+"<tr><td style='width: 110px;text-align: left;font-size:12px;'>Username: </td><td><select id='charID'></select></tr>"
				+"<tr><td style='width: 110px;text-align: left;font-size:12px;'>Password: </td><td><input type='password' name='password' value='' style='width:80px' /></td><td> <input type='button' class='button' value='Save' onClick='send_reg();'></td></tr>"
				+"</table>");
			
			for ( i=0;i<json.length;i++)
				$("#charID").append("<option value='"+json[i].charID+"'>"+json[i].name+"<\/option>");

			$("#login_window").fadeIn('fast');
		},
		error: function(x,t,thro){
			alert('error: '+t);
		}
	})
}

$(document).ready(function() {
	$("#login_window").hide();
	// test what we really have working JQuery
	$("#login_window").html("<table>"
				+"<tr><td colspan=3 style='font-size: 11px;text-align: center;'>Username search</td></tr>"
				+"<tr><td style='width: 110px;text-align: left;font-size:11px;'>Api User ID: </td><td><input type='text' name='apiuser' value='' style='width:80px' /></td><td><a href='http://www.eveonline.com/api/default.asp' class='button' target='_blank'>Get API</a></td></tr>"
				+"<tr><td style='width: 110px;text-align: left;font-size:11px;'>Api Key: </td><td><input type='text' name='apikey' value='' style='width:80px' /></td><td> <input type='button' class='button' value='Send' onClick='send_api()'></td></tr>"
				+"</table>");
	$("#login_window").fadeIn('fast');
});