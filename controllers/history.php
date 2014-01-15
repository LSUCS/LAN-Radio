<?php

class Controller_History extends CoreController {    
    public function action_index() {
        $this->showView('history');
    }
    
    public function action_table($pieces) {
        $this->useHelper('showtable', $pieces);
    }
}