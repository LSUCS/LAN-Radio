/*$(document).ready(function() {
	$(".passwordstrengthcheck").keyup(function(){
		var valid = 0,tovalidiate = $(this).val(),numcheck = /[0-9]/g; 
		var result = numcheck.test(tovalidiate);
		if(result == true) {valid += 1;}
		if(tovalidiate.length >= 5){valid += 1;}
		$(".validinnerbar").css("width",valid*0.5*(parseInt($(".validbar").css("width"))));
	});
});*/
var COOLDOWN = false;

function clickLogo() {
	$('#loginContainer').fadeIn();
    doLogin();
}
function doLogin() {
	$('#errorMessage').fadeOut();
	$('#username').focus();
	$('#username').keyup(function(event){
		if(event.keyCode == '13'){
			processLogin();
		}
	});
	$('#password').keyup(function(event){
		if(event.keyCode == '13'){
			processLogin();
		}
    });
}

function loginPressedKey(e) {
	if(window.event) { e = window.event; }
	if(e.KeyCode == 13) {
		processLogin();
	}
}

function processLogin() {
    //Validation
	if(COOLDOWN) return;
	var cont = true;
	if($('#username').val() == '') {
		cont = false;
		$('#userblock').css('border-color', '#f00');
	} else {
		$('#userblock').css('border-color', '#BBB');
	}
	if($('#password').val() == '') {
		cont = false;
		$('#passblock').css('border-color', '#f00');
	} else {
		$('#passblock').css('border-color', '#BBB');
	}
	if(!cont) return;
    
	COOLDOWN = true;
	setTimeout(function(){COOLDOWN=false;},1234);
	$.post('/login/login/', {action: 'login', user: $('#username').val(), password: $('#password').val()}, function(data){
		var msg = '';
		if(data=='dne_badpass'){ msg = "Your username or password was incorrect."; }
		if(data=='dne_banned'){ msg = "Your account is BANNED! You are not welcome here."; }

        console.log("msg is: " + msg);
		if(msg != ''){
			$('#errorMessage').html(msg);
			if($('#errorMessage').is(":visible")) {
				$('#errorMessage').effect('bounce', {distance: 3}, 200);
			} else {
				$('#errorMessage').fadeIn();	
			}
		} else {
			successfulLogin();
		}
	});
}

function successfulLogin(){
	$('#maincontent').fadeOut(500);
	$('#foot').html("");
	$('#foot').animate({height: '100%'}, 250, function(){ 
        $('#foot').animate({top: 0, height: '40px'}, function(){
		  document.location = "/index/";
		});
    });
}