<?php

class Controller_Songs extends CoreController {
    public function action_index() {
        $this->useHelper('songs');
    }
    
    public function action_tableinfo() {
        $this->useHelper('tableinfo');
    }
    
    public function action_vote($args) {
        $this->useHelper('vote', $args);
    }
    
    public function action_votes() {
        $this->useHelper('votes');
    }
    
    public function action_trackinfo() {
        $this->useHelper('trackinfo');
    }
    
    public function action_add() {
        $this->useHelper('add');
    }
}