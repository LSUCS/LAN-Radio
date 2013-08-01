<?php

class Helper_Toolbox_Add extends CoreHelper {
    public function run() {
        $V = new CoreValidate($_POST);
        $db = Core::get('DB');
        
        $V->val('newEventName', 'string', true, 'Invalid Event Name', array('minlength'=>3, 'maxlength'=>20));
        
        $E = $V->getErrors();
        if($E) {
            var_dump($E);
            die;
            echo json_encode(array('errors'=>$E));
            exit;
        }
        
        $db->query("SELECT * FROM site_events WHERE Name = ?", array($_POST['newEventName']));
        if($db->record_count()) {
            echo json_encode(array('errors'=>array('An event with this name already exists')));
            exit;
        }
        
        $db->query("INSERT INTO site_events (Name) VALUES (?)", array($_POST['newEventName']));
    }
}