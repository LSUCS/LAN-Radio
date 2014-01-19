<?php

namespace Core;

class Utility {

    /**
     * Creates a full hyperlink to the user
     * @param $uid User id number
     * @param $uname Username
     * @param $class_symbol User's class symbol
     * @param $class_color Users class color as an html color code 
     * @return string
     */
    public function linkUser($User) {
        return "<a href='//lsucs.org.uk/members/" . $User->ID . "'>" . $User->username . "</a>";
    }

    /**
     * Creates a full hyperlink to the logged in user
     * @param bool $skip_class_symbol Set to TRUE to ignore class symbol
     * @param bool $skip_class_color Set to TRUE to ignore class color
     * @return string
     */
    public function linkUserMe() {
        return $this->linkUser($this->LoggedUser);
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
    
    public function timeDiff($TimeStamp, $Diff = true, $Levels=2, $HideAgo=false, $Span=true, $Lowercase=false) {
        /*
        Returns a <span> by default but can optionally return the raw time
        difference in text (e.g. "16 hours and 28 minutes", "1 day, 18 hours").
        */
        if(!Core::isNumber($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
            if($TimeStamp == '0000-00-00 00:00:00') { return 'Never'; }
            $TimeStamp = strtotime($TimeStamp);
        }
        if($TimeStamp == 0) { return 'Never'; }
        
        if($Diff) {
            $Time = time()-$TimeStamp;
        } else {
            $Time = $TimeStamp;
        }
        
        // If the time is negative, then it expires in the future.
        if($Time < 0) {
            $Time = -$Time;
            $HideAgo = true;
        }

        $Years=floor($Time/31556926); // seconds in one year
        $Remain = $Time - $Years*31556926;

        $Months = floor($Remain/2629744); // seconds in one month
        $Remain = $Remain - $Months*2629744;

        $Weeks = floor($Remain/604800); // seconds in one week
        $Remain = $Remain - $Weeks*604800;

        $Days = floor($Remain/86400); // seconds in one day
        $Remain = $Remain - $Days*86400;

        $Hours=floor($Remain/3600); // seconds in one hour
        $Remain = $Remain - $Hours*3600;

        $Minutes=floor($Remain/60); // seconds in one minute
        $Remain = $Remain - $Minutes*60;

        $Seconds = $Remain;

        $Return = '';

        if($Years>0 && $Levels>0) {
            if($Years>1) {
                $Return .= $Years.' years';
            } else {
                $Return .= $Years.' year';
            }
            $Levels--;
        }

        if($Months>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if ($Months>1) {
                $Return.=$Months.' months';
            } else {
                $Return.=$Months.' month';
            }
            $Levels--;
        }

        if($Weeks>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if ($Weeks>1) { 
                $Return.=$Weeks.' weeks';
            } else {
                $Return.=$Weeks.' week';
            }
            $Levels--;
        }

        if($Days>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if ($Days>1) {
                $Return.=$Days.' days';
            } else {
                $Return.=$Days.' day';
            }
            $Levels--;
        }

        if($Hours>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if ($Hours>1) {
                $Return.=$Hours.' hours';
            } else {
                $Return.=$Hours.' hour';
            }
            $Levels--;
        }

        if($Minutes>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if ($Minutes>1) {
                $Return.=$Minutes.' mins';
            } else {
                $Return.=$Minutes.' min';
            }
            $Levels--;
        }
        
        if($Seconds>0 && $Levels>0) {
            if(!empty($Return)) {
                $Return.=', ';
            }
            if($Seconds>1) {
                $Return.=$Seconds.' seconds';
            } else {
                $Return.=$Seconds.' second';
            }
            $Levels--;
        }
        
        if(empty($Return)) {
            $Return = 'Just now';
        } elseif (!$HideAgo) {
            $Return .= ' ago';
        }

        if($Lowercase) {
            $Return = strtolower($Return);
        }
        
        if($Span) {
            if($Diff) {
                return '<span class="time" title="'.date('M d Y, H:i', $TimeStamp).'">'.$Return.'</span>';
            } else {
                return '<span class="time" title="'. self::timeDiff($TimeStamp, false, 10, true, false) .'">'.$Return.'</span>';
            }
        } else {
            return $Return;
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
        return preg_match('/(spotify:(?:track:[a-z0-9]+)|http:\/\/gdata\.youtube\.com\/feeds\/api\/videos\/[a-z0-9-_]+)/i', $ID); 
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
        $Return = ($Str == strval(intval($Str))) ? true : false;
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
}