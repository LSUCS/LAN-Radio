<?php

namespace Core\Helper\Songs;

use \Core as Core;
use Core\Core as C;
use Core\Validate;
use Core\Utiltiy;
use Core\Model\User;

class Trackinfo extends Core\Helper {
    public function run() {
        $trackID = $_GET['id'];
        
        $Val = Validate($_GET);
        $Val->val('id', 'trackid', true, "Invalid or missing Track ID");
        
        if($Err = $Val->getErrors()) {
            foreach($Err as $e) echo $e . "\n";
            die;
        }
        C::get('DB')->query("SELECT
                        vl.trackid,
                        vl.addedBy,
                        vl.addedDate,
                        ti.Title,
                        ti.Artist,
                        ti.Album,
                        ti.Duration,
                        SUM(IF(v.updown, 1, -1)) as Votes
                    FROM voting_list AS vl
                    JOIN track_info AS ti
                        ON ti.trackid = vl.trackid
                    LEFT JOIN votes AS v
                        ON vl.trackid = v.trackid
                    WHERE vl.trackid = ?
                    GROUP BY vl.trackid",
                    $trackID);
        
        if(C::get('DB')->record_count() < 1) die('invalid');
        
        $TrackInfo = C::get('DB')->next_record(MYSQLI_ASSOC);
        
        $User = new User($TrackInfo['addedBy']);
        
        $T = C::get('Template');
        $T->init('trackinfo');
        $T->set('Title', $TrackInfo['Title']);
        $T->set('Artist', $TrackInfo['Artist']);
        $T->set('Album', $TrackInfo['Album']);
        $T->set('Duration', Utiltiy::get_time($TrackInfo['Duration']));
        $T->set('Votes', $TrackInfo['Votes']);
        $T->set('Username', $User->username);
        $T->set('UserID', $User->ID);
        $T->set('Avatar', $User->avatarURL);
        
        switch(Utiltiy::getSource($trackID)) {
            case 'youtube':
                $code = array_pop(explode('/', $trackID));
                $tag = '<iframe width="560" height="315" src="http://www.youtube.com/embed/' . $code . '" frameborder="0" allowfullscreen></iframe>';
                break;
            case 'spotify':
                $tag = '<iframe src="https://embed.spotify.com/?uri=' . $trackID . '" width="250" height="330" frameborder="0" allowtransparency="true"></iframe>';
                break; 
        }
        $T->set('EMBED_TAG', $tag);
        
        ob_get_clean();
        ob_start();
        C::get('Template')->push();
        
        $info = ob_get_contents();
        ob_end_clean();

        $title = $TrackInfo['Title'] . ' - ' . $TrackInfo['Artist'];
        $output = array(
            'Title' => $title,
            'Info' => $info
        );

        echo json_encode($output);       
    }
}