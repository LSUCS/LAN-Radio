<?php

class CoreCrypt {
    public function encrypt($Str,$Key=ENCKEY) {
		srand();
		$Str = str_pad($Str, 32-strlen($Str));
		$IVSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$IV = mcrypt_create_iv($IVSize, MCRYPT_RAND);
		$CryptStr = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $Key, $Str, MCRYPT_MODE_CBC, $IV);
		return base64_encode($IV.$CryptStr);
	}

	public function decrypt($CryptStr,$Key=ENCKEY) {
		if ($CryptStr == '') return '';
		
        $IV = substr(base64_decode($CryptStr),0,16);
		$CryptStr = substr(base64_decode($CryptStr),16);
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $Key, $CryptStr, MCRYPT_MODE_CBC,$IV));
	}
    
	public function random_hash($Length = 32) {
		$Secret = '';
		$Chars = 'abcdefghijklmnopqrstuvwxyz0123456789#!?"£$%_-+=';
		$CharLen = strlen($Chars)-1;
		for ($i = 0; $i < $Length; ++$i) {
			$Secret .= $Chars[mt_rand(0, $CharLen)];
		}
		return $Secret;
	}

	public function combine_hash($Str1,$Str2) {
		return sha1(md5($Str2).$Str1.sha1($Str2).SITE_SALT);
	}
}
?>