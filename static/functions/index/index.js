/*

So basically, I have no idea how to do OOP in javascript. And I googled it and it seems really unusual.
SO, new plan. Use globals all the time. I'm sorry anyone reading over this, or future me, but it works...

*/

//The jQuery object of the row being moved
var movingRow;
//The starting position of this row (supplied by JS HTML)
var movingPosition;
//The new position of this row, from ajax call
var movingNewPosition;
//The difference between these two positions
var movingBy;

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

//Controller function managing all movements. The only one that needs to be called
function moveRow(row, distance, vote) {
    row.css('z-index', 1);
    lift(row, distance, vote);
}
function lift(row, distance, vote) {
    if(!vote && distance < 0) {
        moveUp(row, distance);
        return;
    }
    row.animate({
        top: '-2px',
        left: '2px'
    }, {
        duration: 200,
        queue: false,
        complete: function() {
            moveUp(row, distance, vote);
            if(vote) moveRows();
        }
    });
}
function moveRows() {
    if(movingBy < 0) {
        var rows = movingRow.nextAll().splice(0,-movingBy)
        var direction = -1;
    } else {
        var rows = movingRow.prevAll().splice(0,movingBy);
        var direction = 1;
    }
    $(rows).each(function(e) {
        $(this).animate({
            top: direction*25 + 'px'
        }, {
            duration: 300,
            queue: false
        });
    });
}

function moveRowDifferences(diffs) {
    for(var id in diffs) {
        if(diffs[id] == 0) continue;
        moveRow($('#row-' + escapeID(id)), diffs[id], false);
    }
}

function moveUp(row, distance, vote) {
    row.animate({
        top: -25 * distance - 2
    }, {
        duration: 800,
        queue: false,
        complete: function() {
            if(vote || distance > 0) drop(row);
        }
    });
}

function drop(row) {
    row.animate({
        top: (parseInt(row.css('top')) + 2) + 'px',
        left: (parseInt(row.css('left')) - 2) + 'px'
    }, {
        duration: 200,
        queue: false,
        complete: function() {
            row.css('z-index', 0);
            //reloadTable();
        }
    });
}

// 1 = up, 0 = down
function vote(direction, id, currentpos) {
    movingPosition = currentpos;
    if(direction == 1) {
        $('#button-up-' + escapeID(id)).removeClass('voteup');
        $('#button-up-' + escapeID(id)).removeClass('voteup-red');
        $('#button-up-' + escapeID(id)).addClass('voteup-green');
        $('#button-down-' + escapeID(id)).removeClass('votedown-green');
        $('#button-down-' + escapeID(id)).removeClass('votedown-red');
        $('#button-down-' + escapeID(id)).addClass('votedown');
    } else if(direction == 0) {
        $('#button-down-' + escapeID(id)).removeClass('votedown');
        $('#button-down-' + escapeID(id)).removeClass('votedown-green');
        $('#button-down-' + escapeID(id)).addClass('votedown-red');
        $('#button-up-' + escapeID(id)).removeClass('voteup');
        $('#button-up-' + escapeID(id)).removeClass('voteup-green');
        $('#button-up-' + escapeID(id)).addClass('voteup');
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
            /*votedata = votedata.split('!!');
            movingNewPosition = votedata[1];
            $('#score-' + escapeID(id)).html(votedata[0]);
            if(movingPosition - movingNewPosition !== 0) {
                movingRow = $('#row-' + escapeID(id));
                movingBy = movingPosition - movingNewPosition;
                moveRow(movingRow, movingBy, true);
            }*/
            reloadTable();
        }
    });
}

function reloadTable() {
    $.ajax({
        type: "GET",
        url: "/songs/tableinfo",
        dataType: "json",
        success: function(table) {
            changeTable(table);
            window.setTimeout("refreshTable();", 1500);
        }
    });
}

function refreshTable() {
    $.ajax({
        type: "GET",
        url: "/songs",
        success: function(table) {
            $('#table-goes-here').html(table);
            $('#table-container').tinyscrollbar();
        }
    });
}

function changeTable(newTable) {
    
    var oldTable = readTable($($('#table-container').html()));
    var differences = compareTables(newTable, oldTable);
    
    console.log('DIFFERENCES:');
    console.log(differences);
    moveRowDifferences(differences);
}

function readTable(table) {
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
        /*
        //moving down
        if(movingBy < 0) {
            
            if(position == movingPosition) {
                //do nothing
            } else if(position == movingNewPosition) {
                addedRow = true;
                tableInfo[rowID] = {'row_position': position-1};                
                tableInfo[movingRow.attr('id').substr(4)] = {'row_position': position};
            } else if(position > movingPosition && position < movingNewPosition) {
                tableInfo[rowID] = {'row_position': position-1};
            } else {
                tableInfo[rowID] = {'row_position': position};
            }
            //This happens if the row has been moved to the bottom
            if(!addedRow) {
                tableInfo[movingRow.attr('id').substr(4)] = {'row_position': position};
            }
        }
        //moving up
        else {
            if(position == movingNewPosition) {
                tableInfo[movingRow.attr('id').substr(4)] = {'row_position': position};
                tableInfo[rowID] = {'row_position': position+1};
            } else if(position == movingPosition) {
                //do nothing
            } else if(position > movingNewPosition && position < movingPosition) {
                tableInfo[rowID] = {'row_position': position+1};                
            } else {
                tableInfo[rowID] = {'row_position': position};
            }
        }*/
    }
    return tableInfo;
}

function compareTables(newTable, oldTable) {

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
}

$(function() {
	dialogBox = $('<div id="track-info"></div>').dialog({autoOpen: false, width: 600});
    refreshTable();
});
function clickRow(id) {
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
                dialogBox.dialog({title: getDialogTitle(trackData)});
                dialogBox.html(trackDialog(trackData));
                console.log(dialogBox.dialog('open'));
                //dialog = $('<div id="track-info" title="' + getDialogTitle(trackData) + '">' + trackDialog(trackData) + '</div>').dialog();
            } else {
                alert('Error: ' + trackData);
            }
        }
    });
}

function getDialogTitle(data) {
    return data.Artist + " - " + data.Title;
}

function trackDialog(data) {
    //yayyyy, tables for layout
    return "<table><tr><td colspan='2'>" + playableTrack(data.trackid) + "</td></tr><tr><td>" + displayTrackInfo(data) + "</td><td>" + displayVoteInfo(data) + "</td></tr></table>";
}

function displayTrackInfo(info) {
    var ret = [info.Title, info.Artist, info.Album, formatTime(info.Duration)];
    return ret.join("<br />")
}

function playableTrack(id) {
    if(id.indexOf('spotify') !== -1) {
        return '<iframe src="https://embed.spotify.com/?uri=' + id + '" width="250" height="330" frameborder="0" allowtransparency="true"></iframe>';
    } else if (id.indexOf('youtube') !== -1) {
        return '<iframe width="560" height="315" src="http://www.youtube.com/embed/' + getYoutubeCode(id) + '" frameborder="0" allowfullscreen></iframe>';
    } else {
        return 'a picture or something will go here';
    }
}

function getYoutubeCode(id) {
    var parts = id.split('/');
    return parts[parts.length-1];
}

function displayVoteInfo(data) {
    var ret = [data.Votes, formatUsername(data.addedBy, data.Username)];
    return ret.join("<br />");
}