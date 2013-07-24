changingTable = false;

function changeTable(table) {
    if(changingTable) return;
    
    var currentTable = $('#current-table').html();
    if(table == currentTable) return;
    
    changingTable = true;
    
    var direction;
    var otherDirection;
    
    var tablePositions = new Array;
    tablePositions['recent'] = 0;
    tablePositions['popular'] = 1;
    tablePositions['popartist'] = 2;
    tablePositions['popuser'] = 3;
    
    if(tablePositions[table] > tablePositions[currentTable]) {
        direction = 1;
    } else {
        direction = 0;
    }
    
    $.ajax({
        "type": "GET",
        "url": "ajax.php",
        "data": {'action': 'gethistorytable', 'table': table},
        "dataType": "html",
        "success": function(data) {
            createTable(data, direction);
       }
    });
}

function createTable(tableData, direction) {
    var currentTable = $('#history-container .table-container');
    var currentOffsets = currentTable.offset();
    
    var windowWidth = $(window).width();
    
    var position1 = windowWidth + 20;
    var position2 = -1*currentOffsets.left - 1200 - 20;
    if(direction) {
        var initialPosition = position1;
        var destinationPosition = position2;
    } else {
        var initialPosition = position2;
        var destinationPosition = position1;
    }
    
    var newTable = document.createElement('div');
    newTable = $(newTable).html(tableData).addClass('table-container');
    newTable = newTable.css('position', 'absolute').css('top', currentOffsets.top + 'px');
    newTable = newTable.css('left', initialPosition + 'px');
    $('#history-container').append(newTable);
    
    finished = 0;
    
    moveTable(newTable, currentOffsets.left, currentTable);
    
    currentTable.css('position', 'absolute').css('left', currentOffsets.left).css('top', currentOffsets.top);
    moveTable(currentTable, destinationPosition, currentTable);
}

function moveTable(table, Xpos, tableToRemove) {
    $(table).animate({
        position: 'absolute',
        left: Xpos + 'px'
    }, {
        queue: false,
        duration: 1000,
        complete: function() {
            cleanUp(tableToRemove);
        }
    });
}

function cleanUp(tableToRemove) {
    if(++finished > 1) {
        tableToRemove.remove();
        changingTable = false;
    }
    return;
}