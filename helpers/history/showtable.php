<?php

namespace Core\Helper\History;

use \Core as Core;
use Core\Core as C;
use Core\Utility;
use Core\Model\User;

class Showtable extends Core\Helper { 
    private $Tables = array(
        'recent' => array(
            'columns' => array(
                'Track' => 'ti.title',
                'Artist' => 'ti.artist',
                'Duration' => 'ti.duration',
                'Album' => 'ti.Album',
                'Votes' => 'h.votes',
                'PlayCount' => 'COUNT(h2.trackid)',
                'ChooserID' => 'h.addedBy',
                'Played' => 'h.datePlayed',
                'Added' => 'h.dateAdded'
            ),
            'tables' => array(
                'history AS h',
                'JOIN track_info AS ti ON h.trackid = ti.trackid',
                'LEFT JOIN history AS h2 ON h.trackid = h2.trackid'
            ),
            'group' => 'GROUP BY h.trackid',
            'order' => 'ORDER BY h.datePlayed DESC'
            
        ),
        'popular' => array(
            'columns' => array(
                'Track' => 'ti.title',
                'Artist' => 'ti.artist',
                'Duration' => 'ti.duration',
                'Album' => 'ti.Album',
                'Votes' => 'SUM(h.votes)',
                'PlayCount' => 'COUNT(h.trackid)',
                'LastPlayed' => 'MAX(h.datePlayed)'
            ),
            'tables' => array(
                'track_info AS ti',
                'LEFT JOIN history AS h ON ti.trackid = h.trackid'
            ),
            'group' => 'GROUP BY h.trackid',
            'order' => 'ORDER BY PlayCount DESC'
        ),
        'popartist' => array(
            'columns' => array(
                'Artist' => 'a.artist',
                'Tracks' => 'COUNT(DISTINCT h.trackid)',
                'TotalPlays' => 'COUNT(h.trackid)',
                'TotalVotes' => 'SUM(h.votes)'
            ),
            'tables' => array(
                '(SELECT DISTINCT Artist FROM track_info) as a',
                'JOIN track_info AS ti ON a.Artist = ti.Artist',
                'LEFT JOIN history AS h ON ti.trackid = h.trackid'
            ),
            'group' => 'GROUP BY ti.artist',
            'order' => 'ORDER BY TotalVotes DESC'
        ),
        'popuser' => array(
            'columns' => array(
                'UserID' => 'u.addedBy',
                'TotalVotes' => 'SUM(h.votes)',
                'TotalPlays' => 'COUNT(h.trackid)'
            ),
            'tables' => array(
                '(SELECT DISTINCT addedBy FROM history) as u',
                'LEFT JOIN history AS h ON u.addedBy = h.addedBy'
            ),
            'group' => 'GROUP BY u.addedBy',
            'order' => 'ORDER BY TotalVotes DESC'
        )
    );
    
    //Configuration of the columns. Whether not they need a new column, and their label
    private $Columns = array(
        'Track' => array('label' => 'Track', 'column' => true),
        'Artist' => array('label' => 'Artist', 'column' => true),
        'Duration' => array('label' => 'Duration', 'column' => true),
        'Album' => array('label' => 'Album', 'column' => true),
        'Votes' => array('label' => 'Score', 'column' => true),
        'PlayCount' => array('label' => 'Play Count', 'column' => true),
        'ChooserID' => array('label' => 'Username', 'column' => true),
        'Played' => array('label' => 'Played', 'column' => true),
        'Added' =>  array('label' => 'Time before Played', 'column' => true),
        'LastPlayed' => array('label' => 'Last Played', 'column' => true),
        'Tracks' => array('label' => 'Unique Songs', 'column' => true),
        'TotalPlays' => array('label' => 'Total Plays', 'column' => true),
        'TotalVotes' => array('label' => 'Total Votes', 'column' => true),
        'UserID' => array('label' => 'Username', 'column' => true)
    );
    
    private $tableType;
    private $eventID;
    private $page;
    private $rowsPerPage = 20;
    private $Cols = 0;
    private $Output;
    
    private function build_query() {
        $Cols = array();
        foreach($this->Tables[$this->tableType]['columns'] as $Column => $ColQuery) {
            $Cols[] = $ColQuery . ' AS ' . $Column;
        }
        $Query = "SELECT " . implode(', ', $Cols) . " FROM " . implode(' ', $this->Tables[$this->tableType]['tables']);
        //Event ID, if it's not 0 (0 = all)
        //Fix for joining for user table
        if($this->tableType == "popuser" && $this->eventID) {
            $Query = str_replace('ON u.addedBy = h.addedBy','ON u.addedBy = h.addedBy AND h.eventID = ' . $this->eventID, $Query);
        }
        if($this->eventID) $Query .= " WHERE h.eventID = " . $this->eventID;
        if(array_key_exists('group', $this->Tables[$this->tableType])) $Query .= " " . $this->Tables[$this->tableType]['group'];
        if(array_key_exists('order', $this->Tables[$this->tableType])) $Query .= " " . $this->Tables[$this->tableType]['order'];
        $Query .= " LIMIT " . ($this->page-1) * $this->rowsPerPage . "," . $this->rowsPerPage;

        return $Query;
    }

    private function add_placeholder_row() {
        $this->Output .= "<tr><td class='center' colspan='" . $this->Cols . "'>There are currently no songs to show.</td></tr>";
    }
    
    private function build_table_header() {
        $this->Output = '
            <table class="history-table" id="history-table-' . $this->tableType . '">
                <thead>
                    <tr class="header-row">';
        $i = 0;
        foreach($this->Tables[$this->tableType]['columns'] as $Col => $CQ) {
            if($this->Columns[$Col]['column']) {
                $this->Cols++;
                $this->Output .= '<th class="' . strtolower($Col) . ' col' . $i . '">' . $this->Columns[$Col]['label'] . '</th>';
            }
            $i++;
        }
        $this->Output .= '
                    </tr>
                </thead>
                <tbody>';
        
    }
    
    private function add_data($Data) {
        $a = 'even';
        foreach($Data as $D) {
            $i = 0;
            $a = ($a == 'even') ? 'odd' : 'even';
            $this->Output .= "<tr class='" . $a . "'>";
            foreach($D as $Col=>$Val) {
                if(!$this->Columns[$Col]['column']) continue;
                $title = "";
                switch($Col) {
                    case 'Duration':
                        $rowInfo = Utility::get_time($Val);
                        break;
                    case 'UserID':
                    case 'ChooserID':
                        $User = new User($Val);
                        $rowInfo = $User->link();
                        $title = $User->username;
                        break;
                    case 'Played':
                        $rowInfo = Utility::timeDiff($Val);
                        break;
                    case 'Added':
                        $rowInfo = Utility::timeDiff(strtotime($D['Played']) - strtotime($Val), false, 2, true);
                        break;
                    case 'Votes':
                    case 'PlayCount':
                        $rowInfo = number_format($Val);
                        break;
                    default:
                        $rowInfo = $title = Utility::displayStr($Val);
                }
                $this->Output .= "<td class='col" . $i . "' title='" . $title . "'>" . $rowInfo . "</td>";
                $i++;
            }
        }
    }
    
    private function end_table() {
        $this->Output .= "</tbody></table>";
    }
    
    public function run() {
        if(count($this->arguments) < 3) $this->error(403);
        $this->tableType = $this->arguments[0];
        $this->eventID = $this->arguments[1];
        $this->page = $this->arguments[2];
        
        if(!in_array($this->tableType, array_keys($this->Tables))) $this->error('Table does not exist');
        if(!Utility::isNumber($this->eventID)) $this->error('Invalid Event ID');
        if(!Utility::isNumber($this->page)) $this->error('Invalid Page Number');
        
        $DB = C::get('DB');
        //die($this->build_query());
        $DB->query($this->build_query());
        $Total = $DB->record_count();
        $Data = $DB->to_array(false, MYSQLI_ASSOC);        
        
        $this->build_table_header();
        
        if($Total > 0) $this->add_data($Data);
        else $this->add_placeholder_row();
        
        $this->end_table();
        echo $this->Output;
    }
}