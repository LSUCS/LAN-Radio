<?php

class Helper_Songs_Vote extends CoreHelper {    
    public function run() {
        
        $direction = $this->arguments[0];
        
        $trackID = $_GET['id'];
        
        $Val = new CoreValidate(array('id'=>$trackID, 'dir'=>$direction));
        $Val->val('id', 'trackid', true, "Invalid or missing Track ID");
        $Val->val('dir', 'integer', true, "Invalid Direction", array('minsize'=>0, 'maxsize'=>1));
        
        if($Err = $Val->getErrors()) {
            foreach($Err as $e) echo $e . "\n";
            die;
        }
        
        $db = Core::get('DB');
        
        $db->query("SELECT * FROM voting_list WHERE trackid = ?", array($trackID));
        if(!$db->record_count()) {
            die('notrack');
        }
             
        $db->query("SELECT updown FROM votes WHERE trackid = ? AND userid = ?", array($trackID, $this->parent->LoggedUser->ID));
        if($db->record_count()) {
            list($vote) = $db->next_record(MYSQLI_NUM);
            if($vote == $direction) die('identical');
            
            $db->query("UPDATE votes SET updown = ? WHERE trackid = ? AND userid = ?", array($direction, $trackID, $this->parent->LoggedUser->ID));
        } else {
            if(!$direction) {
                //Check if the user has been down voting too much.
                $db->query("SELECT time FROM votes WHERE updown = 0 AND userid = ? AND time > UNIX_TIMESTAMP()-" . VOTE_TIME . " ORDER BY time ASC", array($this->parent->LoggedUser->ID));
                if($db->record_count() > VOTE_MAX) {
                    list($voteTime) = $db->next_record(MYSQLI_NUM);
                    $timeToNextVote = time() - $voteTime - VOTE_TIME;
                    die('votemax - ' . $timeToNextVote);
                }
            }
            $db->query("INSERT INTO votes (trackid, userid, updown, time) VALUES (?, ?, ?, UNIX_TIMESTAMP())", array($trackID, $this->parent->LoggedUser->ID, $direction));
        }
        
        //Find out the new position in the big table.
        $db->query("SELECT *, @rownum:=@rownum+1 as row_position FROM (
                                    SELECT * FROM songlist
                                ) user_rank,(SELECT @rownum:=0) r");
        $NewRows = $db->to_array('trackid');
        $RowInfo = $NewRows[$trackID];
        
        $Track = array("ID" => $trackID, "position" => $RowInfo["row_position"], "score" => $RowInfo["Score"]);
        $msgData = array('type'=>'event', 'event'=>'vote', 'data' => $Track);
        
        try{
            Core::requireLibrary("websocket.client", "phpws/phpws");
            $msg = WebSocketMessage::create(json_encode($msgData));
            
            $socket = new WebSocket("ws://" . WEBSOCKET_HOST . ":" . WEBSOCKET_PORT . "/" . WEBSOCKET_SERVICE);
            $socket->open();
            $socket->setAdmin();
            $socket->sendMessage($msg);
            //$socket->close();
        } catch(Exception $e) {
            var_dump($e);
            die;
        }            
        //Return the score, as it may have changed in the mean time, and we want to be as accurate as possible and just cos.
        echo $RowInfo['Score'] . '!!' . $RowInfo['row_position'];
    }
}
