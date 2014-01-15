<?php
class View_History_History extends CorePage{
	
    function headerIncludes() {
        return array(
        
        );
    }
    
	public function render(){
		$this->showHeader('History', "default", false);
		
        Core::get('DB')->query("SELECT * FROM site_events");
        $Events = Core::get('DB')->to_array(false, MYSQLI_ASSOC);
        
		Core::get('Template')->init('main');
        Core::get('Template')->set('EVENTS', $Events);
		Core::get('Template')->push();
		
		//$this->showFooter();
	}
	
}