<?php

namespace Core\Helper\Status;

use \Core as Core;
use Core\Core as C;
use Core\Utiltiy;

class Update extends Core\Helper {     
    public function run() {
        if(!isset($_GET['songid']) || !isset($_GET['position'])
            || !Utility::validID($_GET['songid']) || !Utiltiy::isNumber($_GET['position'])) C::niceError(404);
        
        C::get('DB')->update("UPDATE site_config SET currentTrackID = ?, currentTrackPosition = ?", $_GET['songid'], $_GET['position']);
        
    }
}