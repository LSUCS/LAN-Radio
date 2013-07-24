<?php

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config.php');

// Require the exceptions class
require_once(CLASS_PATH . 'CoreExceptions.php');

/**
 * Core autoloading system.
 * @throws Exception
 */
class CoreAutoLoader {

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
        if(!self::$_debugMode) return;
        echo '[[AUTOLOADER]] ' . $message . "\n";
    }

    /**
     * This is called by PHP when a class is requested.
     * It determines how to load files depending on their prefix.
     * @static
     * @param $className Name of class
     * @return void
     */
    public static function autoload($className) {
        self::log('Attempting to autoload: ' . $className);

        if(class_exists($className, false)) return;

        if(substr_compare($className, 'Core', 0, 4, true) === 0) {
            return self::autoload_core($className);
        } else {
            return self::autoload_non_core($className);
        }
    }

    /**
     * This function is called by autoload() for non-Core-prefixed files.
     * @static
     * @param $className Non-Core prefixed controller name
     * @return void
     */
    private static function autoload_non_core($className) {
        self::log('Not a Core* class...');

        $classPath = strtolower($className);
        // pluralise
        $classPath = substr_replace($classPath, 's', strpos($classPath, '_'), 0);
        // pathify
        $classPath = INSTALL_PATH . str_replace('_', '/', $classPath) . '.php';

        self::load_class($className, $classPath);
    }

    /**
     * This function is called for CoreXXX classes.
     * @static
     * @param $className Core class name
     * @return void
     */
    private static function autoload_core($className) {
        self::log('A Core* class!');

        $classPath = CLASS_PATH . $className . '.php';
        self::load_class($className, $classPath);
    }

    /**
     * This function actually does the musclework and loads the class.
     * @static
     * @throws Exception
     * @param $className Class name - e.g. controller
     * @param $classPath Class path - e.g. classes/controller.php
     * @return void
     */
    private static function load_class($className, $classPath) {
        self::log('Directed to look for ' . $className . ' in ' . $classPath);

        if(file_exists($classPath)) {
            self::log('File exists; loading!');
            require_once($classPath);
        } else {
            self::log('File does not exist =[');
            throw new CoreAutoLoaderException('Could not autoload the class ' . $className . ': file ' . $classPath . ' not found!');
        }

        if(!class_exists($className, false)) {
            self::log('Class not found: ' . $className);
            throw new CoreAutoLoaderException('Class ' . $className . ' was not in file ' . $classPath . '!');
        }

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
        spl_autoload_register(array('CoreAutoLoader', 'autoload'));
    }
}

class CoreAutoLoaderException extends Exception {}