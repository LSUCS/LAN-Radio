<?php

class Helper_Songs_Tableinfo extends CoreHelper {    
    public function run() {
        Core::get('DB')->query("SELECT *, @rownum:=@rownum+1 as row_position FROM (
                                    SELECT * FROM songlist
                                ) user_rank,(SELECT @rownum:=0) r");
        
        $Info = Core::get('DB')->to_array('trackid');
        
        echo json_encode($Info);
    }
}