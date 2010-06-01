var init=true;
var myAnchor='';
var users=new Array();
var lock=false;
var user_crc32='';

var urlPattern = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
//data.msg[i].msg = data.msg[i].msg.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');



function update_user_crc() {
	// clean undefined entries
	var rebuild=new Array();
	for ( var i in users ) {
		if ( users[i] ) 
			rebuild.push(users[i]);	
	}
	users=rebuild;
	sort(users);
	user_crc32=crc32(json_encode(users));
}

function resize_text() {
	var name='#channel_'+myAnchor;
	var ts=0;
	if ( $("#title_box").is(":visible") )
		ts=$("#title_box").height();
	$(name).height( ($(window).height()-ts-45 ) );
	$(".userlist_container").height( ($(window).height()-ts-25 ) );
	// scrolls chat down
	var name='#channel_'+myAnchor;
	if ( $(name).length )
		$(name).scrollTop( $(name)[0].scrollHeight );
}

function utc_clock() {
	var d = new Date();
	var hour=d.getUTCHours();
	if ( hour < 10 )
		hour='0'+hour;
	var min=d.getUTCMinutes();
	if ( min < 10 )
		min='0'+min;
	var sec=d.getUTCSeconds();
	if ( sec < 10 )
		sec='0'+sec;
	return( hour+':'+min+':'+sec );
}

function update_serverstatus(status) {
	if ( status == "true" )
		$("#server_status").html("Tranquility: <em style='color: green;'>Online</em>");
	if ( status == "false" )
		$("#server_status").html("Tranquility: <em style='color: red;'>Offline</em>");
}
function add_user(ch,c) {
	$("#userlist_"+ch).append('<div class="cid_'+c.charID+' uc_o_container"><div class="uc_i_container"><div class="uc_image"><img src="http://img.eve.is/serv.asp?s=64&c='+c.charID+'" width=32 height=32></div><div class="uc_name">'+c.ally_name+' '+c.corp_name+' '+c.username+'</div><div class="uc_status"></div></div></div>');	
}

function modify_user(c) {
	$(".cid_"+c.charID).html('<div class="uc_i_container"><div class="uc_image"><img src="http://img.eve.is/serv.asp?s=64&c='+c.charID+'" width=32 height=32></div><div class="uc_name">'+c.ally_name+' '+c.corp_name+' '+c.username+'</div><div class="uc_status"></div></div>');	
}
//
// TODO 
// - error: if timeout
// - error: if session lost
//
function pulseengine() {
	lock=true;
	$.ajax({
		type: "POST",
		url: "pulse.php",
		data: "init="+init+"&ucrc="+user_crc32,
		dataType: "json",
		timeout: 10000,				// 10sec timeout
		success: function(json) {
			if ( json ) {
				// server status
				if ( json.ss )
					update_serverstatus( json.ss );
				// channel list
				if ( json.chl ) {
					for ( var i in json.chl ) {
						if ( $('#tab_'+json.chl[i]).length == 0 ) {
							$('#tab_container').append("<div><a class='change_tab' id='tab_"+json.chl[i]+"' href='#"+json.chl[i]+"'>"+json.chl[i].toUpperCase()+"</a></div>");
							$('#channel_container').append("<div id='channel_"+json.chl[i]+"' class='message_board'></div>");
							$('#user_container').append("<div id='userlist_"+json.chl[i]+"' class='userlist_container'></div>");
						}
					}
					show_tab();
					resize_text();
				}
				// messages
				if ( json.msg )
				{
					for ( var i in json.msg )
					{
						var c=json.msg[i];
						var cid="#channel_"+c.ch;
						if ( c.m ) {
							c.m = c.m.replace(urlPattern, '<a href="$1" target="_blank">$1</a>');
							$(cid).append('<b>'+c.d+' ['+c.a+'] ('+c.c+') '+c.f+'></b> '+c.m+'<br/>');
							document.title = 'EChat - '+c.d+' #'+c.ch+' '+c.f;
							if ( ! init && c.ch != myAnchor )	
								$('#tab_'+c.ch).addClass('activitytab');
						}
					}
					// scroll down channel
					var name='#channel_'+myAnchor;
					if ( $(name).length )
						$(name).scrollTop( $(name)[0].scrollHeight );
				}
				// users
				if ( json.usr ) {
					// full clear if crc23 not match or empty
					if ( json.usr.clear )
						users=new Array();

					// add
					if ( json.usr.a ) {
						for ( var i in json.usr.a) {
							var c=json.usr.a[i];
							users.push(c);
							for ( var j in c.ch) {
								if ( $("#userlist_"+c.ch[j]+" .cid_"+c.charID).length == 0 ) {
									add_user(c.ch[j],c)
									if ( ! json.chl ) { // chl only in init
										var cid="#channel_"+c.ch[j];
										$(cid).append(json.usr.ts+' '+c.username+' Warped in!<br/>');
									}
								}
							}
						}
						resize_text();
					}
					// del
					if ( json.usr.d ) {
						for ( var i in json.usr.d) {
							var cid=json.usr.d[i];
							$(".cid_"+cid).remove();
							for ( var j in users ) {
								if ( users[j].charID == cid ) {
									for ( var g in  users[j].ch ) {
										var cid="#channel_"+users[j].ch[g];
										$(cid).append(json.usr.ts+' '+users[j].username+' Warped out!<br/>');
									}
									delete users[j];
								}
							}
						}
						resize_text();
					}
					// modify
					if ( json.usr.m ) {
						for ( var i in json.usr.m) {
							modify_user(json.usr.m[i]);
						}
					}
					update_user_crc();
				}
				// initial load done
				init=false;		
			}
		},
		complete: function() {
			lock=false;
		}		
	});
}
function send_msg() {
	$.ajax({
		type: "POST",
		url: "send.php",
		data: "msg="+$("#msg").val()+"&chan="+myAnchor,
		dataType: "json",
		timeout: 10000,
		success: function(json) {
			$("#msg").val('')
		}
	});
}

function show_tab() {
	$('.message_board').hide();
	$('.userlist_container').hide();
	$('#channel_'+myAnchor).show();
	$('#userlist_'+myAnchor).show();
	$('.change_tab').removeClass('activetab');
	$('#tab_'+myAnchor).removeClass('activitytab');
	$('#tab_'+myAnchor).addClass('activetab');	
}

$(document).ready(function() {
	$("#toggle_title").click(function(){
		$("#title_box").slideToggle(function(){
			resize_text();
		});
	});
	
	$("#toggle_userlist").click(function() {
		$("#user_container").toggle(0,function(){
//			resize_text();
		});		
	});
	// fullscreen resize operation
	$(window).resize(function() {
		resize_text();
	});
	resize_text();
	// delayed initial resize for chrome (bug?)
	$(document).oneTime(1000, function(i) {
		resize_text();
	});
	// clock
	$(document).everyTime(1000, function(i) {
		$("#clock").text(utc_clock());
	});
	$("#clock").text(utc_clock());
	// pulse
	pulseengine(); // once
	$(document).everyTime(1000, function(i) {
		if ( ! lock ) 
			pulseengine();
	});
//	pulseengine();
	
	$("#pulse").click( function() {
		if ( ! lock ) 
			pulseengine();
	});
	
	// channel pre-setup
	var doc_url = document.location.toString();
	if (! doc_url.match('#')  ) { 
		window.location="#public";
	}else {
		myAnchor = doc_url.split('#')[1];
		show_tab();
	}
	// change channel tab
	$(".change_tab").click( function() {
		window.location=$(this).attr('href');
		myAnchor=$(this).attr('href').split('#')[1];
		show_tab();
		resize_text()
	});
	// browser history check
	$(document).everyTime(100, function(i) {
		var doc_url = document.location.toString();
		if ( doc_url.match('#') && myAnchor != doc_url.split('#')[1] ) {
			myAnchor=doc_url.split('#')[1];
			show_tab();
			resize_text()
		}
	});
	$("#send").click( function() {
		send_msg();
	});
	$("#msg").live('keydown', function(e) {
		// submit from enter
		if ( e.keyCode == '13' ) {
			e.preventDefault();
			if ( $("#msg").val().length > 0 ) 
					send_msg();
		}			
	});	
});
