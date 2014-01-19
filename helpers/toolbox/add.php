<?php

namespace Core\Helper\Toolbox;

use \Core as Core;
use Core\Core as C;
use Core\Validate;

class Add extends Core\Helper {     
    public function run() {
        $V = new Validate($_POST);
        $db = C::get('DB');
        
        $V->val('newEventName', 'string', true, 'Invalid Event Name', array('minlength'=>3, 'maxlength'=>20));
        
        $E = $V->getErrors();
        if($E) {
            $this->error($E);
        }
        
        $db->query("SELECT * FROM site_events WHERE Name = ?", $_POST['newEventName']);
        if($db->record_count()) {
            $this->error('An event with this name already exists');
        }
        
        $db->query("INSERT INTO site_events (Name) VALUES (?)", $_POST['newEventName']);
    }
}