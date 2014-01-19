<?php

namespace Core\Controller;

use Core;

class History extends Core\Controller {  
    public function action_index() {
        $this->showView('history');
    }
    
    public function action_table($pieces) {
        $this->useHelper('showtable', $pieces);
    }
}