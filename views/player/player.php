<?php
class View_Player_Player extends CorePage {
	public function render() {
		
        $T = Core::get('Template');
		$T->init('main');
        $T->set('STREAM_LINK', STREAM_HOST . STREAM_MOUNT);
        $T->push();

	}
	
}