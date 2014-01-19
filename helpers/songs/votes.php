<?php

class Helper_Songs_Votes extends CoreHelper {    
    public function run() {
        ini_set('display_errors', true);
        error_reporting(E_ALL);
        
        $Val = new CoreValidate($_GET);
        
        $Val->val('id', 'trackid', true, "Invalid or missing Track ID");
        
        if($Err = $Val->getErrors()) {
            foreach($Err as $e) echo $e . "\n";
            die;
        }
        
        $trackID = $_GET['id'];
        
        if(!$voters = Core::get('Cache')->get('votes_' . $trackID)) {
            Core::get("DB")->query("SELECT * FROM votes WHERE trackid = ? ORDER BY time ASC", array($trackID));
            
            $voters = array();    
            while($vote = Core::get('DB')->next_record(MYSQLI_ASSOC)) {
                $voter = Model_User::loadFromID($vote['userid']);
                
                $voters[] = array(
                    'ID' => $voter->ID,
                    'Username' => $voter->username,
                    'Link' => Core::linkUser($voter),
                    'Vote' => ($vote['updown']) ? 'up' : 'down'
                );
            }
            
            Core::get('Cache')->set('votes_' . $trackID, $voters);
        }
  		Core::get('Template')->init('voters');
        Core::get('Template')->set("VOTERS", $voters, true);
        
		Core::get('Template')->push();
    }
}