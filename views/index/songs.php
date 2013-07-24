<?php
class View_Index_Songs extends CorePage{
	
    function headerIncludes() {
        return array(
            'index/websocks.js',
            'index/file-upload.js',
            'index/search.js',
            'index/boxshadow-hooks.js',
            'index/dataTables.min.js',
            'index/tinyscrollbar.js'
        );
    }
    
	public function render(){

		$this->showHeader('Index');
		
		Core::get('Template')->init('main');
        Core::get('Template')->set('TEST', 'testing');
		Core::get('Template')->push();
		
		$this->showFooter();
	}
	
}

?>