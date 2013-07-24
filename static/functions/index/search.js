$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('body').fileupload({
        url: '/upload?auth=' + $('#userauth').html(),
        dropZone: $('#dropzone')
    });
    
    $('#search-results').resizable();
});

function updateSearch() {
    var search = $('#searchinput').val();
    if(typeof search == 'undefined' || search.length === 0) {
        hideSearch();
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
        "data": {'search': search, 'libraries': libraries.join('|')},
        "dataType": "json",
        "success": function(data) {
            if(typeof data === 'object') {
                console.log(data);
                //showTracks(filterGB(data));
                showTracks(filterSongs(data));
            }
       }
    });
}

function filterSongs(data) {
    for(d in data) {
        if(data[d].Time == "0") delete data[d];
    }
    return data;
}

function showTracks(data) {
    var html = "<table id='search-results-table'><thead><tr><th>Track Name</th><th>Artist/Uploader</th><th></th><th>Album</th></tr></thead><tbody>";
    var limit = (data.length > 20) ? 20 : data.length;
    var row = 'even';
    var current = 0;
    for(t in data) {
        row = (row === 'even') ? 'odd' : 'even';
        html += "<tr id='" + escapeID(data[t].file) + "' class='row" + row + "'><td>" + data[t].Title + "</td><td>" + data[t].Artist + "</td><td>";
        html += formatTime(data[t].Time) + "</td><td>" + data[t].Album + "</td></tr>";
        if(current++ > limit) break;
    }
    html += "</tbody></table>";
    $(html);

    $('#search-results').html(html);
    showSearch();
    $('#search-results-table').dataTable({
        "bFilter": true
    });
    addTableEvents();
}

function addTableEvents() {
    $('#search-results-table tbody tr').on('dblclick', function() {
        addSong($(this).attr('id'));
        $(this).off('dblclick');
    }).on('click', function() {
        //addClickButton($(this).attr('id'));
        $(this).addClass("selected").siblings().removeClass("selected");
    }).on('mouseover', function() {
        //addClickButton($(this).attr('id'));
    });
    
}
function addClickButton(id) {
    //For some reason, jQuery keeps messing up here, so resort back to normal JS
    $('.clickToAdd').remove();
    document.getElementById(id).firstChild.innerHTML += "<input type='button' class='clickToAdd' onclick='alert(addSong(\'" + id + "\'););'/>";
}

function hideSearch() {
    $('html').off('click');
    $('#search').slideUp();
}
               
function showSearch() {       
    $('html').on('click',function(event) {
        if(!$(event.target).closest('#search').length) {
            hideSearch();
        }
    });
    
    $('#search').slideDown();
}

function addSong(songid) {
    console.log(songid);
    $.ajax({
        type: "GET",
        url: "/songs/add",
        data: {
            track: songid
        },
        success: function(data){
            alert('track added');
        }
    })
}