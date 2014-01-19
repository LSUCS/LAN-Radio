<?php

namespace Core\Helper\Mpd;

use \Core as Core;
use Core\Cache;
use Core\Core as C;
use Core\Model\User;

class Playerinfo extends Core\Helper {
    private function connect() {
        $this->MPD = new MPD_MPD(MPD_HOST, MPD_PORT, MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) {
            $this->error($this->MPD->errStr);
        }
        
    }
    
    public function run() {
        if(!$Return = Cache::get('current_song_info')) {
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
                
                C::get('DB')->query("SELECT votes, addedBy FROM history ORDER BY datePlayed DESC LIMIT 1", $this->MPD->current_track_file);
                if(C::get('DB')->record_count() == 0) {
                    $Return = array('error' => 'Kaboomboom', 'track' => $this->MPD->current_track_file);
                    $expire = 3;
                } else {
                    $Info = C::get('DB')->next_record(MYSQLI_ASSOC);
                    
                    $Return['votes'] = $Info['votes'];
                    
                    $User = new User($Info['addedBy']);
                    $Return['avatar'] = $User->avatarURL;
                    $Return['username'] = $User->username;
                    
                    if($Return['length'] - $Return['position'] < 10) {
                        $expire = $Return['length'] - $Return['position'];
                    } else {
                        $expire = 10;
                    }
                    if($expire > 30 || $expire < 1) $expire = 30;
                }
            }
            
            Cache::set('current_song_info', $Return, $expire);
            
        } else {
            if(!isset($Return['error'])) {
                //Update the time
                $Return['position'] += time() - $Return['servertime'];
            }
        }
        echo json_encode($Return);
    }
}