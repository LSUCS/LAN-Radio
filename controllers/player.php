<?php

namespace Core\Controller;

use Core;

class Player extends Core\Controller {
    public function action_index() {
        $this->showView('player');
    }
}