<?php
class View_About_Main extends CorePage{
	public function render(){
		$this->showHeader('About', 'default', false);
		
		Core::get('Template')->init('main');
		Core::get('Template')->push();
		
		$this->showFooter();
	}
}

?>