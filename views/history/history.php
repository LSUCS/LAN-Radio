<?php

namespace Core\View\History;

use \Core;
use Core\Core as C;

class History extends \Core\Page {
	
    function headerIncludes() {
        return array(
        
        );
    }
    
	public function render() {
		$this->showHeader('History', "default", false);
		
        C::get('DB')->query("SELECT * FROM site_events");
        $Events = C::get('DB')->to_array(false, MYSQLI_ASSOC);
        
		C::get('Template')->init('main');
        C::get('Template')->set('EVENTS', $Events);
		C::get('Template')->push();
		
		$this->showFooter();
	}
	
}