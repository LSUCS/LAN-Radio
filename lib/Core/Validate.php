<?php

namespace Core;

define('USERNAME_REGEX', '/^[a-z0-9-_\/]{1,20}$/i');
define('EMAIL_REGEX', '/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i');

class Validate {
    
    private $Data = array();
    private $Errors = array();
    
    private $Field;
    private $Value;
    
    private $DebugMode;
    
    // All of the datatypes that the class supports. Partially for reference.
    // Also checks the type entered, and errors on it not being here.
    private $Types = array(
        'string',
        'integer',
        'number',
        'checkbox',
        'inarray',
        'regex',
        'email',
        'compare',
        'username',
        'day',
        'month',
        'year',
        'hour',
        'minute',
        'second',
        'trackid'
    );
    
    // Again, for reference, but is also checked, to make sure everything's being used correctly
    private $AvailableParameters = array(
        'maxlength',
        'minlength',
        'maxsize',
        'minsize',
        'maxdp',
        'inarray',
        'compare',
        'regex'
    );
    
    function __construct($Data, $Debug = false) {
        $this->Data = $Data;
        $this->DebugMode = $Debug;
    }
    
    public function val($FieldName, $FieldType, $Required, $Error, $Parameters = array()) {
        if(empty($FieldName) || empty($FieldType)) return $this->err('Invalid Name or Type');
        if(!in_array($FieldType, $this->Types)) return $this->err('Invalid Field Type');
        
        if(count(array_intersect(array_keys($Parameters), $this->AvailableParameters)) !== count($Parameters)) {
            return $this->debugParameters($Parameters, $FieldName);
        }
        
        $this->Field = array(
            'Name' => $FieldName,
            'Type' => $FieldType,
            'Error' => $Error,
            'Required' => ($Required) ? true : false,
            'Parameters' => $Parameters
        );
        
        return $this->doValidation();
    }
    
    private function doValidation() {
        $F = $this->Field;
        
        if(!array_key_exists($F['Name'], $this->Data) || is_null($this->Data[$F['Name']])) {
            if($F['Required']) {
                // Missing required value
                return $this->err('There is no value in the required field: \''.$F['Name'].'\'.');
            } else {
                // Not required, so no value to validate
                return true;
            }
        }
        $this->Value = $this->Data[$F['Name']];
        
        switch($F['Type']) {
            case 'string':
                return $this->validateString();
            case 'day':
            case 'month':
            case 'year':
            case 'hour':
            case 'minute':
            case 'second':
            case 'integer':
                return $this->validateInt($F['Type']);
            case 'number':
                return $this->validateNumber();
            case 'checkbox':
                // Not much to validate. If it's required, and not set, it's already been covered.
                return true;
            case 'inarray':
                return $this->validateInArr();
            case 'regex':
            case 'email':
            case 'username':
                return $this->validateRegex($F['Type']);
            case 'compare':
                return $this->validateCompare();
            case 'trackid':
                return $this->validateTrackID();
        }
        // Only way this should happen, is if you add a type, and don't add it in the switch.
        // ...Don't do that.
        $this->Error('This shouldn\'t happen =[');
    }
    
    // How long is a piece of string?
    private function validateString() {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
        
        // Not really sure how this is possible, provided an input field is used, but oh well.
        if(!is_string($V)) return $this->err('Entered value for: \''.$F['Name'].'\' is not a String: \''.$V.'\'');
        
        $Length = strlen($V);
        if(array_key_exists('maxlength', $P) && $Length > $P['maxlength']) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too long. Maximum Length: '.$P['maxlength'].', Given Length: '.(string)$Length);
        }
        if(array_key_exists('minlength', $P) && $Length < $P['minlength']) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too short. Minimum Length: '.$P['minlength'].', Given Length: '.(string)$Length);
        }
        return true;
    }
    
    // Whole numbers only
    private function validateInt($Type = 'number') {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
    
        $Append = ' ('.ucfirst($Type).' Field)';
        switch($Type) {
            case 'day':
                $Max = (isset($P['maxsize'])) ? $P['maxsize'] : 31;
                $Min = (isset($P['minsize'])) ? $P['minsize'] : 1;
                break;
            case 'month':
                $Max = (isset($P['maxsize'])) ? $P['maxsize'] : 12;
                $Min = (isset($P['minsize'])) ? $P['minsize'] : 1;
                break;
            case 'year':
                $Max = (isset($P['maxsize'])) ? $P['maxsize'] : 2100;
                $Min = (isset($P['minsize'])) ? $P['minsize'] : 1900;
                break;
            case 'hour':
                $Max = (isset($P['maxsize'])) ? $P['maxsize'] : 23;
                $Min = (isset($P['minsize'])) ? $P['minsize'] : 0;
                break;
            case 'minute':
            case 'second':
                $Max = (isset($P['maxsize'])) ? $P['maxsize'] : 59;
                $Min = (isset($P['minsize'])) ? $P['minsize'] : 0;
                break;
            case 'number':
            default:
                $Append = '';
                if(array_key_exists('maxsize', $P)) $Max = $P['maxsize'];
                if(array_key_exists('minsize', $P)) $Min = $P['minsize'];
        }

        if(!$this->validInt($V)) {
            return $this->err('Entered value for: \''.$F['Name'].'\''.$Append.' is not an Integer: \''.$V.'\'');
        }
        
        // Too Big
        if(isset($Max) && (int)$V > $Max) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too large. Maximum Size: '.$Max.', Entered: '.$V.$Append.'.');
        }
        // Too Small
        if(isset($Min) && (int)$V < $Min) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too small. Minimum Size: '.$Min.', Entered: '.$V.$Append.'.');
        }
        // Size does matter, after all
        return true;
    }
    
    private function validInt($Int, $AllowNegative = false) {
        // Best way to check for a true integer is regular expressions
        if($AllowNegative) $Regex = '/^-?[\d]+$/';
        else $Regex = '/^[\d]+$/';
        
        if(preg_match($Regex, (string)$Int)) return true;
        return false;
    }
    
    // Technically a float, not a number, but for the sake of ease/clarity
    private function validateNumber() {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
        
        // Again, regex ftw
        if(!preg_match('/^[\d]+\.?([\d]*)$/', $V, $DecimalCheck)) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is not a Number: \''.$V.'\'');
        }
        
        // Too many Decimal Places
        $DPs = strlen($DecimalCheck[1]);
        if(array_key_exists('maxdp', $P) && $DPs > $P['maxdp']) {
            return $this->err('Entered value for: \''.$F['Name'].'\' has too many Decimal Places. Maximum allowed: '.$P['maxdp'].', Entered: '.number_format($DPs).' ('.$V.')');
        }
        
        // Zu gross
        if(array_key_exists('maxsize', $P) && (float)$V > (float)$P['maxsize']) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too large. Maximum Size: '.(string)(float)$P['maxsize'].', Entered: '.$V.'.');
        }
        // Zu klein
        if(array_key_exists('minsize', $P) && (float)$V < (float)$P['minsize']) {
            return $this->err('Entered value for: \''.$F['Name'].'\' is too small. Minimum Size: '.(string)(float)$P['minsize'].', Entered: '.$V.'.');
        }
        // Er k�nnte etwas gr��er sein
        return true;
    }
    
    private function validateInArr() {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
        
        if(array_key_exists('inarray', $P) && is_array($P['inarray'])) {
            if(!in_array($V, $P['inarray'])) {
                return $this->err('Entered value for \''.$F['Name'].'\' ('.$V.') is not in the specified array:'.substr(print_r($P['inarray'],true),5));
            }
        } else {
            $this->Error('Type inarray specified, yet no array supplied.');
        }
        return true;
    }
    
    private function validateRegex($Type = 'regex') {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
        
        $Append = ' ('.ucfirst($Type).' Field)';
        switch($Type) {
            case 'username':
                $Regex = USERNAME_REGEX;
                break;
            case 'email':
                $Regex = EMAIL_REGEX;
                break;
            case 'regex':
            default:
            if(!array_key_exists('regex', $P) || empty($P['regex']) || !is_string($P['regex'])) {
                $this->Error('Type regex specified, yet regex not given.');
            }
            $Append = '';
            $Regex = $P['regex'];
        }
        if(!preg_match($Regex, $V)) {
            return $this->err('Entered value for \''.$F['Name'].'\' ('.$V.') does not match the regex given: '.$Regex.$Append);
        }
        return true;
    }
    
    private function validateCompare() {
        $F = $this->Field;
        $V = $this->Value;
        $P = $F['Parameters'];
        
        if(!array_key_exists('compare', $P) || empty($P['compare'])) {
            $this->Error('Type compare specified, yet no compare field given.');
        }
        if($V !== (string)$P['compare']) {
            return $this->err('Entered value for \''.$F['Name'].'\' ('.$V.') does not match supplied compare value ('.$P['compare'].').');
        }
        return true;
    }
    
    private function validateTrackID() {
        if(!Utility::validID($this->Value)) {
            return $this->err('An invalid track ID was entered for the field: ' . $this->Field . ' -  ' . $this->Value);
        }
        return true;
    }
    
    public function validDate($YearField, $MonthField, $DayField) {
        $D = $this->Data;
        if(!array_key_exists($YearField, $D) || empty($D[$YearField])) return $this->err('Year not found');
        if(!array_key_exists($MonthField, $D) || empty($D[$MonthField])) return $this->err('Month not found');
        if(!array_key_exists($DayField, $D) || empty($D[$DayField])) return $this->err('Day not found');
        $Year = $D[$YearField];
        $Month = $D[$MonthField];
        $Day = $D[$DayField];
        
        if(!checkDate($Month,$Day,$Year)) { //Let's find out why it's borked.
            if(!$this->validInt($Year)) return $this->err('Year is not a positive integer: '.(string)$Year);
            if(!$this->validInt($Month)) return $this->err('Month is not a positive integer: '.(string)$Month);
            if(!$this->validInt($Day)) return $this->err('Day is not a positive integer: '.(string)$Day);
            return $this->err('Invalid Date: '.(string)$Year.'/'.(string)$Month.'/'.(string)$Day);            
        }
        return true;
    }

    private function Error($Error) {
        if($this->DebugMode) {
            Core::niceError($Error);
        }
        // BOOM
        die('Fatal Validation Error');
    }
    
    private function err($Error) {
        $F = $this->Field;
        if(!$this->DebugMode) {
            $Error = $F['Error'];
        }
        $this->Errors[] = $Error;
        return $Error;
    }

    public function getErrors() {
        return $this->Errors;
    }
    
    private function debugParameters($P, $FN) {
        if(!$this->DebugMode) die('Fatal Validation Error');
        foreach($P as $ParamKey=>$V) {
            if(!in_array($ParamKey, $this->AvailableParameters)) {
                return $this->err('You have specified the parameter: \''.$ParamKey.'\', which cannot exist; under the field: \''.$FN.'\'. Please remove it.');
            }
        }
        return true;
    }
}

?>