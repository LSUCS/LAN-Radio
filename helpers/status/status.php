<?php

class Helper_Status_Status extends CoreHelper {    
    public function run() {
        Core::get('DB')->query("SELECT running FROM site_config");
        list($status) = Core::get('DB')->next_record();
        echo ($status) ? 'running' : 'false';
    }
}