<?php

abstract class CorePage{
	public static $instance;
	public $bodyStyle;
	public $useJQuery = true;
	public $parent;
    public $arguments;
	
	function __construct(&$parent, $arguments = array()) {
		$this->parent = $parent;
        $this->arguments = $arguments;

		self::$instance = $this;
	}
	
	/* Override Functions */
	public function headerIncludes() { }
	public abstract function render();
	
	public function showHeader($pageName = "Unknown", $header="default", $search = true){
		$Template = Core::get('Template');
        //Can force public. Can't force private
		if(!$this->parent->loggedIn() || $header == "public") {
            //Public options
			$Template->init('public_layout', true);
			$Template->set('CLEAR_CACHE', '');
            
            $Template->set('PAGE_NAME', 'login');
		} else {
            //Private options
            
			$Template->init('private_layout', true);
			$Template->set('USER_NAME_LINK', $this->parent->linkUserMe());
			$Template->set('USER_NAME', $this->parent->LoggedUser['username']);
            $Template->set('USER_AUTHKEY', '');

			if($this->parent->LoggedUser['moderator']) {
                $Template->set('MENUITEM_TOOLBOX', true, true);
                $Template->set('MENUITEM_ADMINBAR', true, true);
				$Template->set('CLEAR_CACHE', '<a href="'.Core::get('Cache')->generateClearCacheURL().'" id="clearcache">[Clear Cache]</a>');
			} else {
                $Template->set('MENUITEM_TOOLBOX', false, true);
                $Template->set('MENUITEM_ADMINBAR', false, true);
				$Template->set('CLEAR_CACHE', '');
            }
            
            $ru = $_SERVER['REQUEST_URI'];
            if($ru[0] == '/') $ru = substr($ru, 1);
            $ru_parts = explode('/', $ru);

            if(empty($ru_parts[0])) {
                $ru_parts[0] = 'index';
            }
            
            $Template->set('PAGE_NAME', array_shift($ru_parts));
            $Template->set('SEARCH', ($search) ? true:false, true);
		}
		// Basic Tags
		$Template->set('PAGE_TITLE', "$pageName :: ".SITE_NAME);
		$Template->set('BODY_STYLE', $this->bodyStyle);
		$Template->set('HEADER_INCLUDES', $this->headerIncludes(), true);
		$Template->set('JQUERY_INCLUDES', $this->useJQuery, true);
		$Template->set('HEADER', true, true);
		$Template->set('FOOTER', false, true);

		$Template->push();
	}
	
	public function showFooter(){
		// Stop render time
		list($usec, $sec) = explode(" ", microtime());
		$this->parent->Debug['ViewRenderTime'] = number_format((($usec + $sec) - $this->parent->renderStarted), 8);	
			
		$Template = Core::get('Template');
		if(!$this->parent->loggedIn()) {
			$Template->init('public_layout', true);
			$Template->set('DEBUG_TABLES', '');	
		} else {
			$Template->init('private_layout', true);
			if($this->parent->LoggedUser['IsAdmin'])
				$Template->set('DEBUG_TABLES', $this->parent->debug());
            else
                $Template->set('DEBUG_TABLES', '');
		}
		if(SHOW_RENDERTIME)
			$Template->set('CREATION_TIME', 'This page was created in '.$this->parent->Debug['ViewRenderTime'].' second(s).<br/><br/>');
		else
			$Template->set('CREATION_TIME', '');
		$Template->set('HEADER', false, true);
		$Template->set('FOOTER', true, true);
		$Template->push();
	}
}

?>