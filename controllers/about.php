<?php

namespace Core\Controller;

use Core;

class About extends Core\Controller {
    public function action_index() {
        $this->showView('main');
    }
}