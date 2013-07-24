var websocks = true;

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
    },
    
    onmessage: function(msg) {
        data = JSON.parse(msg.data);
        console.log(data);
        
        if(data.type == "query") {
            if(data.query == "mode") {
                this.send(JSON.stringify({"mode":"listen"}))
            }
        } else if(data.type == "event") {
            if(data.event == "add") alert('add');
            else if(data.event == "delete") alert('delete');
            else if(data.event == "vote") alert('vote');
            else if(data.event == "next") alert('next');
        }
    },
    
    onclose: function() {
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