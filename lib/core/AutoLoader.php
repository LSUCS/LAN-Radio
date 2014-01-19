<?php

namespace Core;

require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Config.php');

/**
 * Core autoloading system.
 * @throws AutoLoaderException
 */
class AutoLoader {

    private static $_loadedFiles = array();
    private static $_debugMode = false;

    private function __construct() {}

    /**
     * Debugging function - call this on init to activate debugging system
     * @static
     * @return void
     */
    public static function debug() {
        self::$_debugMode = true;
    }

    /**
     * Debug logging function
     * @static
     * @param $message
     * @return void
     */
    public static function log($message) {
        //if(!self::$_debugMode) return;
        if(!isset($_GET['debug'])) return;
        echo '[[AUTOLOADER]] ' . $message . "\n";
    }

    /**
     * This is called by PHP when a class is requested.
     * It determines how to load files depending on their prefix.
     * @static
     * @param $className Name of class
     * @return void
     */
    public static function autoload($classFullName) {
        self::log('Attempting to autoload: ' . $classFullName);
        
        //Replace underscores with breaks, if no \'s exist. This allows support for pre-php5.3 style namespacing, where classes were prefixed with Lib_
        if(strstr($classFullName, "\\") === false) {
            $classFullName = str_replace('_', '\\', $classFullName);
        }        
        
        //Find where the namespace ends
        $libOffset = strpos($classFullName, '\\');
        
        //Library = namespace name, class name becomes everything after that        
        $library = substr($classFullName, 0, $libOffset);
        $className = substr($classFullName, $libOffset+1);
        
        //Swift breaks things
        if($library == "Swift") {
            self::log('Ignoring Swift Mailer');
            return;
        }

        //Log the Namespace
        self::log('Library/Namespace: ' . $library);
        
        //Check if the class has already been loaded
        if(class_exists('\\' . $library . '\\' . $classFullName, false)) return;                        
                   
        //Turn the class name into a file path
        if($library == "Core") {
            $classOffset = strpos($className, '\\');
            if($classOffset) {
                $classType = substr($className, 0, $classOffset);
                //Certain core files are in MVC structure, not lib folder
                if(in_array($classType, array('Controller', 'Helper', 'View', 'Model'))) {
                    $classPath = Config::INSTALL_PATH . str_replace('\\', DIRECTORY_SEPARATOR, '\\' . strtolower($classType) . 's\\' . strtolower(substr($className, $classOffset + 1)) . '.php');
                }
            } 
        }
        //Everything else should be in the lib folder (CLASS_PATH)
        if(!isset($classPath)) {
            //All Core Exceptions are contained in Exceptions.php
            if($library == "Core" && strstr($className, 'Exception')) {
                $classPath = Config::CLASS_PATH . str_replace('\\', DIRECTORY_SEPARATOR, '\\' . $library . '\\Exceptions.php');
            } else {
                $classPath = Config::CLASS_PATH . str_replace('\\', DIRECTORY_SEPARATOR, '\\' . $library . '\\' . $className . '.php');
            }
        }

        self::load_class($library, $className, $classPath);
    }

    /**
     * This function actually does the musclework and loads the class.
     * @static
     * @throws AutoLoaderException
     * @param $library Class library or namespace - e.g Core
     * @param $className Class name - e.g. controller
     * @param $classPath Class path - e.g. lib/Core/controller.php
     * @return void
     */
    private static function load_class($library, $className, $classPath) {
        self::log('Directed to look for ' . $className . ' in ' . $classPath . ' within the namespace: ' . $library);
        
        //Load file if it exists, if not error
        if(file_exists($classPath)) {
            self::log('File exists; loading!');
            require_once($classPath);
        } else {
            self::log('File does not exist =[');
            throw new AutoLoaderException('Could not autoload the class ' . $className . ': file ' . $classPath . ' not found!');
        }

        //Look for the class or interface nice and freshly loaded. Error if it cannot be found
        if(!class_exists('\\' . $library . '\\' . $className, false) && !interface_exists('\\' . $library . '\\' . $className, false)) {
            self::log('Class not found: ' . '\\' . $library . '\\' . $className);
            throw new AutoLoaderException('Class ' . $className . ' was not in file ' . $classPath . '!');
        } else {
            self::log('Found ' . $className);
        }
        
        //Add it to the loaded files debug info
        self::$_loadedFiles[] = array('file' => $classPath, 'class' => $className);
    }

    /**
     * Debugging function that returns the array of arrays
     * e.g. array(array('file' => 'classes/controller.php', 'class' => 'controller'))
     * @static
     * @return array
     */
    public static function get_loaded_files() {
        return self::$_loadedFiles;
    }

    /**
     * Set up the autoloader by registering it with PHP.
     * @static
     * @return void
     */
    public static function initialise() {
        spl_autoload_register(array('\Core\AutoLoader', 'autoload'));
    }
}

class AutoLoaderException extends \Exception {}