<?php

/*
*  mpd.class.php - PHP Object Interface to the MPD Music Player Daemon
*  Version 1.2, Released 05/05/2004
*  Copyright (C) 2003-2004  Benjamin Carlisle (bcarlisle@24oz.com)
*  http://mpd.24oz.com/ | http://www.musicpd.org/
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Create common command definitions for MPD to use
define("MPD_CMD_STATUS", "status");
define("MPD_CMD_STATISTICS", "stats");
define("MPD_CMD_VOLUME", "volume");
define("MPD_CMD_SETVOL", "setvol");
define("MPD_CMD_PLAY", "play");
define("MPD_CMD_STOP", "stop");
define("MPD_CMD_PAUSE", "pause");
define("MPD_CMD_NEXT", "next");
define("MPD_CMD_PREV", "previous");
define("MPD_CMD_PLLIST", "playlistinfo");
define("MPD_CMD_PLADD", "add");
define("MPD_CMD_PLMOVE", "move");
define("MPD_CMD_PLREMOVE", "delete");
define("MPD_CMD_PLCLEAR", "clear");
define("MPD_CMD_PLSHUFFLE", "shuffle");
define("MPD_CMD_PLLOAD", "load");
define("MPD_CMD_PLSAVE", "save");
define("MPD_CMD_KILL", "kill");
define("MPD_CMD_REFRESH", "update");
define("MPD_CMD_REPEAT", "repeat");
define("MPD_CMD_LSDIR", "lsinfo");
define("MPD_CMD_SEARCH", "search");
define("MPD_CMD_START_BULK", "command_list_begin");
define("MPD_CMD_END_BULK", "command_list_end");
define("MPD_CMD_FIND", "libfind");
define("MPD_CMD_RANDOM", "random");
define("MPD_CMD_SEEK", "seek");
define("MPD_CMD_PLSWAPTRACK", "swap");
define("MPD_CMD_PLMOVETRACK", "move");
define("MPD_CMD_PASSWORD", "password");
define("MPD_CMD_TABLE", "list");
define("MPD_CMD_CURRENTSONG", "currentsong");

// Predefined MPD Response messages
define("MPD_RESPONSE_ERR", "ACK");
define("MPD_RESPONSE_OK", "OK");

// MPD State Constants
define("MPD_STATE_PLAYING", "play");
define("MPD_STATE_STOPPED", "stop");
define("MPD_STATE_PAUSED", "pause");

// MPD Searching Constants
define("MPD_SEARCH_ARTIST", "artist");
define("MPD_SEARCH_TITLE", "title");
define("MPD_SEARCH_ALBUM", "album");
define("MPD_SEARCH_ANY", "any");

// MPD Cache Tables
define("MPD_TBL_ARTIST", "artist");
define("MPD_TBL_ALBUM", "album");

class MPD {
    // TCP/Connection variables
    var $host;
    var $port;
    var $password;

    var $mpd_sock = null;
    var $connected = false;

    // MPD Status variables
    var $mpd_version = "(unknown)";

    var $state;
    var $current_track_position;
    var $current_track_length;
    var $current_track_id;
    var $current_track_title;
    var $current_track_artist;
    var $current_track_album;
    var $current_track_file;
    var $current_track_year;
    var $volume;
    var $repeat;
    var $random;

    var $uptime;
    var $playtime;
    var $db_last_refreshed;
    var $num_songs_played;
    var $playlist_count;

    var $num_artists;
    var $num_albums;
    var $num_songs;

    var $playlist = array();

    // Misc Other Vars
    var $mpd_class_version = "1.2";

    var $debugging = false; // Set to TRUE to turn extended debugging on.
    var $errStr = ""; // Used for maintaining information about the last error message

    var $command_queue; // The list of commands for bulk command sending

    // =================== BEGIN OBJECT METHODS ================

    /* mpd() : Constructor
    * 
    * Builds the MPD object, connects to the server, and refreshes all local object properties.
    */
    function __construct($srv, $port, $pwd = null) {
        $this->host = $srv;
        $this->port = $port;
        $this->password = $pwd;

        $resp = $this->Connect();
        if(is_null($resp)) {
            $this->errStr = "Could not connect";
            return;
        } else {
            list($this->mpd_version) = sscanf($resp, MPD_RESPONSE_OK . " MPD %s\n");
            if(!is_null($pwd)) {
                if(is_null($this->SendCommand(MPD_CMD_PASSWORD, $pwd))) {
                    $this->connected = false;
                    return; // bad password or command
                }
                if(is_null($this->RefreshInfo())) { // no read access -- might as well be disconnected!
                    $this->connected = false;
                    $this->errStr = "Password supplied does not have read access";
                    return;
                }
            } else {
                if(is_null($this->RefreshInfo())) { // no read access -- might as well be disconnected!
                    $this->connected = false;
                    $this->errStr = "Password required to access server";
                    return;
                }
            }
        }
    }

    /* Connect()
    * 
    * Connects to the MPD server. 
    * 
    * NOTE: This is called automatically upon object instantiation; you should not need to call this directly.
    */
    function Connect() {
        if($this->debugging)
            echo "mpd->Connect() / host: " . $this->host . ", port: " . $this->port . "\n";
        
        $this->mpd_sock = fsockopen($this->host, $this->port, $errNo, $errStr, 10);
        if(!$this->mpd_sock) {
            $this->errStr = "Socket Error: $errStr ($errNo)";
            return null;
        } else {
            while(!feof($this->mpd_sock)) {
                $response = fgets($this->mpd_sock, 1024);
                if(strncmp(MPD_RESPONSE_OK, $response, strlen(MPD_RESPONSE_OK)) == 0) {
                    $this->connected = true;
                    return $response;
                    break;
                }
                if(strncmp(MPD_RESPONSE_ERR, $response, strlen(MPD_RESPONSE_ERR)) == 0) {
                    $this->errStr = "Server responded with: $response";
                    return null;
                }
            }
            // Generic response
            $this->errStr = "Connection not available";
            return null;
        }
    }

    /* SendCommand()
    * 
    * Sends a generic command to the MPD server. Several command constants are pre-defined for 
    * use (see MPD_CMD_* constant definitions above). 
    */
    function SendCommand($cmdStr, $arg1 = "", $arg2 = "") {
        if($this->debugging)
            echo "mpd->SendCommand() / cmd: " . $cmdStr . ", args: " . $arg1 . " " . $arg2 .
                "\n";
        if(!$this->connected) {
            echo "mpd->SendCommand() / Error: Not connected\n";
        } else {
            // Clear out the error String
            $this->errStr = "";
            $respStr = "";

            // Check the command compatibility:
            if(!$this->_checkCompatibility($cmdStr)) {
                return null;
            }

            if(strlen($arg1) > 0)
                $cmdStr .= " \"$arg1\"";
            if(strlen($arg2) > 0)
                $cmdStr .= " \"$arg2\"";
            fputs($this->mpd_sock, "$cmdStr\n");
            while(!feof($this->mpd_sock)) {
                $response = fgets($this->mpd_sock, 1024);

                // An OK signals the end of transmission -- we'll ignore it
                if(strncmp(MPD_RESPONSE_OK, $response, strlen(MPD_RESPONSE_OK)) == 0) {
                    break;
                }

                // An ERR signals the end of transmission with an error! Let's grab the single-line message.
                if(strncmp(MPD_RESPONSE_ERR, $response, strlen(MPD_RESPONSE_ERR)) == 0) {
                    list($junk, $errTmp) = explode(MPD_RESPONSE_ERR . " ", $response);
                    $this->errStr = strtok($errTmp, "\n");
                }

                if(strlen($this->errStr) > 0) {
                    return null;
                }

                // Build the response string
                $respStr .= $response;
            }
            if($this->debugging)
                echo "mpd->SendCommand() / response: '" . $respStr . "'\n";
        }
        return $respStr;
    }

    /* QueueCommand()
    *
    * Queues a generic command for later sending to the MPD server. The CommandQueue can hold 
    * as many commands as needed, and are sent all at once, in the order they are queued, using 
    * the SendCommandQueue() method. The syntax for queueing commands is identical to SendCommand(). 
    */
    function QueueCommand($cmdStr, $arg1 = "", $arg2 = "") {
        if($this->debugging)
            echo "mpd->QueueCommand() / cmd: " . $cmdStr . ", args: " . $arg1 . " " . $arg2 .
                "\n";
        if(!$this->connected) {
            echo "mpd->QueueCommand() / Error: Not connected\n";
            return null;
        } else {
            if(strlen($this->command_queue) == 0) {
                $this->command_queue = MPD_CMD_START_BULK . "\n";
            }
            if(strlen($arg1) > 0)
                $cmdStr .= " \"$arg1\"";
            if(strlen($arg2) > 0)
                $cmdStr .= " \"$arg2\"";

            $this->command_queue .= $cmdStr . "\n";

            if($this->debugging)
                echo "mpd->QueueCommand() / return\n";
        }
        return true;
    }

    /* SendCommandQueue()
    *
    * Sends all commands in the Command Queue to the MPD server. See also QueueCommand().
    */
    function SendCommandQueue() {
        if($this->debugging)
            echo "mpd->SendCommandQueue()\n";
        if(!$this->connected) {
            echo "mpd->SendCommandQueue() / Error: Not connected\n";
            return null;
        } else {
            $this->command_queue .= MPD_CMD_END_BULK . "\n";
            if(is_null($respStr = $this->SendCommand($this->command_queue))) {
                return null;
            } else {
                $this->command_queue = null;
                if($this->debugging)
                    echo "mpd->SendCommandQueue() / response: '" . $respStr . "'\n";
            }
        }
        return $respStr;
    }

    /* AdjustVolume()
    *
    * Adjusts the mixer volume on the MPD by <modifier>, which can be a positive (volume increase),
    * or negative (volume decrease) value. 
    */
    function AdjustVolume($modifier) {
        if($this->debugging)
            echo "mpd->AdjustVolume()\n";
        if(!is_numeric($modifier)) {
            $this->errStr = "AdjustVolume() : argument 1 must be a numeric value";
            return null;
        }

        $this->RefreshInfo();
        $newVol = $this->volume + $modifier;
        $ret = $this->SetVolume($newVol);

        if($this->debugging)
            echo "mpd->AdjustVolume() / return\n";
        return $ret;
    }

    /* SetVolume()
    *
    * Sets the mixer volume to <newVol>, which should be between 1 - 100.
    */
    function SetVolume($newVol) {
        if($this->debugging)
            echo "mpd->SetVolume()\n";
        if(!is_numeric($newVol)) {
            $this->errStr = "SetVolume() : argument 1 must be a numeric value";
            return null;
        }

        // Forcibly prevent out of range errors
        if($newVol < 0)
            $newVol = 0;
        if($newVol > 100)
            $newVol = 100;

        // If we're not compatible with SETVOL, we'll try adjusting using VOLUME
        if($this->_checkCompatibility(MPD_CMD_SETVOL)) {
            if(!is_null($ret = $this->SendCommand(MPD_CMD_SETVOL, $newVol)))
                $this->volume = $newVol;
        } else {
            $this->RefreshInfo(); // Get the latest volume
            if(is_null($this->volume)) {
                return null;
            } else {
                $modifier = ($newVol - $this->volume);
                if(!is_null($ret = $this->SendCommand(MPD_CMD_VOLUME, $modifier)))
                    $this->volume = $newVol;
            }
        }

        if($this->debugging)
            echo "mpd->SetVolume() / return\n";
        return $ret;
    }

    /* GetDir()
    * 
    * Retrieves a database directory listing of the <dir> directory and places the results into
    * a multidimensional array. If no directory is specified, the directory listing is at the 
    * base of the MPD music path. 
    */
    function GetDir($dir = "") {
        if($this->debugging)
            echo "mpd->GetDir()\n";
        $resp = $this->SendCommand(MPD_CMD_LSDIR, $dir);
        $dirlist = $this->_parseFileListResponse($resp);
        if($this->debugging)
            echo "mpd->GetDir() / return " . print_r($dirlist) . "\n";
        return $dirlist;
    }

    /* PLAdd()
    * 
    * Adds each track listed in a single-dimensional <trackArray>, which contains filenames 
    * of tracks to add, to the end of the playlist. This is used to add many, many tracks to 
    * the playlist in one swoop.
    */
    function PLAddBulk($trackArray) {
        if($this->debugging)
            echo "mpd->PLAddBulk()\n";
        $num_files = count($trackArray);
        for ($i = 0; $i < $num_files; $i++) {
            $this->QueueCommand(MPD_CMD_PLADD, $trackArray[$i]);
        }
        $resp = $this->SendCommandQueue();
        $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLAddBulk() / return\n";
        return $resp;
    }

    /* PLAdd()
    * 
    * Adds the file <file> to the end of the playlist. <file> must be a track in the MPD database. 
    */
    function PLAdd($fileName) {
        if($this->debugging)
            echo "mpd->PLAdd()\n";
        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLADD, $fileName)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLAdd() / return\n";
        return $resp;
    }

    /* PLMoveTrack()
    * 
    * Moves track number <origPos> to position <newPos> in the playlist. This is used to reorder 
    * the songs in the playlist.
    */
    function PLMoveTrack($origPos, $newPos) {
        if($this->debugging)
            echo "mpd->PLMoveTrack()\n";
        if(!is_numeric($origPos)) {
            $this->errStr = "PLMoveTrack(): argument 1 must be numeric";
            return null;
        }
        if($origPos < 0 or $origPos > $this->playlist_count) {
            $this->errStr = "PLMoveTrack(): argument 1 out of range";
            return null;
        }
        if($newPos < 0)
            $newPos = 0;
        if($newPos > $this->playlist_count)
            $newPos = $this->playlist_count;

        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLMOVETRACK, $origPos, $newPos)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLMoveTrack() / return\n";
        return $resp;
    }

    /* PLShuffle()
    * 
    * Randomly reorders the songs in the playlist.
    */
    function PLShuffle() {
        if($this->debugging)
            echo "mpd->PLShuffle()\n";
        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLSHUFFLE)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLShuffle() / return\n";
        return $resp;
    }

    /* PLLoad()
    * 
    * Retrieves the playlist from <file>.m3u and loads it into the current playlist. 
    */
    function PLLoad($file) {
        if($this->debugging)
            echo "mpd->PLLoad()\n";
        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLLOAD, $file)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLLoad() / return\n";
        return $resp;
    }

    /* PLSave()
    * 
    * Saves the playlist to <file>.m3u for later retrieval. The file is saved in the MPD playlist
    * directory.
    */
    function PLSave($file) {
        if($this->debugging)
            echo "mpd->PLSave()\n";
        $resp = $this->SendCommand(MPD_CMD_PLSAVE, $file);
        if($this->debugging)
            echo "mpd->PLSave() / return\n";
        return $resp;
    }

    /* PLClear()
    * 
    * Empties the playlist.
    */
    function PLClear() {
        if($this->debugging)
            echo "mpd->PLClear()\n";
        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLCLEAR)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLClear() / return\n";
        return $resp;
    }

    /* PLRemove()
    * 
    * Removes track <id> from the playlist.
    */
    function PLRemove($id) {
        if($this->debugging)
            echo "mpd->PLRemove()\n";
        if(!is_numeric($id)) {
            $this->errStr = "PLRemove() : argument 1 must be a numeric value";
            return null;
        }
        if(!is_null($resp = $this->SendCommand(MPD_CMD_PLREMOVE, $id)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->PLRemove() / return\n";
        return $resp;
    }

    /* SetRepeat()
    * 
    * Enables 'loop' mode -- tells MPD continually loop the playlist. The <repVal> parameter 
    * is either 1 (on) or 0 (off).
    */
    function SetRepeat($repVal) {
        if($this->debugging)
            echo "mpd->SetRepeat()\n";
        $rpt = $this->SendCommand(MPD_CMD_REPEAT, $repVal);
        $this->repeat = $repVal;
        if($this->debugging)
            echo "mpd->SetRepeat() / return\n";
        return $rpt;
    }

    /* SetRandom()
    * 
    * Enables 'randomize' mode -- tells MPD to play songs in the playlist in random order. The
    * <rndVal> parameter is either 1 (on) or 0 (off).
    */
    function SetRandom($rndVal) {
        if($this->debugging)
            echo "mpd->SetRandom()\n";
        $resp = $this->SendCommand(MPD_CMD_RANDOM, $rndVal);
        $this->random = $rndVal;
        if($this->debugging)
            echo "mpd->SetRandom() / return\n";
        return $resp;
    }

    /* Shutdown()
    * 
    * Shuts down the MPD server (aka sends the KILL command). This closes the current connection, 
    * and prevents future communication with the server. 
    */
    function Shutdown() {
        if($this->debugging)
            echo "mpd->Shutdown()\n";
        $resp = $this->SendCommand(MPD_CMD_SHUTDOWN);

        $this->connected = false;
        unset($this->mpd_version);
        unset($this->errStr);
        unset($this->mpd_sock);

        if($this->debugging)
            echo "mpd->Shutdown() / return\n";
        return $resp;
    }

    /* DBRefresh()
    * 
    * Tells MPD to rescan the music directory for new tracks, and to refresh the Database. Tracks 
    * cannot be played unless they are in the MPD database.
    */
    function DBRefresh() {
        if($this->debugging)
            echo "mpd->DBRefresh()\n";
        $resp = $this->SendCommand(MPD_CMD_REFRESH);

        // Update local variables
        $this->RefreshInfo();

        if($this->debugging)
            echo "mpd->DBRefresh() / return\n";
        return $resp;
    }

    /* Play()
    * 
    * Begins playing the songs in the MPD playlist. 
    */
    function Play() {
        if($this->debugging)
            echo "mpd->Play()\n";
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_PLAY)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->Play() / return\n";
        return $rpt;
    }

    /* Stop()
    * 
    * Stops playing the MPD. 
    */
    function Stop() {
        if($this->debugging)
            echo "mpd->Stop()\n";
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_STOP)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->Stop() / return\n";
        return $rpt;
    }

    /* Pause()
    * 
    * Toggles pausing on the MPD. Calling it once will pause the player, calling it again
    * will unpause. 
    */
    function Pause() {
        if($this->debugging)
            echo "mpd->Pause()\n";
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_PAUSE)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->Pause() / return\n";
        return $rpt;
    }

    /* SeekTo()
    * 
    * Skips directly to the <idx> song in the MPD playlist. 
    */
    function SkipTo($idx) {
        if($this->debugging)
            echo "mpd->SkipTo()\n";
        if(!is_numeric($idx)) {
            $this->errStr = "SkipTo() : argument 1 must be a numeric value";
            return null;
        }
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_PLAY, $idx)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->SkipTo() / return\n";
        return $idx;
    }

    /* SeekTo()
    * 
    * Skips directly to a given position within a track in the MPD playlist. The <pos> argument,
    * given in seconds, is the track position to locate. The <track> argument, if supplied is
    * the track number in the playlist. If <track> is not specified, the current track is assumed.
    */
    function SeekTo($pos, $track = -1) {
        if($this->debugging)
            echo "mpd->SeekTo()\n";
        if(!is_numeric($pos)) {
            $this->errStr = "SeekTo() : argument 1 must be a numeric value";
            return null;
        }
        if(!is_numeric($track)) {
            $this->errStr = "SeekTo() : argument 2 must be a numeric value";
            return null;
        }
        if($track == -1) {
            $track = $this->current_track_id;
        }

        if(!is_null($rpt = $this->SendCommand(MPD_CMD_SEEK, $track, $pos)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->SeekTo() / return\n";
        return $pos;
    }

    /* Next()
    * 
    * Skips to the next song in the MPD playlist. If not playing, returns an error. 
    */
    function Next() {
        if($this->debugging)
            echo "mpd->Next()\n";
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_NEXT)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->Next() / return\n";
        return $rpt;
    }

    /* Previous()
    * 
    * Skips to the previous song in the MPD playlist. If not playing, returns an error. 
    */
    function Previous() {
        if($this->debugging)
            echo "mpd->Previous()\n";
        if(!is_null($rpt = $this->SendCommand(MPD_CMD_PREV)))
            $this->RefreshInfo();
        if($this->debugging)
            echo "mpd->Previous() / return\n";
        return $rpt;
    }

    /* Search()
    * 
    * Searches the MPD database. The search <type> should be one of the following: 
    *        MPD_SEARCH_ARTIST, MPD_SEARCH_TITLE, MPD_SEARCH_ALBUM
    * The search <string> is a case-insensitive locator string. Anything that contains 
    * <string> will be returned in the results. 
    */
    function Search($type, $string) {
        if($this->debugging)
            echo "mpd->Search()\n";
        if(!in_array($type, array(
            MPD_SEARCH_ARTIST,
            MPD_SEARCH_ALBUM,
            MPD_SEARCH_TITLE,
            MPD_SEARCH_ANY))) {
            $this->errStr = "mpd->Search(): invalid search type";
            return null;
        } else {
            if(is_null($resp = $this->SendCommand(MPD_CMD_SEARCH, $type, $string)))
                return null;
            $searchlist = $this->_parseFileListResponse($resp);
        }
        if($this->debugging)
            echo "mpd->Search() / return " . print_r($searchlist) . "\n";
        return $searchlist;
    }

    /* Find()
    * 
    * Find() looks for exact matches in the MPD database. The find <type> should be one of 
    * the following: 
    *         MPD_SEARCH_ARTIST, MPD_SEARCH_TITLE, MPD_SEARCH_ALBUM
    * The find <string> is a case-insensitive locator string. Anything that exactly matches 
    * <string> will be returned in the results. 
    */
    function Find($type, $string) {
        if($this->debugging)
            echo "mpd->Find()\n";
        /*if(!in_array($type, array(
            MPD_SEARCH_ARTIST,
            MPD_SEARCH_ALBUM,
            MPD_SEARCH_TITLE,
            MPD_SEARCH_ANY))) {
            $this->errStr = "mpd->Find(): invalid find type";
            return null;
        } else {*/
            if(is_null($resp = $this->SendCommand(MPD_CMD_FIND, $type, $string)))
                return null;
            $searchlist = $this->_parseFileListResponse($resp);
        //}
        if($this->debugging)
            echo "mpd->Find() / return " . print_r($searchlist) . "\n";
        return $searchlist;
    }

    /* Disconnect()
    * 
    * Closes the connection to the MPD server.
    */
    function Disconnect() {
        if($this->debugging)
            echo "mpd->Disconnect()\n";
        fclose($this->mpd_sock);

        $this->connected = false;
        unset($this->mpd_version);
        unset($this->errStr);
        unset($this->mpd_sock);
    }

    /* GetArtists()
    * 
    * Returns the list of artists in the database in an associative array.
    */
    function GetArtists() {
        if($this->debugging)
            echo "mpd->GetArtists()\n";
        if(is_null($resp = $this->SendCommand(MPD_CMD_TABLE, MPD_TBL_ARTIST)))
            return null;
        $arArray = array();

        $arLine = strtok($resp, "\n");
        $arName = "";
        $arCounter = -1;
        while($arLine) {
            list($element, $value) = explode(": ", $arLine);
            if($element == "Artist") {
                $arCounter++;
                $arName = $value;
                $arArray[$arCounter] = $arName;
            }

            $arLine = strtok("\n");
        }
        if($this->debugging)
            echo "mpd->GetArtists()\n";
        return $arArray;
    }

    /* GetAlbums()
    * 
    * Returns the list of albums in the database in an associative array. Optional parameter
    * is an artist Name which will list all albums by a particular artist.
    */
    function GetAlbums($ar = null) {
        if($this->debugging)
            echo "mpd->GetAlbums()\n";
        if(is_null($resp = $this->SendCommand(MPD_CMD_TABLE, MPD_TBL_ALBUM, $ar)))
            return null;
        $alArray = array();

        $alLine = strtok($resp, "\n");
        $alName = "";
        $alCounter = -1;
        while($alLine) {
            list($element, $value) = explode(": ", $alLine);
            if($element == "Album") {
                $alCounter++;
                $alName = $value;
                $alArray[$alCounter] = $alName;
            }

            $alLine = strtok("\n");
        }
        if($this->debugging)
            echo "mpd->GetAlbums()\n";
        return $alArray;
    }

    //*******************************************************************************//
    //***************************** INTERNAL FUNCTIONS ******************************//
    //*******************************************************************************//

    /* _computeVersionValue()
    *
    * Computes a compatibility value from a version string
    *
    */
    function _computeVersionValue($verStr) {
        list($ver_maj, $ver_min, $ver_rel) = explode(".", $verStr);
        return (100 * $ver_maj) + (10 * $ver_min) + ($ver_rel);
    }

    /* _checkCompatibility()
    * 
    * Check MPD command compatibility against our internal table. If there is no version 
    * listed in the table, allow it by default.
    */
    function _checkCompatibility($cmd) {
        // Check minimum compatibility
        $req_ver_low = (isset($this->COMPATIBILITY_MIN_TBL[$cmd])) ? $this->COMPATIBILITY_MIN_TBL[$cmd] : '';
        $req_ver_hi = (isset($this->COMPATIBILITY_MAX_TBL[$cmd])) ? $this->COMPATIBILITY_MAX_TBL[$cmd] : '';

        $mpd_ver = $this->_computeVersionValue($this->mpd_version);

        if($req_ver_low) {
            $req_ver = $this->_computeVersionValue($req_ver_low);

            if($mpd_ver < $req_ver) {
                $this->errStr = "Command '$cmd' is not compatible with this version of MPD, version " .
                    $req_ver_low . " required";
                return false;
            }
        }

        // Check maxmum compatibility -- this will check for deprecations
        if($req_ver_hi) {
            $req_ver = $this->_computeVersionValue($req_ver_hi);

            if($mpd_ver > $req_ver) {
                $this->errStr = "Command '$cmd' has been deprecated in this version of MPD.";
                return false;
            }
        }

        return true;
    }

    /* _parseFileListResponse()
    * 
    * Builds a multidimensional array with MPD response lists.
    *
    * NOTE: This function is used internally within the class. It should not be used.
    */
    function _parseFileListResponse($resp) {
        if(is_null($resp)) return null;
        
        $plistArray = array();
        $plistLine = strtok($resp, "\n");
        $plistFile = "";
        $plCounter = -1;
        while($plistLine) {
            list($element, $value) = explode(": ", $plistLine);
            if($element == "file") {
                $plCounter++;
                $plistFile = $value;
                $plistArray[$plCounter]["file"] = $plistFile;
            } else {
                $plistArray[$plCounter][$element] = $value;
            }

            $plistLine = strtok("\n");
        }
        
        return $plistArray;
    }
    
    function _parseInfoResponse($resp) {
        if(is_null($resp)) return null;
        
        $return = array();
        $split = explode("\n", $resp);
        foreach($split as $info) {
            if(empty($info)) continue;
            $splitInfo = explode(': ', $info);
            $return[strtolower($splitInfo[0])] = $splitInfo[1];
        }
        return $return;
    }

    /* RefreshInfo()
    * 
    * Updates all class properties with the values from the MPD server.
    *
    * NOTE: This function is automatically called upon Connect() as of v1.1.
    */
    function RefreshInfo() {
        // Get the Server Statistics
        $statStr = $this->SendCommand(MPD_CMD_STATISTICS);
        if(!$statStr) {
            return null;
        } else {
            $stats = array();
            $statLine = strtok($statStr, "\n");
            while($statLine) {
                list($element, $value) = explode(": ", $statLine);
                $stats[$element] = $value;
                $statLine = strtok("\n");
            }
        }

        // Get the Server Status
        $statusStr = $this->SendCommand(MPD_CMD_STATUS);
        if(!$statusStr) {
            return null;
        } else {
            $status = array();
            $statusLine = strtok($statusStr, "\n");
            while($statusLine) {
                list($element, $value) = explode(": ", $statusLine);
                $status[$element] = $value;
                $statusLine = strtok("\n");
            }
        }

        // Get the Playlist
        $plStr = $this->SendCommand(MPD_CMD_PLLIST);
        $this->playlist = $this->_parseFileListResponse($plStr);
        $this->playlist_count = count($this->playlist);

        // Set Misc Other Variables
        $this->state = $status['state'];
        if(($this->state == MPD_STATE_PLAYING) || ($this->state == MPD_STATE_PAUSED)) {
            $this->current_track_id = $status['song'];
            list($this->current_track_position, $this->current_track_length) = explode(":", $status['time']);
            
            //Get info about the current song playing
            $curStr = $this->SendCommand(MPD_CMD_CURRENTSONG);
            if($curStr) {
                $curInfo = $this->_parseInfoResponse($curStr);
                $this->current_track_title = $curInfo['title'];
                $this->current_track_artist = $curInfo['artist'];
                $this->current_track_album = $curInfo['album'];
                $this->current_track_file = $curInfo['file'];
                $this->current_track_year = $curInfo['date'];
            }
            
        } else {
            $this->current_track_id = -1;
            $this->current_track_position = -1;
            $this->current_track_length = -1;
        }

        $this->repeat = $status['repeat'];
        $this->random = $status['random'];

        $this->db_last_refreshed = $stats['db_update'];

        $this->volume = (isset($status['volume'])) ? $status['volume'] : 0;
        $this->uptime = (isset($stats['uptime'])) ? $stats['uptime'] : 0;
        $this->playtime = (isset($stats['playtime'])) ? $stats['playtime'] : 0;
        $this->num_songs_played = (isset($stats['songs_played'])) ? $stats['songs_played'] : 0;
        $this->num_artists = (isset($stats['num_artists'])) ? $stats['num_artists'] : 0;
        $this->num_songs = (isset($stats['num_songs'])) ? $stats['num_songs']: 0;
        $this->num_albums = (isset($stats['num_albums'])) ? $stats['num_albums'] : 0;
        
        return true;
    }

    /* ------------------ DEPRECATED METHODS -------------------*/
    /* GetStatistics()
    * 
    * Retrieves the 'statistics' variables from the server and tosses them into an array.
    *
    * NOTE: This function really should not be used. Instead, use $this->[variable]. The function
    *   will most likely be deprecated in future releases.
    */
    function GetStatistics() {
        if($this->debugging)
            echo "mpd->GetStatistics()\n";
        $stats = $this->SendCommand(MPD_CMD_STATISTICS);
        if(!$stats) {
            return null;
        } else {
            $statsArray = array();
            $statsLine = strtok($stats, "\n");
            while($statsLine) {
                list($element, $value) = explode(": ", $statsLine);
                $statsArray[$element] = $value;
                $statsLine = strtok("\n");
            }
        }
        if($this->debugging)
            echo "mpd->GetStatistics() / return: " . print_r($statsArray) . "\n";
        return $statsArray;
    }

    /* GetStatus()
    * 
    * Retrieves the 'status' variables from the server and tosses them into an array.
    *
    * NOTE: This function really should not be used. Instead, use $this->[variable]. The function
    *   will most likely be deprecated in future releases.
    */
    function GetStatus() {
        if($this->debugging)
            echo "mpd->GetStatus()\n";
        $status = $this->SendCommand(MPD_CMD_STATUS);
        if(!$status) {
            return null;
        } else {
            $statusArray = array();
            $statusLine = strtok($status, "\n");
            while($statusLine) {
                list($element, $value) = explode(": ", $statusLine);
                $statusArray[$element] = $value;
                $statusLine = strtok("\n");
            }
        }
        if($this->debugging)
            echo "mpd->GetStatus() / return: " . print_r($statusArray) . "\n";
        return $statusArray;
    }

    /* GetVolume()
    * 
    * Retrieves the mixer volume from the server.
    *
    * NOTE: This function really should not be used. Instead, use $this->volume. The function
    *   will most likely be deprecated in future releases.
    */
    function GetVolume() {
        if($this->debugging)
            echo "mpd->GetVolume()\n";
        $volLine = $this->SendCommand(MPD_CMD_STATUS);
        if(!$volLine) {
            return null;
        } else {
            list($vol) = sscanf($volLine, "volume: %d");
        }
        if($this->debugging)
            echo "mpd->GetVolume() / return: $vol\n";
        return $vol;
    }

    /* GetPlaylist()
    * 
    * Retrieves the playlist from the server and tosses it into a multidimensional array.
    *
    * NOTE: This function really should not be used. Instead, use $this->playlist. The function
    *   will most likely be deprecated in future releases.
    */
    function GetPlaylist() {
        if($this->debugging)
            echo "mpd->GetPlaylist()\n";
        $resp = $this->SendCommand(MPD_CMD_PLLIST);
        $playlist = $this->_parseFileListResponse($resp);
        if($this->debugging)
            echo "mpd->GetPlaylist() / return " . print_r($playlist) . "\n";
        return $playlist;
    }

    /* ----------------- Command compatibility tables --------------------- */
    var $COMPATIBILITY_MIN_TBL = array(
        MPD_CMD_SEEK => "0.9.1",
        MPD_CMD_PLMOVE => "0.9.1",
        MPD_CMD_RANDOM => "0.9.1",
        MPD_CMD_PLSWAPTRACK => "0.9.1",
        MPD_CMD_PLMOVETRACK => "0.9.1",
        MPD_CMD_PASSWORD => "0.10.0",
        MPD_CMD_SETVOL => "0.10.0");

    var $COMPATIBILITY_MAX_TBL = array(MPD_CMD_VOLUME => "0.10.0");

} // ---------------------------- end of class ------------------------------


?>