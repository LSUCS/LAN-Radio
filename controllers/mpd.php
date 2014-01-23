<?php

namespace Core\Controller;

use Core;
use Core\Core as C;
use Core\Session;

class Mpd extends Core\Controller {
    public function action_search() {
        $this->useHelper('search');
    }
    public function action_playerinfo() {
        $this->useHelper('playerinfo');
    }
    public function action_command($args) {
        Session::enforceAdmin();
        $this->useHelper('command', $args);
    }
}