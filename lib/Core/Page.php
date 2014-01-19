<?php

namespace Core;

abstract class Page{
	public static $instance;
	public $bodyStyle;
	public $useJQuery = true;
	public $arguments;
    private $controller;
	
	function __construct($controller, $arguments = array()) {
        $this->controller = $controller;
        $this->arguments = $arguments;

		self::$instance = $this;
	}
	
	/* Override Functions */
	public function headerIncludes() { }
	public abstract function render();
	
	public function showHeader($pageName = "Unknown", $header="default", $search = true){
		$Template = Core::get('Template');
        //Can force public. Can't force private
		if(!Session::loggedIn() || $header == "public") {
            //Public options
			$Template->init('public_layout', true);
			$Template->set('CLEAR_CACHE', '');
            
            $Template->set('PAGE_NAME', 'login');
		} else {
            //Private options
            
			$Template->init('private_layout', true);
			$Template->set('USER_NAME_LINK', Session::getUser()->link());
			$Template->set('USER_NAME', Session::getUser()->username);
            $Template->set('USER_AUTHKEY', '');

			if(Session::isAdmin()) {			 
                $Template->set('MENUITEM_TOOLBOX', true, true);
                $Template->set('MENUITEM_ADMINBAR', true, true);
				$Template->set('CLEAR_CACHE', '<a href="'.Cache::generateClearCacheURL().'" id="clearcache">[Clear Cache]</a>');
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
        
        //Get the controller from the router
        $r = Router::getInstance();
        $controller = $r->getCalledController();
        
        //Get the action from the controller
        $action = $this->controller->getCalledAction();
        
		// Basic Tags
        $Template->set("CONTROLLER", $controller);
        $Template->set("ACTION", $action);
		$Template->set('PAGE_TITLE', "$pageName :: " . Config::SITE_NAME);
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
        
		Core::get('Core')->Debug['ViewRenderTime'] = number_format((($usec + $sec) - Core::get('Core')->renderStarted), 8);	
			
		$Template = Core::get('Template');
		if(!Session::loggedIn()) {
			$Template->init('public_layout', true);
			$Template->set('DEBUG_TABLES', '');	
		} else {
			$Template->init('private_layout', true);
			if(Session::isAdmin()) {
				$Template->set('DEBUG_TABLES', Core::get('Core')->debug());
                $Template->set('CREATION_TIME', 'This page was created in ' . Core::get('Core')->Debug['ViewRenderTime' ]. ' second(s).<br/>');
            } else {
                $Template->set('DEBUG_TABLES', '');
                $Template->set('CREATION_TIME', '');
            }
		}
			
		$Template->set('HEADER', false, true);
		$Template->set('FOOTER', true, true);
        $Template->set('YEAR', date('Y'));
		$Template->push();
        
	}
}