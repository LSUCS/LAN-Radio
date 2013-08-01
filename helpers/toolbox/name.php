<?php

class Helper_Toolbox_Name extends CoreHelper {
    public function run() {
        $V = new CoreValidate($_POST);
        $db = Core::get('DB');
        
        $db->query("SELECT ID FROM site_events");
        $events = $db->collect('ID');
        
        $V->val('eventID', 'inarray', true, 'Invalid Event', array('inarray'=>$events));
        
        $E = $V->getErrors();
        if($E) {
            echo json_encode(array('errors'=>$E));
            exit;
        }
        
        
        
        $db->query("UPDATE site_config SET currentEvent = ?", array($_POST['eventID']));
    }
}