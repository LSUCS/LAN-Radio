<?php

namespace Core\Helper\Mpd;

use \Core as Core;
use Core\Core as C;
use Core\Config;

class Search extends Core\Helper {
    private function connect() {
        C::loadLibrary('MPD/MPD.php');
        $this->MPD = new \MPD(Config::MPD_HOST, Config::MPD_PORT, Config::MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) $this->error($this->MPD->errStr);
    }
    
    public function run() {
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        $Libraries = (isset($_GET['libraries'])) ? $_GET['libraries'] : 'any';        
        
        $this->connect();
        $Results = $this->MPD->Find($Libraries, $_GET['search']);
        
        echo json_encode($Results);
    }
}