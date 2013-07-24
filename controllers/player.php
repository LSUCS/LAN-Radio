<?php

class Controller_Player extends CoreController {
    public function action_index() {
        $this->showView('player');
    }
}