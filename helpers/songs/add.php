<?php

class Helper_Songs_Add extends CoreHelper {    
    public function run() {
        $C = Core::get('Cache');
        $db = Core::get('DB');
        
        $bannedUsers = $C->get('add-banned');
        if(!$bannedUsers) {
            $db->query("SELECT * FROM banned_users");
            $bannedUsers = $db->to_array('UserID', MYSQLI_ASSOC);
            $C->set('add-banned', $bannedUsers);
        } 
        if(array_key_exists($bannedUsers, $this->parent->LoggedUser->ID)) { 
            die("banned");
        }
        
        //Check everything is well in Smallville
        $trackID = Core::unEscapeID($_GET['track']);
        
        $db->query("SELECT * FROM voting_list WHERE trackid = ?", array($trackID));
        if($db->record_count()) die('exists');
        
        //Check if the user has been adding too much.
        $db->query("SELECT addedDate FROM voting_list WHERE addedBy = ? AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(addedDate) < " . ADD_TIME . " ORDER BY addedBy ASC", array($this->parent->LoggedUser->ID));
        if($db->record_count() > ADD_MAX) {
            list($voteTime) = $db->next_record(MYSQLI_NUM);
            $timeToNextVote = time() - strtotime($voteTime) - ADD_TIME;
            die('addmax - ' . $timeToNextVote);
        }
        
        $db->query("SELECT * FROM track_info WHERE trackid = ?", array($trackID));
        $needLookup = false;
        if($db->record_count()) {
            $TrackInfo = $db->next_record(MYSQLI_ASSOC);
            if(empty($TrackInfo["Artist"]) || empty($TrackInfo["Title"])) {
                $needLookup = true;
            }
        } else {
            $needLookup = true;
        }
        
        if($needLookup) {
            $flag = 0;
            for($try = 1; $try <= 3; $try++) {
                //Get info on the track and add it to the database
                if(Core::getSource($trackID) == "spotify") {
                    
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
                if(!empty($Track['Title']) && !empty($Track['Artist'])) {
                    $flag = 1;
                    break;
                }
            }
            if($flag == 0) {
                $this->error("Sorry, the information for this track could not be found at this time");
            }
            
            //Add info to the track catalogue
            $db->query("INSERT INTO track_info (trackid, Title, Artist, Album, Duration) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE",
                array($trackID, $Track['Title'], $Track['Artist'], $Track['Album'], $Track['Time']));
        }
        
        //Add it to the voting list
        $db->query("INSERT INTO voting_list (trackid, addedBy, addedDate) VALUES (?, ?, NOW())", array($trackID, $this->parent->LoggedUser->ID));
        
        //Add a vote
        $db->query("INSERT INTO votes (trackid, userid, updown) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE updown = 1", array($trackID, $this->parent->LoggedUser->ID));
        
        //Find out the new position in the big table.
        $db->query("SELECT *, @rownum:=@rownum+1 as row_position FROM
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
