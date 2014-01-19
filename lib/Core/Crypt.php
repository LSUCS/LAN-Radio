<?php

namespace Core;

class Crypt {
    /**
     * Crypt::encrypt()
     * Encrypts a string
     * @param string $Str
     * @param string $Key. Encryption key. Config::ENCKEY by default
     * @return string
     */
    public static function encrypt($Str, $Key=Config::ENCKEY) {
		$Str = str_pad($Str, 32-strlen($Str));
		$IVSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$IV = mcrypt_create_iv($IVSize, MCRYPT_RAND);
		$CryptStr = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $Key, $Str, MCRYPT_MODE_CBC, $IV);
		return base64_encode($IV.$CryptStr);
	}

	/**
	 * Crypt::decrypt()
	 * Decrypts a string
	 * @param string $CryptStr
	 * @param string $Key. Encryption key. Config::ENCKEY by default
	 * @return string
	 */
	public static function decrypt($CryptStr, $Key=Config::ENCKEY) {
		if ($CryptStr == '') return '';
		
        $IV = substr(base64_decode($CryptStr),0,16);
		$CryptStr = substr(base64_decode($CryptStr),16);
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $Key, $CryptStr, MCRYPT_MODE_CBC,$IV));
	}
    
	/**
	 * Crypt::randomHash()
	 * Returns a random hash. Used for password secrets.
	 * @param integer $Length. Length of the random hash string.
	 * @return string
	 */
	public static function randomHash($Length = 32) {
		$Secret = '';
		$Chars = 'abcdefghijklmnopqrstuvwxyz0123456789#!?"?$%_-+=';
		$CharLen = strlen($Chars)-1;
		for ($i = 0; $i < $Length; ++$i) {
			$Secret .= $Chars[mt_rand(0, $CharLen)];
		}
		return $Secret;
	}

	/**
	 * Crypt::combineHash()
	 * Combines two hashes using a random algorithm (fixed)
	 * @param string $Str1
	 * @param string $Str2
	 * @return
	 */
	public static function combineHash($Str1, $Str2) {
		return sha1(md5($Str1).sha1($Str2).$Str1);
	}
    
    /**
     * Crypt::hashPassword()
     * One way hashing of a password, secret, and site salt
     * @param string $password
     * @param string $secret
     * @return string
     */
    public static function hashPassword($password, $secret) {
        return crypt($password, self::combineHash($secret, Config::SITE_SALT));
    }
    
    /**
     * Crypt::verifyPassword()
     * Checks if a password hash is equal to the stored password hash
     * @param string $password
     * @param string $hash
     * @param string $secret
     * @return bool
     */
    public static function verifyPassword($password, $hash, $secret) {
        return ($hash == self::hashPassword($password, $secret));
    }
}