$(document).ready(function() {
    historyPage.changeTable('recent');
    
    $('#eventSelector').on("change", function() {
        historyPage.changeTable(historyPage.currentTable, $(this).val(), 1);
    })
});

var historyPage = {
    changingTable: false,
    scrollbar: false,
    currentTable: '',
    currentEvent: 0,
    currentPage: 1,
    
    changeTable: function(table, event, page) {
        if(this.changingTable) return;
        
        if(!event) event = this.currentEvent;
        if(!page) page = this.currentPage;
        
        if(table == this.currentTable && event == this.currentEvent && page == this.currentPage) return;
        
        //Begin Changing
        
        //If there's not a scrollbar, make sure one doesn't appear
        if($(window).height() >= $("body").get(0).scrollHeight) {
            this.scrollbar = true;
            $("body").css("overflow-y", "hidden");
        }
        
        this.changingTable = true;
        
        var tablePositions = new Array;
        tablePositions['recent'] = 0;
        tablePositions['popular'] = 1;
        tablePositions['popartist'] = 2;
        tablePositions['popuser'] = 3;
                
        var direction;
        if(tablePositions[table] > tablePositions[this.currentTable]) {
            direction = 1;
        } else {
            direction = 0;
        }
        
        $('#history-navigation a').removeClass('selected');
        $('#button-' + table).addClass('selected');
        
        data = [table, event, page];
        data = data.join('/');
        $.get(
            "/history/table/" + data,
            function(data) {
                historyPage.createTable(data, direction);
           }
        );
        
        this.currentTable = table;
        this.currentEvent = event;
        this.currentPage = page
        
        return false;
    },
    
    createTable: function(tableData, direction) {
        var currentTable = $('#history-container .table-container');
        var currentOffsets = currentTable.offset();
        var currentTableHeight = currentTable.outerHeight();
        
        var windowWidth = $(window).width();
        
        var position1 = windowWidth + 20;
        var position2 = -1*currentOffsets.left - 2000 - 20;
        if(direction) {
            var initialPosition = position1;
            var destinationPosition = position2;
        } else {
            var initialPosition = position2;
            var destinationPosition = position1;
        }
        
        var newTable = document.createElement('div');
        newTable = $(newTable).html(tableData).addClass('table-container');
        newTable = newTable.css('position', 'relative').css('top', -currentTableHeight + 'px').css('left', initialPosition + 'px');
        $('#history-container').append(newTable);
        
        finished = 0;
        
        this.moveTable(newTable, 0, currentTable);
        
        currentTable.css('position', 'relative').css('top', 0);
        this.moveTable(currentTable, destinationPosition, currentTable);
    },
    
    moveTable: function(table, Xpos, tableToRemove) {
        $(table).animate({
            position: 'relative',
            left: Xpos + 'px'
        }, {
            queue: false,
            duration: 1000,
            complete: function() {
                table.css('top', '0');
                historyPage.cleanUp(tableToRemove);
            }
        });
    },
    
    cleanUp: function(tableToRemove) {
        if(++finished > 1) {
            tableToRemove.remove();
            this.changingTable = false;
        }
        if(this.scrollbar) {
            $("body").css("overflow-y", "auto");
            this.scrollbar = false;
        }
        return;
    }
}