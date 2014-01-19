<?php

namespace Core\Controller;

use Core;

class Index extends Core\Controller {
    public function action_index() {
        $this->showView('songs');
    }
    
    public function action_test() {
        $trackID = "spotify:track:4Va3HTCerVUFC0kAcIIFLj";
        
        if(strstr($trackID, 'spotify')) {
            
            $data = $this->lookupJSON('http://ws.spotify.com/lookup/1/.json?uri=' . $trackID);
            
            $Track = array(
                'Title' => $data->track->name,
                'Artist' => $data->track->artists[0]->name,
                'Album' => $data->track->album->name,
                'Time' => $data->track->length
            );
        } else {

            $data = $this->lookupJSON($trackID . '?alt=json');
            
            //Youtube API is annoying
            $t = '$t';
            $group = 'media$group';
            $duration = 'yt$duration';
            
            $Track = array(
                'Title' => $data->entry->title->$t,
                'Artist' => $data->entry->author[0]->name->$t,
                'Album' => 'n/a',
                'Time' => $data->entry->$group->$duration->seconds
            );
        }
        var_dump($Track);
    }
    function lookupJSON($URL, $Decode=true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        
        //$data = file_get_contents($URL);
        
        //Track doesn't exist. C'est IMPOSSIBLÉ!!
        if(!$data) die('invalid');
        
        if($Decode) $data = json_decode($data);
        
        return $data;
    }
}