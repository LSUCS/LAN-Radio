<?php

namespace Core\Helper\Songs;

use \Core as Core;
use Core\Core as C;

class Tableinfo extends Core\Helper {
    public function run() {
        C::get('DB')->query("SELECT *, @rownum:=@rownum+1 as row_position FROM (
                                    SELECT * FROM songlist
                                ) user_rank,(SELECT @rownum:=0) r");
        
        $Info = C::get('DB')->to_array('trackid');
        
        echo json_encode($Info);
    }
}