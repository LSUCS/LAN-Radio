function escapeID(ID) {
    return ID.replace( /(:|\.|\[|\]|\/)/g, "\\$1" );
}

function formatTime(time) {
    var hours = 0;
    var minutes = 0;
    var seconds = 0;
    while(time > 60*60) {
        hours++;
        time -= 60*60;
    }
    while(time > 60) {
        minutes++;
        time -= 60;
    }
    time = Math.round(time);
    
    if(time < 10) time = '0' + time;
    
    if(hours > 0) {
        if(minutes < 10) minutes = '0' + minutes;
        return hours + ':' + minutes + ':' + time;
    } else {
        return minutes + ':' + time;
    }
}

function formatNiceTime(time) {
    var minutes = 0;
    var seconds = 0;
    while(time > 60) {
        minutes++;
        time -= 60;
    }
    
    if(minutes > 0) { 
        var ret = minutes + ' Minutes';
        if(time > 0) {
            ret += ' and ' + time + ' Seconds';
        }
        return ret
    } else {
        return time + ' Seconds';
    }
}



function changeNav(page) {
    if(page == "index") {
        if(window.location.pathname == "/" || window.location.pathname == "") return false;
    }    
    if(window.location.pathname.indexOf(page) !== -1) return false;
}

function formatUsername(ID, Username) {
    return Username;
}