$(function() {
    reload(true);
});

function reload(initial) {
    $.ajax({
        "url": "/mpd/playerinfo",
        "dataType": "json",
        "success": function(data) {
            if(typeof data === 'object') {
                if(data.error) {
                    console.log("Reloading info. Error, adding no info.");
                    addNoInfo();
                } else {
                    console.log("Reloading info. Track playing.");
                    addInfo(data, initial);
                }
            }
       } 
    });
}

var secondTimer;
var barWidth = 350;
function addInfo(info, initial) {
    if(info.position > info.length) {
        info.position = info.length;
    } else {
        window.clearTimeout(secondTimer);
        secondTimer = window.setTimeout("second();", 1000);
    }
    
    $('#artist').html(info.artist);
    $('#song').html(info.track);
    $('#current-time').html(timestamp(info.position));
    $('#current-time-seconds').html(info.position);
    $('#end-time').html(timestamp(info.length));
    $('#end-time-seconds').html(info.length);
    $('#votes').html(info.votes);
    
    if(initial) {
        movePointer(info.position);
    }   
    
    reloadTimer = window.setTimeout("reload();", 1000*10);
    
}

function addNoInfo() {
    window.clearTimeout(secondTimer);
    
    $('#artist').html("No Track Playing");
    $('#song').html("");
    $('#current-time').html("0:00");
    $('#current-time-seconds').html("0");
    $('#end-time').html("0:00");
    $('#end-time-seconds').html("0");
    $('#votes').html("");
    
    reloadTimer = window.setTimeout("reload();", 1000*5);
}

function second() {
    var time = parseInt($('#current-time-seconds').html());
    time += 1;
    
    $('#current-time').html(timestamp(time));
    $('#current-time-seconds').html(time);
    
    movePointer(1);
    
    if(time !== parseInt($('#end-time-seconds').html())) {
        secondTimer = window.setTimeout("second();", 1000);
    }
}

function movePointer(seconds) {
    var newLeft = parseInt($('#current-position').css('left')) + barWidth/$('#end-time-seconds').html() * seconds;
    $('#current-position').css('left', newLeft + 'px');
}

function timestamp(time) {
    var hours = 0;
    var minutes = 0;
    
    while(time > 60*60) {
        hours += 1;
        time -= 60*60;
    }
    while(time > 60) {
        minutes += 1;
        time -= 60;
    }
    var ret = '';
    
    if(hours > 0) {
        ret = hours + ':' + showTimeNumber(minutes);
    } else {
        ret = minutes;
    }
    ret += ':' + showTimeNumber(time);
    return ret;
}

function showTimeNumber(digit) {
    if(typeof digit == 'undefined' || digit == '' || !isInt(digit)) return '00';
    if(digit < 10) return '0' + digit;
    return digit;
}

function isInt(n) {
    return n % 1 === 0;
}