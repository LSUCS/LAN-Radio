<?php

class Helper_Songs_Songs extends CoreHelper {    
    public function run() {
        //Load the active voting list
        Core::get("DB")->query("SELECT * FROM songlist");
        $VotingTracks = Core::get("DB")->to_array(false, MYSQL_ASSOC);
                
        //Load the users' votes
        Core::get("DB")->query("SELECT trackid, updown FROM votes WHERE userid = " . $this->parent->LoggedUser->ID);
        $this->UserVotes = Core::get("DB")->to_array('trackid', MYSQLI_ASSOC);
        
  		Core::get('Template')->init('table');
        
        if(count($VotingTracks)) {
            $a = 'even';
            $counter = 0;
            $Songs = array();
            //Store users so we don't have to get them multiple times
            $Users = array();
            foreach($VotingTracks as $VT) {
                $counter++;
                $parity = ($counter % 2 == 0) ? 'even' : 'odd';
                
                if(!array_key_exists($VT['addedBy'], $Users)) {
                    $Users[$VT['addedBy']] = Model_User::loadFromID($VT['addedBy']);
                }
                
                $Songs[] = array(
                    'ID' => $VT['trackid'],
                    'SOURCE' => Core::getSource($VT['trackid']),
                    'COUNT' => $counter,
                    'TITLE' => Core::displayStr($VT['Title']),
                    'ARTIST' => Core::displayStr($VT['Artist']),
                    'DURATION' => Core::get_time($VT['Duration']),
                    'ALBUM' => Core::displayStr($VT['Album']),
                    'ADDEDBY' => Core::linkUser($Users[$VT['addedBy']]),
                    'SCORE' => $VT['Score'],
                    'PARITY' => $parity,
                    'UPCOLOUR' => $this->getColour(1, $VT['trackid']),
                    'DOWNCOLOUR' => $this->getColour(0, $VT['trackid'])
                );   
            }
            
            Core::get('Template')->set("TABLE_ROWS", true, true);
            Core::get('Template')->set("SONGS", $Songs);
        } else {
            Core::get('Template')->set("TABLE_ROWS", false, true);
        }
        
		Core::get('Template')->push();
    }
    
    private function getColour($Direction, $ID) {
        if(array_key_exists($ID, $this->UserVotes)) {
            if($this->UserVotes[$ID]['updown'] == '1') {
                if($Direction) {
                    return '-green';
                }
            } else {
                if(!$Direction) {
                    return '-red';
                }
            }
        }
        return '';
    }
}