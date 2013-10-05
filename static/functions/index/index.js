
$(document).bind('dragover', function (e) {
    var dropZone = $('#dropcanvas'),
        timeout = window.dropZoneTimeout;
    if (!timeout) {
        dropZone.addClass('in');
    } else {
        clearTimeout(timeout);
    }
    if (e.target === dropZone[0]) {
        dropZone.addClass('hover');
    } else {
        dropZone.removeClass('hover');
    }
});

$(function() {
	dialogBox = $('<div id="track-info"></div>').dialog({autoOpen: false, width: 600});
    radioJS.refreshTable();
});

var radioJS = {
    //Whether or not we're using websockets
    websocks: false,
    
    //Gets the position of a song
    getPosition: function(rowID) {
        return $($('#' + rowID + ' .col0')[0]).html();
    },
    
    addRow: function(row, position) {
        var allRows = $('#voting-table tr');
         
        //Add 1 because our row will be in position: totalRows + 1
        var difference = allRows.length - position + 1;
        var pxdiff = -25 * difference;
        
        //Move rows down to make space for the new one
        this.moveRows($(allRows[position - 1]), -9999, true);
        
        row.css('top', pxdiff + 'px');
        row.css('display', 'none')
        $('#voting-table').append(row);
        row.fadeIn();
        
        this.refreshTable();
    },
    
    removeRow: function(row) {
        row.animate({
            opacity: 0
        }, {
            duration: 300,
            queue: false,
            complete: function() {
                radioJS.moveRows(row, -9999);
                window.setTimeout('radioJS.refreshTable();', 400);
            }
        });
    },

    //Controller function managing all movements. The only one that needs to be called
    moveRow: function(row, distance, vote) {
        row.css('z-index', 1);
        this.lift(row, distance, vote);
    },
    
    lift: function(row, distance, vote) {
        if(!vote && distance < 0) {
            this.moveUp(row, distance, false);
            return;
        }
        row.animate({
            top: '-2px',
            left: '2px'
        }, {
            duration: 200,
            queue: false,
            complete: function() {
                radioJS.moveUp(row, distance, vote);
                if(vote) radioJS.moveRows(row, distance);
            }
        });
    },
    
    moveRows: function(row, distance, adding) {
        if(distance < 0) {
            var rows = row.nextAll().splice(0,-distance);
            var direction = -1;
        } else {
            var rows = row.prevAll().splice(0,distance);
            var direction = 1;
        }
        if(adding) {
            rows.push(row.toArray()[0]);
            direction *= -1;
        }
        
        $(rows).each(function() {
            $(this).animate({
                top: direction*25 + 'px'
            }, {
                duration: 300,
                queue: false
            });
        });
    },

    moveRowDifferences: function(diffs) {
        for(var id in diffs) {
            if(diffs[id] == 0) continue;
            this.moveRow($('#row-' + escapeID(id)), diffs[id], false);
        }
    },

    moveUp: function(row, distance, vote) {
        console.log("moving by " + distance);
        row.animate({
            top: -25 * distance - 2
        }, {
            duration: 800,
            queue: false,
            complete: function() {
                if(vote || distance > 0) radioJS.drop(row);
            }
        });
    },

    drop: function(row) {
        row.animate({
            top: (parseInt(row.css('top')) + 2) + 'px',
            left: (parseInt(row.css('left')) - 2) + 'px'
        }, {
            duration: 200,
            queue: false,
            complete: function() {
                row.css('z-index', 0);
                radioJS.refreshTable();
            }
        });
    },

    // 1 = up, 0 = down
    vote: function(direction, id) {
        var id2 = escapeID(id);
        movingPosition = this.getPosition('row-' + id2);
        if(direction == 1) {
            $('#button-up-' + id2).removeClass('voteup');
            $('#button-up-' + id2).removeClass('voteup-red');
            $('#button-up-' + id2).addClass('voteup-green');
            $('#button-down-' + id2).removeClass('votedown-green');
            $('#button-down-' + id2).removeClass('votedown-red');
            $('#button-down-' + id2).addClass('votedown');
        } else if(direction == 0) {
            $('#button-down-' + id2).removeClass('votedown');
            $('#button-down-' + id2).removeClass('votedown-green');
            $('#button-down-' + id2).addClass('votedown-red');
            $('#button-up-' + id2).removeClass('voteup');
            $('#button-up-' + id2).removeClass('voteup-green');
            $('#button-up-' + id2).addClass('voteup');
        } else {
            return;
        }
        
        $.ajax({
            type: "GET",
            url: "songs/vote/" + direction,
            data: {
                'id': id
            },
            success: function(votedata) {
                if(!this.websocks) radioJS.reloadTable();
            }
        });
    },
    
    reloadTable: function() {
        $.ajax({
            type: "GET",
            url: "/songs/tableinfo",
            dataType: "json",
            success: function(table) {
                radioJS.changeTable(table);
                window.setTimeout("radioJS.refreshTable();", 1500);
            }
        });
    },
    
    refreshTable: function() {
        $.ajax({
            type: "GET",
            url: "/songs",
            success: function(table) {
                $('#table-goes-here').html(table);
                $('#table-container').tinyscrollbar();
            }
        });
    },
    
    changeTable: function(newTable) {
        
        var oldTable = this.readTable($($('#table-container').html()));
        var differences = this.compareTables(newTable, oldTable);
        
        console.log('DIFFERENCES:');
        console.log(differences);
        this.moveRowDifferences(differences);
    },
    
    readTable: function(table) {
        var tableRows = table.find("tbody").children('tr');
        
        var tableInfo = {};
        var rowID;
        var position;
        var addedRow = false;
        
        for(var row in tableRows) {
    
            if(!$.isNumeric(row)) break;
            
            rowID = $(tableRows[row]).attr('id').substr(4);
            position = parseInt(row)+1;
            
            tableInfo[rowID] = {'row_position': position};        
        }
        return tableInfo;
    },
    
    compareTables: function(newTable, oldTable) {
    
        var differences = [];
        for(var rowID in oldTable) {
            if(typeof newTable[rowID] != 'undefined') {
                differences[rowID] = oldTable[rowID].row_position - newTable[rowID].row_position;
            } else {
                differences[rowID] = 'deleted';
            }
        }
        for(var rowID in newTable) {
            if(typeof differences[rowID] == 'undefined') {
                differences[rowID] = 'added';
            }
        }
        return differences;
    },
    
    clickRow: function(id) {
        $.ajax({
            type: "GET",
            url: "/songs/trackinfo/",
            data: {
                'id': id
            },
            dataType: "json",
            success: function(trackData) {
                if(typeof trackData === 'object') {
                    console.log(trackData);
                    
                    if(dialogBox.dialog('isOpen') === true) {
                        dialogBox.dialog('close');
                    }
                    dialogBox.dialog({'title': radioJS.getDialogTitle(trackData)});
                    dialogBox.html(radioJS.trackDialog(trackData));
                    console.log(dialogBox.dialog('open'));
                    //dialog = $('<div id="track-info" title="' + getDialogTitle(trackData) + '">' + trackDialog(trackData) + '</div>').dialog();
                } else {
                    alert('Error: ' + trackData);
                }
            }
        });
    },
    
    getDialogTitle: function(data) {
        return data.Artist + " - " + data.Title;
    },
    
    trackDialog: function(data) {
        //yayyyy, tables for layout
        return "<table><tr><td colspan='2'>" + this.playableTrack(data.trackid) + "</td></tr><tr><td>" + this.displayTrackInfo(data) + "</td><td>" + this.displayVoteInfo(data) + "</td></tr></table>";
    },
    
    displayTrackInfo: function(info) {
        var ret = [info.Title, info.Artist, info.Album, formatTime(info.Duration)];
        return ret.join("<br />")
    },
    
    playableTrack: function(id) {
        if(id.indexOf('spotify') !== -1) {
            return '<iframe src="https://embed.spotify.com/?uri=' + id + '" width="250" height="330" frameborder="0" allowtransparency="true"></iframe>';
        } else if (id.indexOf('youtube') !== -1) {
            return '<iframe width="560" height="315" src="http://www.youtube.com/embed/' + this.getYoutubeCode(id) + '" frameborder="0" allowfullscreen></iframe>';
        } else {
            return 'a picture or something will go here';
        }
    },
    
    getYoutubeCode: function(id) {
        var parts = id.split('/');
        return parts[parts.length-1];
    },
    
    displayVoteInfo: function(data) {
        var ret = [data.Votes, formatUsername(data.addedBy, data.Username)];
        return ret.join("<br />");
    }
}