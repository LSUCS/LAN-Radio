<?php

namespace Core\Controller;

use Core;

class Toolbox extends Core\Controller {
    const REQUIRE_ADMIN = true;
    
    public function action_index() {
        $this->showView('toolbox');
    }
    
    public function action_controls() {
        $this->showView('controls');
    }
    
    //Helpers
    public function action_start() {
        $this->useHelper('start');
    }
    
    public function action_add() {
        $this->useHelper('add');
    }
    
    public function action_change() {
        $this->useHelper('change');
    }
    
    public function action_global() {
        $this->useHelper('globals');
    }
}    