<?php

namespace Core\Controller;

use Core;

class Status extends Core\Controller {
    const ENFORCE_LOGIN = false;
    
    public function action_status() {
        $this->useHelper('status');
    }
    public function action_getsong() {
        $this->useHelper('getsong');
    }
    public function action_update() {
        $this->useHelper('update');
    }
}