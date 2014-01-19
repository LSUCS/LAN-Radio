<?php

namespace Core\Controller;

use Core;
use Core\Core as C;

class Mpd extends Core\Controller {
    public function action_search() {
        $this->useHelper('search');
    }
    public function action_playerinfo() {
        $this->useHelper('playerinfo');
    }
    public function action_command($args) {
        C::get('Core')->enforceAdmin();
        $this->useHelper('command', $args);
    }
}