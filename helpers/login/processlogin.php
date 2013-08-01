<?php
class Helper_Login_ProcessLogin extends CoreHelper{
	
	public function run(){
		        
        Core::requireLibrary("LANAuth");
        $Auth = new LANAuth;
        
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
        
        $Crypt = new CoreCrypt;
        
        $SessionID = $Crypt->random_hash();
        Core::get('DB')->query("INSERT INTO users_sessions (SessionID, UserID, Date) VALUES (?, ?, NOW())", array($SessionID, $UserInfo['userid']));
        
        $plainKey = $UserInfo['userid'] . "||~#~||" . $SessionID;
        
        $CookieExpire = time()+60*60*24*3; // 3 days, last all LAN
		setcookie('session', $Crypt->encrypt($plainKey), $CookieExpire, '/');
	}
}


?>