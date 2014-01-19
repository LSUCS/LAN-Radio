<?php

namespace Core\Helper\Login;

use \Core as Core;
use Core\Core as C;
use Core\Validate;
use Core\Crypt;

class ProcessLogin extends Core\Helper {
	
	public function run(){
		        
        $Auth = new LAN_Auth;
        
        $UserInfo = $Auth->getUser($_POST['user']);
        if(!$UserInfo) {
            echo 'dne_nouser';
            exit;            
        }
		
		$passwordCorrect = $Auth->checkCredentials($_POST['user'], $_POST['password']);
		if (!$passwordCorrect) {
			echo 'dne_badpass';
			exit;
		}
        
        //IP History and device detection normally goes here
        
        $SessionID = Crypt::random_hash();
        C::get('DB')->query("INSERT INTO users_sessions (SessionID, UserID, Date) VALUES (?, ?, NOW())", $SessionID, $UserInfo['userid']);
        
        $plainKey = $UserInfo['userid'] . "||~#~||" . $SessionID;
        
        $CookieExpire = time()+60*60*24*3; // 3 days, last all LAN
		setcookie('session', Crypt::encrypt($plainKey), $CookieExpire, '/');
	}
}

?>
