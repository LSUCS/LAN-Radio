<?php
class View_Toolbox_Toolbox extends CorePage{    
	public function render(){
        if(!$_SESSION['logged_user']['moderator']) Core::niceError(403);
		
        $template = Core::get('Template');
        $db = Core::get('DB');
        
        $this->showHeader('Toolbox', "default", false);
		$template->init('main');
		
        $db->query("SELECT * FROM site_config");
        if($db->record_count() !== 1) die('Fatal Settings Error');
        $settings = $db->next_record(MYSQLI_ASSOC);
        $template->set('RUNNING', $settings['running'], true);
        
        $template->set('SPOTIFY', $settings['spotify'], true);
        $template->set('YOUTUBE', $settings['youtube'], true);
        $template->set('LOCAL', $settings['local'], true);
        $template->set('UPLOAD', $settings['upload'], true);
        
        $db->query("SELECT ID, Name FROM site_events");
        $events = $db->to_array(false, MYSQLI_ASSOC);
        $template->set('EVENTS', $events);
        
        foreach($events as $key=>$e) {
            if($e["ID"] = $settings['currentEvent']) {
                $events[$key]["START_ACTION"] = "disabled='disabled' value='Running'";
                $events[$key]["END_ACTION"] = "value='End'";
            } else {
                $events[$key]["START_ACTION"] = "value='Start'";
                $events[$key]["END_ACTION"] = "disabled='disabled' value='Not Running'";
            }
        }
        $template->set('EVENTS2', $events);
        
        $template->push();
		$this->showFooter();
	}
	
}

?>