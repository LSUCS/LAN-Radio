/* So I learnt how to do JS objects/classes... */
var ws = {
    url: "ws://lan-radio",
    port: 1521,
    service: "radio",
    sock: null,
    
    connect: function() {
        console.log("Connecting to: " + this.url + ":" + this.port + "/" + this.service);
        
        this.sock = new WebSocket(this.url + ":" + this.port + "/" + this.service);
        this.sock.onopen = this.onopen;
        this.sock.onmessage = this.onmessage;
        this.sock.onclose = this.onclose;
    },
    
    onopen: function() {
        console.log("connected");
        radioJS.websocks = true;
    },
    
    onmessage: function(msg) {
        data = JSON.parse(msg.data);
        console.log(data);
        
        if(data.type == "query") {
            if(data.query == "mode") {
                this.send(JSON.stringify({"mode":"listen"}))
            }
        } else if(data.type == "event") {
            eventData = data.event;
            console.log("EVENT: " + eventData.event);
            if(eventData.event == "add") ws.onadd(eventData.song);
            else if(eventData.event == "delete") ws.ondelete(eventData.song);
            else if(eventData.event == "vote") ws.onvote(eventData.song);
            else if(eventData.event == "next") ws.onnext(eventData.song);
        }
    },
    
    onadd: function(d) {
        var parity = (d.position % 2 == 0) ? 'even' : 'odd';
        
        var row = $("<tr id=\"row-" + d.trackid + "\" class=\"" + parity + "\">\
                    <td class=\"col0\">" + d.position + "</td>\
                    <td class=\"col1\"><img class=\"source-icon\" src=\"/static/images/" + d.source + "-icon.png\" alt=\"" + d.source + "\"/></td>\
                    <td class=\"col2\">" + d.Title + "</td>\
                    <td class=\"col3\">" + d.Artist + "</td>\
                    <td class=\"col4\">" + d.Duration + "</td>\
                    <td class=\"col5\">" + d.Album + "</td>\
                    <td class=\"col6 votebox\">\
                        <a href=\"#\" >\
                            <button class=\"voteup votebtn\"></button>\
                        </a>\
                        <span class=\"score\">" + d.Score + "</span>\
                        <a href=\"#\">\
                            <button class=\"votedown votebtn\"></button>\
                        </a>\
                    </td>\
                </tr>");
        
        radioJS.addRow(row, d.position);
    },
    
    ondelete: function(d) {
        radioJS.removeRow($('#row-'+escapeID(d.ID)));
    },
    
    onvote: function(d) {
        var rowID = "row-" + escapeID(d.ID);
        
        var t = radioJS.getPosition(rowID) - d.position;

        $('#' + rowID + ' .score').html(d.score);
        radioJS.moveRow($('#'+rowID), radioJS.getPosition(rowID) - d.position, true);
    },
    
    onnext: function(d) {
        radioJS.removeRow($($('#voting-table tr')[0]));
    },
    
    onclose: function() {
        radioJS.websocks = false;
        console.log("closed");
    },
    
    send: function(msg) {
        this.sock.send(msg);
    }
}

$(function() {
    console.log("connecting to socket server");
    ws.connect();
});