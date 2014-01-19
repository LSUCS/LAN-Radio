<?php

namespace Core\Helper\Songs;

use \Core as Core;
use Core\Core as C;
use Core\Validate;
use Core\Cache;
use Core\Model\User;

class Votes extends Core\Helper {
    public function run() {
        $Val = new Validate($_GET);
        
        $Val->val('id', 'trackid', true, "Invalid or missing Track ID");
        
        if($Err = $Val->getErrors()) {
            foreach($Err as $e) echo $e . "\n";
            die;
        }
        
        $trackID = $_GET['id'];
        
        if(!$voters = Cache::get('votes_' . $trackID)) {
            C::get("DB")->query("SELECT * FROM votes WHERE trackid = ? ORDER BY time ASC", array($trackID));
            
            $voters = array();    
            while($vote = C::get('DB')->next_record(MYSQLI_ASSOC)) {
                $voter = new User($vote['userid']);
                
                $voters[] = array(
                    'ID' => $voter->ID,
                    'Username' => $voter->username,
                    'Link' => $voter->link(),
                    'Vote' => ($vote['updown']) ? 'up' : 'down'
                );
            }
            
            Cache::set('votes_' . $trackID, $voters);
        }
  		C::get('Template')->init('voters');
        C::get('Template')->set("VOTERS", $voters, true);
        
		C::get('Template')->push();
    }
}