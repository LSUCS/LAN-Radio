<?php

class Helper_Status_Getsong extends CoreHelper {    
    public function run() {
        Core::get('DB')->query("SELECT 
                        trackid, Score, addedBy, addedDate FROM songlist
                    LIMIT 1");
        if(!Core::get('DB')->record_count()) die('empty');
        
        list($ID, $Score, $addedBy, $DateAdded) = Core::get('DB')->next_record(MYSQLI_NUM);
        echo $ID;
        
        Core::get('DB')->query("INSERT INTO history (trackid, votes, addedBy, datePlayed, dateAdded) 
                        VALUES ('%s', '%s', '%s', NOW(), '%s')", 
                        array($ID,$Score,$addedBy,$DateAdded));
    
        Core::get('DB')->query("DELETE FROM votes WHERE trackid = '%s'", $ID);
        //Core::get('DB')->query("DELETE FROM track_info WHERE trackid = '%s'", $ID);
        Core::get('DB')->query("DELETE FROM voting_list WHERE trackid = '%s'", $ID);
        
        
        $Track = array("ID" => $ID);
        $msgData = array('type'=>'event', 'event'=>'delete', 'data' => $Track);
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
        
    
    }
}