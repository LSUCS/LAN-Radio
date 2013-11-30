<?php

class Helper_Mpd_Playerinfo extends CoreHelper {
    private function connect() {
        Core::requireLibrary('MPD');
        $this->MPD = new MPD(MPD_HOST, MPD_PORT, MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) {
            echo json_encode(array('error' => $this->MPD->errStr));
            die;
        }
        
    }
    
    public function run() {

        if(!$Return = Core::get('Cache')->get('current_song_info')) {
            $this->connect();
            if($this->MPD->state !== "play") {
                $Return = array('error' => "No Track Playing");
                $expire = 5;
            } else {
    
                $Return = array();
                $Return['position'] = $this->MPD->current_track_position;
                $Return['servertime'] = time();
                
                $Return['length'] = $this->MPD->current_track_length;
                $Return['track'] = $this->MPD->current_track_title;
                $Return['artist'] = $this->MPD->current_track_artist;
                $Return['album'] = $this->MPD->current_track_album;
                $Return['year'] = $this->MPD->current_track_year;
                $Return['file'] = $this->MPD->current_track_file;
                
                Core::get('DB')->query("SELECT votes, addedBy FROM history WHERE trackid = ? ORDER BY datePlayed DESC LIMIT 1", array($this->MPD->current_track_file));
                if(Core::get('DB')->record_count() == 0) {
                    $Return = array('error' => 'Kaboomboom', 'track' => $this->MPD->current_track_file);
                    $expire = 3;
                } else {
                    $Info = Core::get('DB')->next_record(MYSQLI_ASSOC);
                    
                    $Return['votes'] = $Info['votes'];
                    
                    $User = Model_User::loadFromID($Info['addedBy']);
                    $Return['avatar'] = $User->AvatarURL;
                    $Return['username'] = $User->Username;
                    
                    if($Return['length'] - $Return['position'] < 10) {
                        $expire = $Return['length'] - $Return['position'];
                    } else {
                        $expire = 10;
                    }
                    if($expire > 30 || $expire < 1) $expire = 30;
                }
            }
            
            Core::get('Cache')->set('current_song_info', $Return, $expire);
            
        } else {
            if(!isset($Return['error'])) {
                //Update the time
                $Return['position'] += time() - $Return['servertime'];
            }
        }
        echo json_encode($Return);
    }
}