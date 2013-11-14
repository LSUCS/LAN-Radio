<?php

class Controller_Mpd extends CoreController {
    public function action_search() {
        $this->useHelper('search');
    }
    public function action_playerinfo() {
        $this->useHelper('playerinfo');
    }
    public function action_command($args) {
        Core::get('Core')->enforceAdmin();
        $this->useHelper('command', $args);
    }
}