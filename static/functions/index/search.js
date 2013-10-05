$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('body').fileupload({
        url: '/upload?auth=' + $('#userauth').html(),
        dropZone: $('#dropzone')
    });
    
    $('#search-results').resizable();
});

var search = {
    
    updateSearch: function() {
        var query = $('#searchinput').val();
        if(typeof query == 'undefined' || query.length == 0) {
            this.hideSearch();
            return;
        }
        
        var libraries = []
        $('#library-buttons input').each(function(index) {
            if($(this).is(":checked")) libraries.push($(this).attr('id').substring($(this).attr('id').indexOf('-') + 1));
        });
        
        //var spotifyAPI = "http://ws.spotify.com/search/1/track.json";
        var API = "/mpd/search";
        $.ajax({
            "type": "GET",
            "url": API,
            "data": {'search': query, 'libraries': libraries.join('|')},
            "dataType": "json",
            "success": function(data) {
                if(typeof data === 'object') {
                    console.log(data);

                    search.showTracks(search.filterSongs(data));
                }
           }
        });
    },
    
    filterSongs: function(data) {
        for(d in data) {
            if(data[d].Time == "0") delete data[d];
        }
        return data;
    },
    
    getLibrary: function(file) {
        if(file.indexOf('spotify') !== -1) return 'spotify';
        if(file.indexOf('gdata.youtube.com') !== -1) return 'youtube';
        return local;
    },
    
    showTracks: function(data) {
        var html = "<table id='search-results-table'><thead><tr><th class='icon'></th><th>Track Name</th><th>Artist/Uploader</th><th></th><th>Album</th></tr></thead><tbody>";
        //var limit = (data.length > 20) ? 20 : data.length;
        var limit = data.length;
        var row = 'even';
        var current = 0;
        for(t in data) {
            row = (row === 'even') ? 'odd' : 'even';
            html += "<tr id='" + escapeID(data[t].file) + "' class='row" + row + "'><td>";
            html += "<img class='search-library-icon' src='/static/images/" + this.getLibrary(data[t].file) + "-icon.png' />";
            html += "</td><td>" + data[t].Title + "</td><td>" + data[t].Artist + "</td><td>";
            html += formatTime(data[t].Time) + "</td><td>" + data[t].Album + "</td></tr>";
            if(++current > limit) break;
        }
        html += "</tbody></table>";
        //html = this.addTableEvents($(html));
    
        $('#search-results').html(html);
        this.showSearch();
        var dt = $('#search-results-table').dataTable({
            "bFilter": true
        });
        this.addTableEvents(dt);
    },
    
    addTableEvents: function(dt) {
        $(dt.fnGetNodes()).on('dblclick', function() {
            this.addSong($(this).attr('id'));
            $(this).off('dblclick');
        }).on('click', function() {
            $(this).addClass("selected").siblings().removeClass("selected");
        }).on('mouseover', function() {
        });
    },
    /*
    addClickButton: function(id) {
        //For some reason, jQuery keeps messing up here, so resort back to normal JS
        $('.clickToAdd').remove();
        document.getElementById(id).firstChild.innerHTML += "<input type='button' class='clickToAdd' onclick='alert(addSong(\'" + id + "\'););'/>";
    },
    */
    hideSearch: function() {
        console.log('hiding');
        $('html').off('click');
        $('#search').slideUp();
    },
                   
    showSearch: function() {
        if($('#search').is(':visible')) return;
        $('html').on('click',function(event) {
            if(!$(event.target).closest('#search').length && !$(event.target).closest('#searchbox').length) {
                search.hideSearch();
            }
        });
        
        $('#search').slideDown();
    },
    
    addSong: function(songid) {
        console.log(songid);
        $.ajax({
            type: "GET",
            url: "/songs/add",
            data: {
                track: songid
            },
            success: function(data){
                
            }
        })
    }
}