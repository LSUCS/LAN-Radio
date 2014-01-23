<?php

namespace Core\Helper\Mpd;

use \Core as Core;
use Core\Core as C;
use Core\Config;

class Command extends Core\Helper {
    private $MPD = null;
    
    private function connect() {
        C::loadLibrary('MPD/MPD.php');
        $this->MPD = new \MPD(Config::MPD_HOST, Config::MPD_PORT, Config::MPD_PASSWORD);
    
        if(!empty($this->MPD->errStr)) $this->error($this->MPD->errStr);
    }
    
    public function run() {
        $command = $this->arguments[0];
        $method = 'run_' . $command;
        
        if(!method_exists($this, $method)) $this->error('Invalid Command');
        
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