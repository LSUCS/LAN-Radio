<?php

class Controller_Toolbox extends CoreController {
    public function action_index() {
        $this->showView('toolbox');
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
        $this->useHelper('global');
    }
}