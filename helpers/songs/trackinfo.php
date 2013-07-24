<?php

class Helper_Songs_Trackinfo extends CoreHelper {    
    public function run() {
        $trackID = $_GET['id'];
        
        $Val = new CoreValidate($_GET);
        $Val->val('id', 'trackid', true, "Invalid or missing Track ID");
        
        if($Err = $Val->getErrors()) {
            foreach($Err as $e) echo $e . "\n";
            die;
        }
        
        Core::get('DB')->query("SELECT
                        vl.trackid,
                        vl.addedBy,
                        u.Username,
                        vl.addedDate,
                        ti.Title,
                        ti.Artist,
                        ti.Album,
                        ti.Duration,
                        SUM(IF(v.updown, 1, -1)) as Votes
                    FROM voting_list AS vl
                    JOIN track_info AS ti
                        ON ti.trackid = vl.trackid
                    JOIN users AS u
                        ON u.ID = vl.addedBy
                    LEFT JOIN votes AS v
                        ON vl.trackid = v.trackid
                    WHERE vl.trackid = ?
                    GROUP BY vl.trackid",
                    array($trackID));
        
        if(Core::get('DB')->record_count() < 1) die('invalid');
        
        echo json_encode(Core::get('DB')->next_record(MYSQLI_ASSOC));
    }
}