<?php

namespace Core\Helper\Status;

use \Core as Core;
use Core\Core as C;

class Status extends Core\Helper {
    public function run() {
        C::get('DB')->query("SELECT running FROM site_config");
        list($status) = C::get('DB')->next_record();
        echo ($status) ? 'running' : 'false';
    }
}