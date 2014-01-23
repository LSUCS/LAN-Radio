<?php

namespace Core\View\Player;

use \Core;
use Core\Core as C;
use Core\Config;

class Player extends \Core\Page {
	public function render() {
		
        $T = C::get('Template');
		$T->init('main');
        $T->set('STREAM_LINK', Config::STREAM_HOST . Config::STREAM_MOUNT);
        $T->push();

	}
	
}