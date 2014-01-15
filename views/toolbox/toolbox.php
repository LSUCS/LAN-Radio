<?php
class View_Toolbox_Toolbox extends CorePage{    
	public function render(){
        if(!$_SESSION['logged_user']->isAdmin) Core::niceError(403);
		
        $template = Core::get('Template');
        $db = Core::get('DB');
        
        $this->showHeader('Toolbox', "default", false);
		$template->init('main');
  
        $template->set('RUNNING', CoreSettings::get('running'), true);
        
        $template->set('SPOTIFY', CoreSettings::get('spotify'), true);
        $template->set('YOUTUBE', CoreSettings::get('youtube'), true);
        $template->set('LOCAL', CoreSettings::get('local'), true);
        $template->set('UPLOAD', CoreSettings::get('upload'), true);
        
        $db->query("SELECT ID, Name FROM site_events");
        $events = $db->to_array(false, MYSQLI_ASSOC);
        $template->set('EVENTS', $events);
        
        $currentEvent = CoreSettings::get('currentEvent');
        foreach($events as $key=>$e) {
            if($e["ID"] == $currentEvent) {
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