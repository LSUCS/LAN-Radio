<?php

class Helper_Toolbox_Start extends CoreHelper {
    public function run() {
        $V = new CoreValidate($_POST);
        $db = Core::get('DB');
        
        $db->query("SELECT ID FROM site_events");
        $events = $db->collect('ID');
        
        $V->val('eventID', 'inarray', true, 'Invalid Event', array('inarray'=>$events));
        $V->val('startEvent', 'checkbox', false, 'Invalid action');
        $V->val('endEvent', 'checkbox', false, 'Invalid action');
        
        $E = $V->getErrors();
        
        //Neither are set, or both are set
        if((isset($_POST['startEvent']) && isset($_POST['endEvent'])) || (!isset($_POST['startEvent']) && !isset($_POST['endEvent']))) {
            $E[] = "Invalid action";
        }
        
        if($E) {
            echo json_encode(array('errors'=>$E));
            exit;
        }
        
        $currentEvent = CoreSettings::get('currentEvent');
        
        //We're trying to start an event
        if(isset($_POST['startEvent'])) {
            if($currentEvent == $_POST['eventID']) {
                $E[] = "This event is already running";
            } elseif($currentEvent !== 0) {
                $E[] = "There is already an event running. Please end it first.";
            } else {
                $newEvent = $_POST['eventID'];
            }
        } 
        //We're trying to end an event
        else {
            if($currentEvent !== $_POST['event']) {
                $E[] = "This event is not running";
            } else {
                $newEvent = 0;
            }
        }
        
        
        $db->query("UPDATE site_config SET currentEvent = ?", array($newEvent));
    }
}