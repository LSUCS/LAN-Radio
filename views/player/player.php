<?php

namespace Core\View\Player;

use \Core;
use Core\Core as C;

class Player extends \Core\Page {
	public function render() {
		
        $T = C::get('Template');
		$T->init('main');
        $T->set('STREAM_LINK', STREAM_HOST . STREAM_MOUNT);
        $T->push();

	}
	
}