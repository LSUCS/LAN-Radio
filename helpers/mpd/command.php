<?php

class Helper_Mpd_Command extends CoreHelper {
    private $MPD = null;
    
    private function connect() {
        Core::requireLibrary('MPD');
        $this->MPD = new MPD(MPD_HOST, MPD_PORT);
    
        if(!empty($this->MPD->errStr)) $this->jsonError($this->MPD->errStr);
    }
    
    private function jsonError($err) {
        echo json_encode(array('error' => $err));
        exit;
    }
    
    public function run() {
        $command = $this->arguments[0];
        $method = 'run_' . $command;
        
        if(!method_exists($this, $method)) $this->jsonError('Invalid Command');
        
        $this->connect();
        call_user_func(array($this, $method), $this->arguments[1]);
    }
    
    private function run_status() {
        $Return = array(
            'volume' => $this->MPD->volume,
            'repeat' => $this->MPD->repeat,
            'random' => $this->MPD->random,
            #'single' => $this->MPD->single,
            #'consume' => $this->MPD->consume,
            'playlist' => $this->MPD->playlist,
            'playlistlength' => $this->MPD->playlist_count,
            #'xfade' => $this->MPD->xfade,
            'state' => $this->MPD->state,
            #'song' => $this->MPD->song,
            #'songid' => $this->MPD->songid,
            #'time' => $this->MPD->time,
            #'elapsed' => $this->MPD->elapsed,
            #'bitrate' => $this->MPD->bitrate,
            
            'uptime' => $this->MPD->uptime,
            'db_update' => $this->MPD->db_last_refreshed,
            'artists' => $this->MPD->num_artists,
            'playtime' => $this->MPD->playtime,
            'albums' => $this->MPD->num_albums,
            #'db_playtime' => $this->MPD->db_playtime,
            'songs' => $this->MPD->num_songs
        );
        
        echo json_encode($Return);
    }
    
    private function run_play() {
        if($this->MPD->state !== "play") $this->MPD->Play();
    }
    
    private function run_pause() {
        $this->MPD->Pause();
    }
    
    private function run_stop() {
        $this->MPD->Stop();
    }
    
    private function run_next() {
        $this->MPD->Next();
    }
    
    private function run_changevolume($volume) {
        $this->MPD->SetVolume($volume);
    }
    
    private function run_add($trackid) {
        $this->MPD->PLAdd($trackid);
    }
}