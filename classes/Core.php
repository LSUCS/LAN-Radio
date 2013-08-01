<?php

session_start();

class Core {
    private static $_registryClassMap = array(
        'Error' => 'CoreError',
        'DB' => 'CoreDatabase',
        'Template' => 'CoreTemplate',
        'Cache' => 'CoreCache',
    	'API' => 'CoreAPI'
    );

    private static $_registry = array();
    private static $_loadedLibraries = array();
    private static $_errors = array();

    public $Debug = array();
    public $LoggedUser = array();
    public $renderStarted = 0;
	public $pieces = '';
	
    private function __construct() { }

    public static function initialise() {
        date_default_timezone_set("UTC");
        error_reporting(PHP_ERROR_REPORTING);
        set_error_handler(array('Core', 'registerError'));
        register_shutdown_function(array('Core', 'coreShutdown'));
                
        $C = new Core();
        self::register('Core', $C);
        $C->Debug['RequestedPage'] = $_SERVER['PHP_SELF'];
        $C->handleSessions();
        if($C->loggedIn()) {
            $C->LoggedUser = $_SESSION['logged_user'];
        }
    }

    public static function register($name, $classInst) {
        if(isset(self::$_registry[$name])) return false;
        self::$_registry[$name] = $classInst;
        return $classInst;
    }

    public static function get_classes_in_registry() {
        return array_keys(self::$_registry);
    }

    public static function get($name) {
        if(!isset(self::$_registry[$name])) {
            if(!isset(self::$_registryClassMap[$name])) {
                throw new ClassNotInRegistryException($name . ' was not in the registry');
            } else {
                $C = Core::get('Core');
                return self::register($name, new self::$_registryClassMap[$name]($C));
            }
        }
        return self::$_registry[$name];
    }

    public function handleSessions() {
        if(isset($_COOKIE['session']) && !isset($_SESSION['logged_user'])) {
            // Cookie Security
            $Crypt = new CoreCrypt;
            $cookieInfo = $Crypt->decrypt($_COOKIE['session']);
            if(strstr($cookieInfo ,'||~#~||')) {
                $cookieInfo = explode('||~#~||', $cookieInfo);
                $userID = $cookieInfo[0];
                $sessionID = $cookieInfo[1];
                
                Core::get('DB')->query("SELECT UserID FROM users_sessions WHERE SessionID = ?", array($sessionID));
                $SessionInfo = Core::get('DB')->next_record();

                if($SessionInfo && $SessionInfo['UserID'] == $userID) {
                    /*Core::requireLibrary('LANAuth');
                    $Auth = new LANAuth;
                    $_SESSION['logged_user'] = $Auth->getUserByID($userID);
                    */
                    $_SESSION['logged_user']= Model_User::loadFromID($userID);
                    return;
                }
            }
            $_SESSION = array(); // nuke
            session_destroy();
        }
    }

    /**
     * Check if a user has a particular perm in either their user privs or class privs
     * @param $permname Permission identifier
     * @return bool
     */
    public function hasPerm($permname) {
        return in_array($permname, $this->LoggedUser['_P']);
    }

    /**
     * Returns a boolean indicating whether the user is logged in or not
     * @return boolean
     */
    public function loggedIn() {
        return isset($_SESSION['logged_user']);
    }

    /**
     * Ensures the user is actually loggedin. If not, redirects to the login page
     * @return void
     */
    public function enforceLogin() {
        if (!$this->loggedIn()) {
            echo var_dump($_SESSION);
            die('nope');
            // clean request uri
            $requesturi = $_SERVER['REQUEST_URI'];
            $requesturi = urlencode($requesturi);

            header("Location: " . CORE_SERVER . "login/?return=$requesturi");
            die;
        }
    }

    /**
     * Creates a full hyperlink to the user
     * @param $uid User id number
     * @param $uname Username
     * @param $class_symbol User's class symbol
     * @param $class_color Users class color as an html color code 
     * @return string
     */
    public function linkUser($uid, $uname, $class_color = false) {
        $ClassSymbol = '';
        $ClassColor = (($class_color) ? 'color:#' . $class_color : '');
        return "$ClassSymbol<a href='".CORE_SERVER."user/$uid' style='$ClassColor'>$uname</a>";
    }

    /**
     * Creates a full hyperlink to the logged in user
     * @param bool $skip_class_symbol Set to TRUE to ignore class symbol
     * @param bool $skip_class_color Set to TRUE to ignore class color
     * @return string
     */
    public function linkUserMe($skip_class_symbol = false, $skip_class_color = false) {
        return $this->linkUser(
            $this->LoggedUser->ID,
            $this->LoggedUser->Username/*,
            (($skip_class_symbol) ? '' : $this->LoggedUser['_CI']['Symbol']),
            (($skip_class_color) ? false : $this->LoggedUser['_CI']['Color'])*/);
    }

    public function formatBytes($Bytes, $Decimals = 2) {
        $Unit = array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
        $UnitPointer = 0;
        while($Bytes >= 1024 && $UnitPointer < count($Unit)) {
            $UnitPointer++;
            $Bytes /= 1024;
        }
        return number_format($Bytes, $Decimals) . $Unit[$UnitPointer];
    }
    
    /**
     * Transforms a time in seconds into hours:minutes:seconds 
     * @param int $Time The song time in seconds
     * @return string
     */
    public function get_time($Time) {
        $Hours = $Minutes = $Seconds = 0;
        while($Time > 60*60) {
            $Time -= 60*60;
            $Hours++;
        }
        while($Time > 60) {
            $Time -= 60;
            $Minutes++;
        }
        $Time = round($Time);
        
        if($Time < 10) $Time = '0' . $Time;
        
        if($Hours) {
            if($Minutes < 10) $Minutes = '0' + $Minutes;
            return $String = $Hours . ':' . $Minutes . ':' . $Time;
        } else {
            return $Minutes . ':' . $Time;
        }
    }
    
    /**
     * Converts special characters to HTML entities
     * This is preferable to htmlspecialchars as it doesn't screw up on double escape
     * @param string $Str The string to be converted
     * @return string
     */
    public function displayStr($Str) {
        if($Str === NULL || $Str === FALSE || is_array($Str)) {
            return '';
        }
        if($Str != '' && !self::isNumber($Str)) {
            $Str = self::makeUtf8($Str);
            $Str = mb_convert_encoding($Str, "HTML-ENTITIES", "UTF-8");
            $Str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m", "&amp;", $Str);
    
            $Replace = array(
                "'", '"', "<", ">",
                '&#128;', '&#130;', '&#131;', '&#132;', '&#133;', '&#134;', '&#135;', '&#136;', '&#137;', '&#138;', '&#139;', '&#140;', '&#142;', '&#145;', '&#146;', '&#147;', '&#148;', '&#149;', '&#150;', '&#151;', '&#152;', '&#153;', '&#154;', '&#155;', '&#156;', '&#158;', '&#159;'
            );
    
            $With = array(
                '&#39;', '&quot;', '&lt;', '&gt;',
                '&#8364;', '&#8218;', '&#402;', '&#8222;', '&#8230;', '&#8224;', '&#8225;', '&#710;', '&#8240;', '&#352;', '&#8249;', '&#338;', '&#381;', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8226;', '&#8211;', '&#8212;', '&#732;', '&#8482;', '&#353;', '&#8250;', '&#339;', '&#382;', '&#376;'
            );
    
            $Str = str_replace($Replace, $With, $Str);
        }
        return $Str;
    }
    
    /**
     * Checks if an ID is valid
     * @param string $ID The ID to validate
     * @return bool
     */
    public function validID($ID) {
        return preg_match('/(spotify:(?:track:[a-z0-9]+)|http:\/\/gdata\.youtube\.com\/feeds\/api\/videos\/[a-z0-9-]+)/i', $ID); 
    }
    
    public function unEscapeID($ID) {
        return str_replace(array('\:', '\.', '\[', '\]', '\/'), array(':', '.', '[', ']', '/'), $ID);
    }
    
    public function getSource($ID){
        if(strstr($ID, "spotify") !== false) {
            return "spotify";
        }
        if(strstr($ID, "gdata.youtube.com") !== false) {
            return "youtube";
        }
    }
    
    /**
     * Checks if a string is a valid number
     * @param string $Str The string to test
     * @return bool
     */
    public function isNumber($Str) {
        $Return = true;
        if ($Str < 0) {
            $Return = false;
        }
        // We're converting input to a int, then string and comparing to original
        $Return = ($Str == strval(intval($Str)) ? true : false);
        return $Return;
    }
        
    /**
     * Converts a string's encoding to UTF-8
     * @param string $Str The string to be converted
     * @return string
     */
    public function makeUtf8($Str) {
        if ($Str != "") {
            if (self::isUtf8($Str)) {
                $Encoding = "UTF-8";
            }
            if (!isset($Encoding)) {
                $Encoding = mb_detect_encoding($Str, 'UTF-8, ISO-8859-1');
            }
            if (!isset($Encoding)) {
                $Encoding = "ISO-8859-1";
            }
            
            if ($Encoding == "UTF-8") {
                return $Str;
            } else {
                return @mb_convert_encoding($Str, "UTF-8", $Encoding);
            }
        }
    }
    
    /**
     * Checks if a string is encoded in UTF-8
     * @param string $Str The string to be checked
     * @return bool
     */
    public function isUtf8($Str) {
        return preg_match('%^(?:
    		[\x09\x0A\x0D\x20-\x7E]			 // ASCII
    		| [\xC2-\xDF][\x80-\xBF]			// non-overlong 2-byte
    		| \xE0[\xA0-\xBF][\x80-\xBF]		// excluding overlongs
    		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
    		| \xED[\x80-\x9F][\x80-\xBF]		// excluding surrogates
    		| \xF0[\x90-\xBF][\x80-\xBF]{2}	 // planes 1-3
    		| [\xF1-\xF3][\x80-\xBF]{3}		 // planes 4-15
    		| \xF4[\x80-\x8F][\x80-\xBF]{2}	 // plane 16
    		)*$%xs', $Str
        );
    }


    /**
     * Returns html-formatted debug information
     * @return string
     */
    public function debug() {
        // Populate debug var
        $this->Debug['Queries'] = self::get('DB')->Queries;
        $this->Debug['CacheHits'] = self::get('Cache')->CacheHits;
        $this->Debug['AutoloadedFiles'] = AutoLoader::get_loaded_files();
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
        $ret .= "<div class='debugDiv'><a id='toggle_debug_api' href='#' onclick='return showHideDebug(\"debug_api\")'>(Show)</a> <b>API</b></div>";
        $ret .= "<div id='debug_api' class='debugDivChild'><table width='100%'>";
        $ret .= "<tr><td><a href='#' onclick=\"$.post('api.php', {cmd:prompt('What command do you want to run from the API?'), args:prompt('And what arguments (if any)? You can separate arguments with |')}, function(data){ $('#api_output').hide();$('#api_output').html(data);$('#api_output').fadeIn(); });return false;\">Run Command</a><br/><b>Output:</b> <span id='api_output'>&nbsp;</span></td></tr>";
        $ret .= "</table></div>";
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

    public static function requireLibrary($libraryName, $folder = false) {
        if(in_array($libraryName, self::$_loadedLibraries)) {
            return true;
        }
        
        if($folder) {
            $folder .= "/";
        } else {
            $folder = "";
        }
        	
        // okay, try loading it
        try {
            require_once('libraries/' . $folder . $libraryName . '.php');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

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

?>
