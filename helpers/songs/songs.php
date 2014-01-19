<?php

namespace Core\Helper\Songs;

use \Core as Core;
use Core\Core as C;
use Core\Utility;
use Core\Model\User;
use Core\Session;

class Songs extends Core\Helper { 
    public function run() {
        //Load the active voting list
        C::get("DB")->query("SELECT * FROM songlist");
        $VotingTracks = C::get("DB")->to_array(false, MYSQL_ASSOC);
                
        //Load the users' votes
        C::get("DB")->query("SELECT trackid, updown FROM votes WHERE userid = ?", Session::getUser()->ID);
        $this->UserVotes = C::get("DB")->to_array('trackid', MYSQLI_ASSOC);
        
  		C::get('Template')->init('table');
        
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
                    $Users[$VT['addedBy']] = new User($VT['addedBy']);
                }
                
                $Songs[] = array(
                    'ID' => $VT['trackid'],
                    'SOURCE' => Utility::getSource($VT['trackid']),
                    'COUNT' => $counter,
                    'TITLE' => Utility::displayStr($VT['Title']),
                    'ARTIST' => Utility::displayStr($VT['Artist']),
                    'DURATION' => Utility::get_time($VT['Duration']),
                    'ALBUM' => Utility::displayStr($VT['Album']),
                    'ADDEDBY' => $Users[$VT['addedBy']]->link(),
                    'SCORE' => $VT['Score'],
                    'PARITY' => $parity,
                    'UPCOLOUR' => $this->getColour(1, $VT['trackid']),
                    'DOWNCOLOUR' => $this->getColour(0, $VT['trackid'])
                );   
            }
            
            C::get('Template')->set("TABLE_ROWS", true, true);
            C::get('Template')->set("SONGS", $Songs);
        } else {
            C::get('Template')->set("TABLE_ROWS", false, true);
        }
        
		C::get('Template')->push();
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