<?php

namespace Core\View\About;

use \Core;
use Core\Core as C;

class Main extends \Core\Page {
	public function render(){
		$this->showHeader('About', 'default', false);
		
		C::get('Template')->init('main');
		C::get('Template')->push();
		
		$this->showFooter();
	}
}

?>