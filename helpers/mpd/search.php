<?php

namespace Core\Helper\Mpd;

use \Core as Core;;

class Search extends Core\Helper {
    private function connect() {
        Core::requireLibrary('MPD');
        $this->MPD = new MPD_MPD(MPD_HOST, MPD_PORT, MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) $this->error($this->MPD->errStr);
    }
    
    public function run() {
        $Libraries = (isset($_GET['libraries'])) ? $_GET['libraries'] : 'any';        
        
        $this->connect();
        $Results = $this->MPD->Find($Libraries, $_GET['search']);
        
        echo json_encode($Results);
    }
}