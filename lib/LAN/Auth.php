<?php

class LANAuth {
    private $APIUrl = LANAUTH_API_URL;
    private $APIKey = LANAUTH_API_KEY;
    
    private function apiCall($method, $params) {
		//Prepare fields
        $fields = array("key" => $this->APIKey);
        $fields = array_merge($fields, $params);
        foreach($fields as $key=>$value) $fields[$key] = $key.'='.$value; 

		//Prepare cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim($this->APIUrl, "/") . '/' . $method);
        curl_setopt($ch, CURLOPT_POST, 2);
        curl_setopt($ch, CURLOPT_POSTFIELDS, implode("&", $fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        //Decode response and return
        $result = json_decode(curl_exec($ch), true);
        return $result;
    }
    
    public function getUser($user) {
        return $this->apiCall("getuserbyusername", array('username' => $user));
    }
    
    public function getUserByID($ID) {
        return $this->apiCall("getuserbyid", array('userid'=>$ID));
    }
    
    public function checkCredentials($user, $pass) {
        return $this->apiCall("validatecredentials", array('username' => $user, 'password' => $pass));
    }
}
