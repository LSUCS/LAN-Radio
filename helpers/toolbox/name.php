<?php

namespace Core\Helper\Toolbox;

use \Core as Core;
use Core\Core as C;
use Core\Validate;

class Name extends Core\Helper {     
    public function run() {
        $V = new Validate($_POST);
        $db = C::get('DB');
        
        $db->query("SELECT ID FROM site_events");
        $events = $db->collect('ID');
        
        $V->val('eventID', 'inarray', true, 'Invalid Event', array('inarray'=>$events));
        
        $E = $V->getErrors();
        if($E) {
            $this->error($E);
        }
        
        $db->query("UPDATE site_config SET currentEvent = ?", $_POST['eventID']);
    }
}