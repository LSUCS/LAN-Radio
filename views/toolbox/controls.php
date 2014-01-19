<?php

namespace Core\View\Toolbox;

use \Core;
use Core\Core as C;

class Controls extends \Core\Page {
	public function render(){
        $template = C::get('Template');
        $db = C::get('DB');
        
        $this->showHeader('Controls', "default", false);
		$template->init('controls');
		
        $template->push();
		$this->showFooter();
	}
	
} 