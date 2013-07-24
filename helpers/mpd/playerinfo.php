<?php

class Helper_Mpd_Playerinfo extends CoreHelper {
    private function connect() {
        Core::requireLibrary('MPD');
        $this->MPD = new MPD('localhost', 6600);
    
        if(!empty($this->MPD->errStr)) {
            json_encode(array('error' => $this->MPD->errStr));
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
                
                Core::get('DB')->query("SELECT 
                                vl.addedBy AS UserID,
                                u.Username,
                                u.Avatar,
                                SUM(IF(v.updown, 1, -1)) AS Votes 
                            FROM voting_list AS vl
                            LEFT JOIN votes AS v
                                ON vl.trackid = v.trackid
                            JOIN users AS u
                                ON u.ID = vl.addedBy 
                            WHERE vl.trackid = ?", array($Return['file']));
                if(Core::get('DB')->record_count() == 0) {
                    $Return = array('error' => 'Kaboomboom');
                    $expire = 3;
                } else {
                    $Info = Core::get('DB')->next_record(MYSQLI_ASSOC);
                
                    $Return['votes'] = $Info['Votes'];
                    $Return['avatar'] = $Info['Avatar'];
                    $Return['username'] = $Info['Username'];
                    
                    if($Return['length'] - $Return['position'] < 10) {
                        $expire = $Return['length'] - $Return['position'];
                    } else {
                        $expire = 10;
                    }
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