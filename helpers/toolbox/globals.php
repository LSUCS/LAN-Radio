<?php

namespace Core\Helper\Toolbox;

use \Core as Core;
use Core\Core as C;

class Globals extends Core\Helper {     
    public function run() {
        $s = array();
        $s[] = (isset($_POST['spotify'])) ? 1 : 0;
        $s[] = (isset($_POST['youtube'])) ? 1 : 0;
        $s[] = (isset($_POST['local'])) ? 1 : 0;
        $s[] = (isset($_POST['upload'])) ? 1 : 0;
        
        C::get('DB')->query("UPDATE site_config SET spotify = ?, youtube = ?, local = ?, upload = ?", $s);

    }
}