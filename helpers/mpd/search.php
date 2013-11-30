<?php

class Helper_Mpd_Search extends CoreHelper {
    private function connect() {
        Core::requireLibrary('MPD');
        $this->MPD = new MPD(MPD_HOST, MPD_PORT, MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) json_encode(array('error' => $this->MPD->errStr));
    }
    
    public function run() {
        $Libraries = (isset($_GET['libraries'])) ? $_GET['libraries'] : 'any';        
        
        $this->connect();
        $Results = $this->MPD->Find($Libraries, $_GET['search']);
        
        echo json_encode($Results);
    }
}