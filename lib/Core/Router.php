<?php

namespace Core;

use Core\Controller;

/**
 * @throws Exception|_404Exception
 * Core routing system
 */

class Router {
    /**
     * @var Core_Router Instance of self
     */
    private static $_instance = null;
    /**
     * @var string Name of called controller
     */
    private $called_controller = false;

    /**
     * Singleton: no public constructor
     */
    private function __construct() {        
    }    

    /**
     * Singleton: getInstance instantiates if necessary and returns self
     * @static
     * @return Router
     */
    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get the name of the called controller, without Controller_ prefix
     * @throws Exception
     * @return string Name of called controller
     */
    public function getCalledController() {
        if (!$this->called_controller)
            throw new \Exception("getCalledController called before routing occurred!");
        return $this->called_controller;
    }

    /**
     * Run the router!
     * @throws Core404Exception
     * @return void
     */
    public function run() {
        $ru = $_SERVER['REQUEST_URI'];
        
        //Remove and directory stuff from the url
        if(Config::SUBDIR) {
            if(strpos($ru, '/' . Config::SUBDIR) !== -1) $ru = substr($ru, 1 + strlen(Config::SUBDIR));
        }
        
        // this is in the format /add/room

        $end = strpos($ru, '?');
        
        $qs = '';
        if ($end !== false) {
            $qs = substr($ru, $end + 1);
            $ru = substr($ru, 0, $end);
        }
                
        // reinitialise $_GET!
        $_GET = array();
        parse_str($qs, $_GET);

        // and then $_REQUEST...
        $_REQUEST = array();
        $_REQUEST = array_merge($_REQUEST, $_GET);
        $_REQUEST = array_merge($_REQUEST, $_POST);

        if ($ru[0] == '/') $ru = substr($ru, 1);

        if ($ru[strlen($ru) - 1] == '/') $ru = substr($ru, 0, strlen($ru) - 1);

        $ru_parts = explode('/', $ru);

        if (empty($ru_parts[0])) {
            $ru_parts[0] = 'index';
        }

        // okay, now do some magic...

        $controller_name = array_shift($ru_parts); // take first element...

        $controller_name = ucfirst(strtolower(preg_replace('/[^(\x20-\x7F)]*/', '', $controller_name))); // clean it up!

        $this->called_controller = strtolower($controller_name);

        $controller_name = 'Core\\Controller\\' . $controller_name;

        // now!
        try {
        	Core::get('Core')->pieces = $ru;
    
            $controller = new $controller_name;
            $controller->run($ru_parts);
        } catch (AutoloaderException $cae) {
            throw new _404Exception($cae->getMessage(), 1, $nae);
        } catch (\Exception $e) {
            Core::get('Error')->haltException($e);
        }
    }
}