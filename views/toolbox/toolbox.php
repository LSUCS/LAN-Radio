<?php

namespace Core\View\Toolbox;

use \Core;
use Core\Core as C;
use Core\Settings;

class Toolbox extends \Core\Page { 
	public function render(){
        if(!$_SESSION['logged_user']->isAdmin) Core::niceError(403);
		
        $template = C::get('Template');
        $db = C::get('DB');
        
        $this->showHeader('Toolbox', "default", false);
		$template->init('main');
  
        $template->set('RUNNING', Settings::get('running'), true);
        
        $template->set('SPOTIFY', Settings::get('spotify'), true);
        $template->set('YOUTUBE', Settings::get('youtube'), true);
        $template->set('LOCAL', Settings::get('local'), true);
        $template->set('UPLOAD', Settings::get('upload'), true);
        
        $db->query("SELECT ID, Name FROM site_events");
        $events = $db->to_array(false, MYSQLI_ASSOC);
        $template->set('EVENTS', $events);
        
        $currentEvent = Settings::get('currentEvent');
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