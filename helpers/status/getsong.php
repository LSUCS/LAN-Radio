<?php

class Helper_Status_Getsong extends CoreHelper {    
    public function run() {
        Core::get('DB')->query("SELECT 
                        * FROM songlist
                    LIMIT 1");
        if(!Core::get('DB')->record_count()) die('empty');
        
        list($ID, $Score, $addedBy, $DateAdded) = Core::get('DB')->next_record(MYSQLI_NUM);
        echo $ID;
        
        //Core::get('DB')->query("INSERT INTO history (trackid, votes, addedBy, datePlayed, dateAdded) 
        //                VALUES ('" . $ID . "', '" . $Score . "', '" . $addedBy . "', '" . sqltime() . "', '" . $DateAdded . "')");
    
        //Core::get('DB')->query("DELETE FROM votes WHERE trackid = '" . $ID . "'");
        //Core::get('DB')->query("DELETE FROM track_info WHERE trackid = '" . $ID . "'");
        //Core::get('DB')->query("DELETE FROM voting_list WHERE trackid = '" . $ID . "'");
    
    }
}