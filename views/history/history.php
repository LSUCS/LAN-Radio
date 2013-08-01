<?php
class View_History_History extends CorePage{
	
    function headerIncludes() {
        return array(
        
        );
    }
    
	public function render(){
		$this->showHeader('History', "default", false);
		
		Core::get('Template')->init('main');
		Core::get('Template')->push();
		
		$this->showFooter();
	}
	
}

?>