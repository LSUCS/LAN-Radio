<?php

class Controller_Index extends CoreController {
    public function action_index() {
        $this->showView('songs');
    }
}