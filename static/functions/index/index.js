/*$(document).bind('dragover', function (e) {
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
});*/

$(document).ready(function() {
	dialogBox = $('<div id="track-info"></div>').dialog({autoOpen: false, width: 600, close: function() { $(this).html(''); }});
    radioJS.refreshTable();
    radioJS.scrollbar = $('#table-container');
    radioJS.scrollbar.tinyscrollbar();
    
    /* this hides extra leftover tooltips */
    $("#main")
        .on("mouseover", function () {
            $('.score').tooltip('close');
        })
        .on("mouseover", "#voting-table", function (event) {
            event.stopPropagation();
        });
});

var radioJS = {
    //Whether or not we're using websockets
    websocks: false,
    scrollbar: null,
    
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
        
        $.ajax({
            type: "GET",
            url: "songs/vote/" + direction,
            data: {
                'id': id
            },
            success: function(votedata) {
                var voteIndex = votedata.indexOf('votemax');
                var message;
                if(votedata == 'identical') {
                    message = "This vote is identical to a previous vote you made.";
                } else if(voteIndex != -1) {
                    message = "You have downvoted too many songs. You must wait " + formatNiceTime(votedata.substr(11)) + " before downvoting again.";
                }
                if(message) {
                    if(dialogBox.dialog('isOpen') === true) {
                        dialogBox.dialog('close');
                    }
                    dialogBox.dialog({'title': "Vote Error"});
                    dialogBox.html('<span class="ui-icon ui-icon-circle-close" style="float: left; margin: 0 7px 50px 0;"></span>' + message);
                    console.log(dialogBox.dialog('open'));
                } else {
                    radioJS.voteChangeArrows(direction, id);
                    if(!radioJS.websocks) {
                        if(votedata.indexOf('identical') != -1) {
                            $('#score-' + escapeID(id)).html(votedata.split('!!')[0]);
                            radioJS.reloadTable();
                        }
                    }
                }
            }
        });
    },
    
    voteChangeArrows: function(direction, id) { 
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
                radioJS.scrollbar.tinyscrollbar_update('relative');
                $( ".score" ).tooltip({
                    position: {
                        my: "left+40 top",
                        at: "left bottom",
                        collision: "flipfit"
                    },
                    content: function(callback) {
                        var id = $(this).attr('id');
                        id = id.substr(6);
                        radioJS.getTooltip(id, callback);
                    },
                    close: function( event, ui ) {
                        ui.tooltip.hover(
                            function () {
                                $(this).stop(true).fadeTo(400, 1); 
                            },
                            function () {
                                $(this).fadeOut("400", function(){ $(this).remove(); })
                            }
                        );
                    }
                });
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
                    //console.log(trackData);
                    
                    if(dialogBox.dialog('isOpen') === true) {
                        dialogBox.dialog('close');
                    }
                    dialogBox.dialog({'title': trackData.Title});
                    dialogBox.html(trackData.Info);
                    dialogBox.dialog('open');
                } else {
                    alert('Error: ' + trackData);
                }
            }
        });
    },
    
    getTooltip: function(id, callback) {
        $.get(  '/songs/votes/',
                {'id': id},
                function(voteData) {
                    callback(voteData);
                }
        );
    }
}