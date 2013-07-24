<?php

class Helper_Status_Update extends CoreHelper {    
    public function run() {
        if(!isset($_GET['songid']) || !isset($_GET['position'])
            || !Core::validID($_GET['songid']) || !Core::isNumber($_GET['position'])) Core::niceError(404);
        
        Core::get('DB')->update("UPDATE site_config SET currentTrackID = '" . $_GET['songid'] . "', currentTrackPosition = " . $_GET['position']);
        
    }
}