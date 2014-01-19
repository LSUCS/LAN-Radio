<?php

namespace LAN;

use Core\Config;

class Auth {    
    private static function apiCall($method, $params) {
		//Prepare fields
        $fields = array("key" => Config::LANAUTH_API_KEY);
        $fields = array_merge($fields, $params);
        foreach($fields as $key=>$value) $fields[$key] = $key.'='.$value; 

		//Prepare cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim(Config::LANAUTH_API_URL, "/") . '/' . $method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        //Decode response and return
        $result = json_decode($result, true);
        return $result;
    }
    
    public static function getUser($user) {
        return self::apiCall("getuserbyusername", array('username' => $user));
    }
    
    public static function getUserByID($ID) {
        return self::apiCall("getuserbyid", array('userid'=>$ID));
    }
    
    public static function checkCredentials($user, $pass) {
        return self::apiCall("validatecredentials", array('username' => $user, 'password' => $pass));
    }
}
