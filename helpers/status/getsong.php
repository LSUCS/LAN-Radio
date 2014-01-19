<?php

namespace Core\Helper\Status;

use \Core as Core;
use Core\Core as C;
use Core\Settings;

class Getsong extends Core\Helper {
    public function run() {
        C::get('DB')->query("SELECT 
                        trackid, Score, addedBy, addedDate FROM songlist
                    LIMIT 1");
        if(!C::get('DB')->record_count()) die('empty');
        
        list($ID, $Score, $addedBy, $DateAdded) = C::get('DB')->next_record(MYSQLI_NUM);
        echo $ID;
        
        C::get('DB')->query("INSERT INTO history (trackid, votes, addedBy, datePlayed, dateAdded, eventID) 
                        VALUES (?, ?, ?, NOW(), ?, ?)", 
                        $ID, $Score, $addedBy, $DateAdded, Settings::get('currentEvent'));
    
        C::get('DB')->query("DELETE FROM votes WHERE trackid = '%s'", $ID);
        C::get('DB')->query("DELETE FROM voting_list WHERE trackid = '%s'", $ID);
        
        
        $Track = array("ID" => $ID);
        $msgData = array('type'=>'event', 'event'=>'delete', 'data' => $Track);
        try{
            $msg = phpws_WebSocketMessage::create(json_encode($msgData));
            
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