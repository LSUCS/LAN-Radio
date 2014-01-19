<?php

namespace Core;
use Core\Model\User;

/**
 * Session
 * Handles user's session data. Manages cookies.
 * Handles logging out and enforce login operations.
 */
Class Session { 
    private static $loggedUser = false;
    
    /**
     * Session::handleSessions()
     * Handles an active user's sessions, clearing out any invalid or expired sessions
     * Populates $_SESSION['logged_user'] and loads User Model
     * @return void
     */
    public function handleSessions() {
        //if(isset($_COOKIE['session']) && !isset($_SESSION['logged_user'])) {
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
                    $_SESSION['logged_user'] = new User($userID);
                    //if($_SESSION['logged_user']->Username !== "MetalMichael" && $_SESSION['logged_user']->Username !== "Mael") die("Live Maintenence Taking Place. Up Soon");
                    return;
                }
            }
            $_SESSION = array(); // nuke
            session_destroy();
        //}
    }
    
    /**
     * Session::logout()
     * Logs a user out, and deletes all cookie and session data
     * Redirects to login page
     * @return void
     */
     public static function logout() {
        if(self::loggedIn() || isset($_COOKIE['session'])) {
            //Delete the session from the database
            if(isset($_COOKIE['session'])) {
                $cookieInfo = Crypt::decrypt($_COOKIE['session']);
                
                Core::get('DB')->query("DELETE FROM UserSessions WHERE UserID = ? AND SessionID = ?", $cookieInfo[0], $cookieInfo[1]);
            }
            
            //Delete cookies
            unset($_SESSION['logged_user']);
            session_destroy();
            setcookie('session', '', time() - 3600, '/');
            self::$loggedUser = false;
        }
        //Redirect to login
        header('Location: ' . Config::CORE_SERVER . 'login?method=loggedout');
        die;
     }
    
    /**
     * Returns a boolean indicating whether the user is logged in or not
     * Based on self::loggedUser
     * @return boolean
     */
    public static function loggedIn() {
        return (self::$loggedUser !== false);
    }

    /**
     * Ensures the user is actually loggedin. If not, redirects to the login page
     * @return void
     */
    public static function enforceLogin() {
        if(!self::loggedIn()) {
            //Clean request uri
            $requesturi = $_SERVER['REQUEST_URI'];
            $requesturi = urlencode($requesturi);
            
            //Redirect
            header("Location: " . Config::CORE_SERVER . "login/?return=$requesturi");
            die;
        }
    }
    
    /**
     * Returns a boolean indicating whether the user is an admin or not
     * Based on self::loggedUser
     * @return boolean
     */
    public static function isAdmin() {
        return (self::$loggedUser->isAdmin());
    }
    
    /**
     * Ensures a user is an admin. If not, they are given a 403 error
     * @return void
     */
    public static function enforceAdmin() {
        if(!self::loggedIn() || !self::isAdmin()) {
            Core::niceError(403);
        }
        return true;
    }
    
    
    /**
     * Gets the logged in user object, or false if there is no user logged in
     * @return User self::$loggedUser
     */
    public static function getUser() {
        return self::$loggedUser;
    }
    
}