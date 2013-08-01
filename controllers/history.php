<?php

class Controller_History extends CoreController {
    public function __routing($pieces) {
        if (!count($pieces)) {
            $this->action_index();
        } else {
            $action = array_shift($pieces);
            $thing = array_shift($pieces);
            switch ($action) {
                case 'details':
                    if (!$thing || !is_numeric($thing)) {
                        $this->action_index();
                    } else {
                        $this->action_details($thing);
                    }
                    break;
                case 'rate':
                    $this->action_rate();
                    break;
                case 'torrentinfo':
                    $this->action_torrentinfo();
                    break;
                case 'addtags':
                    $this->action_addtags();
                    break;
                case 'thanks':
                    $this->action_thanks();
                    break;
                default:
                    $this->action_index();
            }
        }
    }
    
    public function action_index() {
        $this->showView('history');
    }
}