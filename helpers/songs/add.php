<?php

class Helper_Songs_Add extends CoreHelper {    
    public function run() {
        //Check everything is well in Smallville
        $trackID = Core::unEscapeID($_GET['track']);

        Core::get('DB')->query("SELECT * FROM voting_list WHERE trackid = ?", array($trackID));
        if(Core::get('DB')->record_count()) die('exists');
        
        Core::get('DB')->query("SELECT * FROM track_info WHERE trackid = ?", array($trackID));
        if(!Core::get('DB')->record_count()) {
            
            //Get info on the track and add it to the database
            if(strstr($trackID, 'spotify')) {
                
                $data = $this->lookupJSON('http://ws.spotify.com/lookup/1/.json?uri=' . $trackID);
                
                $Track = array(
                    'Title' => $data->track->name,
                    'Artist' => $data->track->artists[0]->name,
                    'Album' => $data->track->album->name,
                    'Time' => $data->track->length
                );
            } else {

                $data = $this->lookupJSON($trackID . '?alt=json');
                
                //Youtube API is annoying
                $t = '$t';
                $group = 'media$group';
                $duration = 'yt$duration';
                
                $Track = array(
                    'Title' => $data->entry->title->$t,
                    'Artist' => $data->entry->author[0]->name->$t,
                    'Album' => 'n/a',
                    'Time' => $data->entry->$group->$duration->seconds
                );
            }
            
            //Add info to the track catalogue
            Core::get('DB')->query("INSERT IGNORE INTO track_info (trackid, Title, Artist, Album, Duration) VALUES(?, ?, ?, ?, ?)",
                array($trackID, $Track['Title'], $Track['Artist'], $Track['Album'], $Track['Time']));
        }
        
        //Add it to the voting list
        Core::get('DB')->query("INSERT INTO voting_list (trackid, addedBy, addedDate) VALUES (?, ?, NOW())", array($trackID, $this->parent->LoggedUser->ID));
        
        //Add a vote
        Core::get('DB')->query("INSERT INTO votes (trackid, userid, updown) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE updown = 1", array($trackID, $this->parent->LoggedUser->ID));
        
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        //Find out the new position in the big table.
        Core::get('DB')->query("SELECT *, @rownum:=@rownum+1 as row_position FROM
                                    ( SELECT * FROM songlist ) position, (SELECT @rownum:=0) r");

        $NewRows = Core::get("DB")->to_array('trackid');
        
        $Track = $NewRows[$trackID];
        $Track["position"] = $Track["row_position"];
        $Track["source"] = Core::getSource($trackID);
        $Track["Duration"] = Core::get_time($Track["Duration"]);

        $msgData = array('type'=>'event', 'event'=>'add', 'data'=>$Track);
        
        Core::requireLibrary("websocket.client", "phpws/phpws");
        $msg = WebSocketMessage::create(json_encode($msgData));

        $socket = new WebSocket("ws://" . WEBSOCKET_HOST . ":" . WEBSOCKET_PORT . "/" . WEBSOCKET_SERVICE);
        $socket->open();
        $socket->setAdmin();
        $socket->sendMessage($msg);
        //$socket->close();

    }
    function lookupJSON($URL, $Decode=true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        
        //$data = file_get_contents($URL);
        
        //Track doesn't exist. C'est IMPOSSIBLÉ!!
        if(!$data) die('invalid');
        
        if($Decode) $data = json_decode($data);
        
        return $data;
    }
}
