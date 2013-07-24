<?php

class Controller_Status extends CoreController {
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