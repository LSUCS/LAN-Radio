<?php

namespace Core\Helper\Songs;

use \Core as Core;
use Core\Core as C;
use Core\Cache;
use Core\Utility;
use Core\Config;
use Core\Session;

class Add extends Core\Helper {
    public function run() {
        $db = C::get('DB');
        
        $bannedUsers = Cache::get('add-banned');
        if(!$bannedUsers) {
            $db->query("SELECT * FROM banned_users");
            $bannedUsers = $db->to_array('UserID', MYSQLI_ASSOC);
            Cache::set('add-banned', $bannedUsers);
        } 
        if(array_key_exists($bannedUsers, $this->parent->LoggedUser->ID)) { 
            die("banned");
        }
        
        //Check everything is well in Smallville
        $trackID = Utility::unEscapeID($_GET['track']);
        
        $db->query("SELECT * FROM voting_list WHERE trackid = ?", $trackID);
        if($db->record_count()) die('exists');
        
        //Check if the user has been adding too much.
        $db->query("SELECT addedDate FROM voting_list WHERE addedBy = ? AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(addedDate) < " . Config::ADD_TIME . " ORDER BY addedBy ASC", Session::getUser()->ID);
        if($db->record_count() > Config::ADD_MAX) {
            list($voteTime) = $db->next_record(MYSQLI_NUM);
            $timeToNextVote = time() - strtotime($voteTime) - Config::ADD_TIME;
            die('addmax - ' . $timeToNextVote);
        }
        
        $db->query("SELECT * FROM track_info WHERE trackid = ?", $trackID);
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
                
                switch(Utility::getSource($trackID)) {
                    case 'spotify':
                    
                        $data = $this->lookupJSON('http://ws.spotify.com/lookup/1/.json?uri=' . $trackID);
                        
                        $Track = array(
                            'Title' => $data->track->name,
                            'Artist' => $data->track->artists[0]->name,
                            'Album' => $data->track->album->name,
                            'Time' => $data->track->length
                        );
                        break;
                    case 'soundcloud':
                        $soundcloudID = substr($trackID, strpos($trackID, ';') +1 );
                        $data = $this->lookupJSON('http://api.soundcloud.com/tracks/' . $soundcloudID . '.json?client_id=' . Config::SOUNDCLOUD_CLIENT_ID);
                        
                        $Track = array(
                            'Title' => $data->title,
                            'Artist' => $data->user->username,
                            'Album' => 'n/a',
                            'Time' => $data->duration/1000
                        );
                        break;
                        
                    case 'youtube':
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
                        break;
                    
                    default:
                        die('invalid source');
                
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
            $db->query("INSERT INTO track_info (trackid, Title, Artist, Album, Duration) VALUES(?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE Title = VALUES(Title), Artist = VALUES(Artist), Album = VALUES(Album), Duration = VALUES(Duration)",
                $trackID, $Track['Title'], $Track['Artist'], $Track['Album'], $Track['Time']);
        }
        
        //Add it to the voting list
        $db->query("INSERT INTO voting_list (trackid, addedBy, addedDate) VALUES (?, ?, NOW())", $trackID, Session::getUser()->ID);
        
        //Add a vote
        $db->query("INSERT INTO votes (trackid, userid, updown, time) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE updown = 1", $trackID, Session::getUser()->ID);
        
        //Find out the new position in the big table.
        $db->query("SELECT *, @rownum:=@rownum+1 as row_position FROM
                                    ( SELECT * FROM songlist ) position, (SELECT @rownum:=0) r");

        $NewRows = $db->to_array('trackid');
        
        $Track = $NewRows[$trackID];
        $Track["position"] = $Track["row_position"];
        $Track["source"] = Utility::getSource($trackID);
        $Track["Duration"] = Utility::get_time($Track["Duration"]);

        $msgData = array('type'=>'event', 'event'=>'add', 'data'=>$Track);
        
        try {
            C::loadLibrary('phpws/phpws/websocket.client.php');
            $msg = \WebSocketMessage::create(json_encode($msgData));
            
            $socket = new \WebSocket("ws://" . Config::WEBSOCKET_HOST . ":" . Config::WEBSOCKET_PORT . "/" . Config::WEBSOCKET_SERVICE);
            $socket->open();
            $socket->setAdmin();
            $socket->sendMessage($msg);
            //$socket->close();
        } catch(Exception $e) {
            var_dump($e);
            die;
        }   

    }
    function lookupJSON($URL, $Decode=true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        
        //Track doesn't exist. C'est IMPOSSIBLÉ!!
        if(!$data) die('invalid');
        
        if($Decode) $data = json_decode($data);
        
        return $data;
    }
}
