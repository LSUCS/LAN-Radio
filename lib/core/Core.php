<?php

namespace Core;

session_start();

class Core {
    private static $_registryClassMap = array(
        'Error' => 'Core\\Error',
        'DB' => 'Core\\Database',
        'Template' => 'Core\\Template'
    );

    private static $_registry = array();
    private static $_loadedLibraries = array();
    private static $_errors = array();

    public $Debug = array();
    public $LoggedUser = array();
    public $renderStarted = 0;
	public $pieces = '';

    /**
     * Core::initialise()
     * Initialise the core class, error and shutdown functions
     * Handle a user's session, and import LoggedUser if they are logged in
     * @return void
     */
    public static function initialise() {
        date_default_timezone_set("UTC");
        error_reporting(PHP_ERROR_REPORTING);
        set_error_handler(array('\\Core\\Core', 'registerError'));
        register_shutdown_function(array('\\Core\\Core', 'coreShutdown'));
                
        $C = new Core();
        self::register('Core', $C);
        $C->Debug['RequestedPage'] = $_SERVER['PHP_SELF'];
        Session::handleSessions();
        if(Session::loggedIn()) {
            $C->LoggedUser = $_SESSION['logged_user'];
        }
    }

    /**
     * Core::register()
     * Called when a Core class is loaded. Registers a class in $_registry
     * Returns given instance of class
     * @param string $name
     * @param instance $classInst
     * @return $classInst
     */
    public static function register($name, $classInst) {
        if(isset(self::$_registry[$name])) return false;
        self::$_registry[$name] = $classInst;
        return $classInst;
    }

    /**
     * Core::get_classes_in_registry()
     * Returns a list of classes that have already been loaded into the registry
     * @return array
     */
    public static function get_classes_in_registry() {
        return array_keys(self::$_registry);
    }

    /**
     * Core::get()
     * Gets a class instance from the registry
     * @param string $name
     * @return class instance
     */
    public static function get($name) {
        if(!isset(self::$_registry[$name])) {
            if(!isset(self::$_registryClassMap[$name])) {
                throw new ClassNotInRegistryException($name . ' was not in the registry');
            } else {
                $C = self::get('Core');
                return self::register($name, new self::$_registryClassMap[$name]($C));
            }
        }
        return self::$_registry[$name];
    }
    
    /**
     * Core::loadLibrary()
     * Loads a library/file that doesn't support namespace/autoloading
     * @param string $lib  The library to be loaded, name after lib/  e.g something.php or /foo/bar.php
     * @return void
     */
    public static function loadLibrary($lib) {
        require(Config::CLASS_PATH . DIRECTORY_SEPARATOR . $lib);
    }

    /**
     * Returns html-formatted debug information
     * @return string
     */
    public function debug() {
        // Populate debug var
        $this->Debug['Queries'] = self::get('DB')->Queries;
        $this->Debug['CacheHits'] = Cache::CacheHits;
        $this->Debug['AutoloadedFiles'] = CoreAutoLoader::get_loaded_files();
        $this->Debug['RegistryLoaded'] = self::get_classes_in_registry();
        $this->Debug['Core'] = array('RequestedPage', 'View', 'ViewRenderTime', 'Templates', 'RegistryLoaded');
        $ret = "<div class='debugContainer'>";
        // Errors
        if(count(self::$_errors) >= 1) {
            $ret .= "<div class='debugDivError'><a id='toggle_debug_errors' href='#' onclick='return showHideDebug(\"debug_errors\")'>(Show)</a> <b>Errors</b> (" . count(self::$_errors) . ")</div>";
            $ret .= "<div id='debug_errors' class='debugDivChildError'><table width='100%'>";
            foreach (self::$_errors as $SoftError) {
                $ret .= "<tr><td align='left' valign='middle'>$SoftError</td></tr>";
            }
            $ret .= "</table></div>";
        }
        // API
        //$ret .= "<div class='debugDiv'><a id='toggle_debug_api' href='#' onclick='return showHideDebug(\"debug_api\")'>(Show)</a> <b>API</b></div>";
        //$ret .= "<div id='debug_api' class='debugDivChild'><table width='100%'>";
        //$ret .= "<tr><td><a href='#' onclick=\"$.post('api.php', {cmd:prompt('What command do you want to run from the API?'), args:prompt('And what arguments (if any)? You can separate arguments with |')}, function(data){ $('#api_output').hide();$('#api_output').html(data);$('#api_output').fadeIn(); });return false;\">Run Command</a><br/><b>Output:</b> <span id='api_output'>&nbsp;</span></td></tr>";
        //$ret .= "</table></div>";
        // Debugging
        $ret .= "<div class='debugDiv'><a id='toggle_debug' href='#' onclick='return showHideDebug(\"debug\")'>(Show)</a> <b>Variables</b> (" . count($this->Debug['Core']) . ")</div>";
        $ret .= "<div id='debug' class='debugDivChild'><table width='100%'>";
        foreach($this->Debug['Core'] as $CoreID) {
            if(is_array($this->Debug[$CoreID])) {
                foreach ($this->Debug[$CoreID] as $ArrayContents)
                    $ret .= "<tr><td align='center' valign='middle' width='150px'><b>$CoreID" . '[]' . "</b></td><td align='left' valign='top'>$ArrayContents</td></tr>";
            } else
                $ret .= "<tr><td align='center' valign='middle' width='150px'><b>$CoreID</b></td><td align='left' valign='top'>{$this->Debug[$CoreID]}</td></tr>";
        }
        $ret .= "</table></div>";
        // AutoLoader Debugging
        $ret .= "<div class='debugDiv'><a id='toggle_debug_autoloader' href='#' onclick='return showHideDebug(\"debug_autoloader\")'>(Show)</a> <b>Autoloaded</b> (" . count($this->Debug['AutoloadedFiles']) . ")</div>";
        $ret .= "<div id='debug_autoloader' class='debugDivChild'><table width='100%'>";
        foreach($this->Debug['AutoloadedFiles'] as $AF) {
            $ret .= "<tr><td align='center' valign='middle' width='150px'><b>{$AF['class']}</b></td><td align='left' valign='top'>{$AF['file']}</td></tr>";
        }
        $ret .= "</table></div>";
        // SQL Debugging
        if(count($this->Debug['Queries'])) {
            $ret .= "<div class='debugDiv'><a id='toggle_debug_sql' href='#' onclick='return showHideDebug(\"debug_sql\")'>(Show)</a> <b>SQL Queries</b> (" . count($this->Debug['Queries']) . ")</div>";
            $ret .= "<div id='debug_sql' class='debugDivChild'><table width='100%'>";
            foreach ($this->Debug['Queries'] as $QueryInfo) {
                $ret .= "<tr><td align='left' valign='top'>{$QueryInfo['Query']}</td><td align='right' valign='top' width='200px'>{$QueryInfo['ExecutionTime']} ms</td></tr>";
            }
            $ret .= "</table></div>";
        }
        // Cache Debugging
        if(count($this->Debug['CacheHits'])) {
            $ret .= "<div class='debugDiv'><a id='toggle_debug_cache' href='#' onclick='return showHideDebug(\"debug_cache\")'>(Show)</a> <b>Cache Hits</b> (" . count($this->Debug['CacheHits']) . ")</div>";
            $ret .= "<div id='debug_cache' class='debugDivChild'><table width='100%'>";
            foreach ($this->Debug['CacheHits'] as $CacheInfo) {
                if (is_array($CacheInfo['Value']))
                    $ret .= "<tr><td align='center' valign='middle' width='150px'><b>{$CacheInfo['Key']}</b></td><td align='left' valign='top' style='border:1px solid #222;padding:5px;'><pre>" . var_export($CacheInfo['Value'], true) . "</pre></td><td align='right' width='50px'>{$CacheInfo['Source']}</td></tr>";
                else
                    $ret .= "<tr><td align='center' valign='middle' width='150px'><b>{$CacheInfo['Key']}</b></td><td align='left' valign='top' style='border:1px solid #222;padding:5px;'>{$CacheInfo['Value']}</td><td align='right' width='50px'>{$CacheInfo['Source']}</td></tr>";
            }
            $ret .= "</table></div>";
            $ret .= "</div>";
        }
        return $ret;
    }

    /**
     * Redirects the user to the nicely formatted error page
     * @param $id Error number (refer to views/error/error.php for error numbers)
     * @return void
     */
    public function niceError($id) {
        header("Location: error.php?id=$id");
        exit;
    }

    /**
     * Core::coreShutdown()
     * Core has suffered a fatal error, and has to shutdown.
     * Show debug information for the user
     * @return void
     */
    public static function coreShutdown() {
        $error = error_get_last();
        if ($error['type'] == 1) {
            ob_clean();
            echo "<h2>Uh oh! A problem has been encountered...</h2>";
            echo "<b>Error:</b> {$error['message']}<br/>";
            echo "<b>File:</b> {$error['file']} (line {$error['line']})<br/>";
            echo "<br/>Cannot continue since this is causing a fatal PHP error.";
        }
    }

    /**
     * Registers an error that is shown in the debug output
     * @param $errno Error number. Use 0 for non-php errors.
     * @param $errstr Error description
     * @param $errfile Full path to the file that caused the error
     * @param $errline Line number, if possible, to where the error occurred. Use "null" if can't determine.
     * @return void
     */
    public static function registerError($errno, $errstr, $errfile, $errline) {
        if (ini_get('error_reporting') == 0) return; // @ operator?
        self::$_errors[] = "PHP Error $errno: $errstr (File: $errfile, Line: $errline)";
    }
}