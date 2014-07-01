<?php

namespace Core;

class Settings {
    private static $loaded = false;
    private static $settings;
    
    public static function get($setting) {
        if(!self::$loaded) self::load();
        
        self::checkSetting($setting);
        $s = self::$settings[$setting]["value"];
        
        //Fix for string boolean
        if($s == "false") return false;
        return $s;
    }
    
    public static function set($setting, $value) {
        if(!self::$loaded) self::load();
        
        self::checkSetting($setting);
        
        $db = Core::get('DB');
        $db->query("UPDATE settings SET value = '%s' WHERE setting = '%s'", $value, $setting);
        self::$settings[$setting]["value"] = $value;
    }
    
    private static function load() {
        $db = Core::get('DB');
        $db->query("SELECT * FROM settings");
        self::$settings = $db->to_array("setting", MYSQLI_ASSOC);
        self::$loaded = true;        
    }
    
    private static function checkSetting($setting) {
        if(array_key_exists($setting, self::$settings)) {
            return true;
        } else {
            throw new SettingDoesNotExist("Setting: '" . $setting . "' does not exist!");
        }
    }
}

class SettingDoesNotExist extends \Exception { }