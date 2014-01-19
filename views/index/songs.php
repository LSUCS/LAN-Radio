<?php

namespace Core\View\Songs;

use \Core;
use Core\Core as C;

class Songs extends \Core\Page {
	
    function headerIncludes() {
        return array(
            'index/websocks.js',
            //'index/file-upload.js',
            'index/search.js',
            'index/boxshadow-hooks.js',
            'index/dataTables.min.js',
            'index/tinyscrollbar.js'
        );
    }
    
	public function render(){
		$this->showHeader('Index');
		
		C::get('Template')->init('main');
		C::get('Template')->push();
		
		$this->showFooter();
	}
	
}