<?php
class View_Toolbox_Controls extends CorePage{    
	public function render(){
        $template = Core::get('Template');
        $db = Core::get('DB');
        
        $this->showHeader('Controls', "default", false);
		$template->init('controls');
		
        $template->push();
		$this->showFooter();
	}
	
} 