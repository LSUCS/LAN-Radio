$(document).ready(function() {
    $('#volume-slider').slider({
        orientation: "horizontal",
        range: "min",
        "max": 100,
        change: controls.changeVolume
    });
    controls.reloadStatus();
    
    $('#add-button').button().on('click', function() {
        controls.sendCommand('add', $('#add-song').val());
    });
});

var controls = {
    url: '/mpd/command/',
    reloadStatus: function() {
        this.sendCommand('status');
    },
    
    updateStatus: function(data) {
        var status = "";
        
        for(var x in data) {
            if(typeof data[x] === 'object') {
                status += x + ": <br />";
                for(y in data[x]) {
                    if(typeof data[x][y] === 'object') {
                        status += "&nbsp;&nbsp;&nbsp;&nbsp;" + y + ": <br />";
                        for(z in data[x][y]) {
                            status += "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                            status += z + ": " + data[x][y][z] + "<br />";
                        }
                    } else {
                        status += "&nbsp;&nbsp;&nbsp;&nbsp;" + y + ": " + data[x][y] + "<br />";
                    }
                }
            } else {
                status += x + ": " + data[x] + "<br />";
            }
        }
        $('#status').html(status);
        
        
        $('#volume-slider').slider("value", data.volume);
    },
    
    click: function(command) {
        this.sendCommand(command);
    },
    
    changeVolume: function() {
        controls.sendCommand('changevolume', $(this).slider("value"));
    },
    
    sendCommand: function(command, args) {
        var url = this.url + command;
        if(args) url += "/" + args;
        $.ajax({
            "type": "GET",
            "url": url,
            "dataType": "json",
            "success": function(data) {
                if(typeof data === 'object') {
                    console.log(data);

                    controls.receiveCommand(command, data);
                }
           }
        });
    },
    
    receiveCommand: function(command, data) {
        if(data != null && data.error) {
            $('#status').html("ERROR: " + data.error);
        }
        switch(command) {
            case 'status':
                this.updateStatus(data);
                break;
            case 'changevolume':
                break;
            default:
                this.reloadStatus();
        }
    }
};