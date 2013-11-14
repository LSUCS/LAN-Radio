<?php

class Helper_History_Showtable extends CoreHelper {    
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
                'Chooser' => 'u.Username',
                'Played' => 'h.datePlayed',
                'Added' => 'h.dateAdded'
            ),
            'tables' => array(
                'history AS h',
                'JOIN track_info AS ti ON h.trackid = ti.trackid',
                'LEFT JOIN votes AS v ON h.trackid = v.trackid',
                'LEFT JOIN users AS u ON h.addedBy = u.ID',
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
                'PlayCount' => 'COUNT(h2.trackid)',
                'LastPlayed' => 'MAX(h2.datePlayed)'
            ),
            'tables' => array(
                'history AS h',
                'JOIN track_info AS ti ON h.trackid = ti.trackid',
                'LEFT JOIN history AS h2 ON h.trackid = h2.trackid'
            ),
            'group' => 'GROUP BY h.trackid',
            'order' => 'ORDER BY PlayCount DESC'
        ),
        'popartist' => array(
            'columns' => array(
                'Artist' => 'ti.artist',
                'Tracks' => 'COUNT(DISTINCT h2.trackid)',
                'TotalPlays' => 'COUNT(h2.trackid)',
                'TotalVotes' => 'SUM(h2.votes)'
            ),
            'tables' => array(
                'history AS h',
                'JOIN track_info AS ti ON h.trackid = ti.trackid',
                'JOIN track_info AS ti2 ON ti.artist = ti2.artist',
                'LEFT JOIN history AS h2 ON ti2.trackid = h2.trackid'
            ),
            'group' => 'GROUP BY ti.artist',
            'order' => 'ORDER BY TotalVotes DESC'
        ),
        'popuser' => array(
            'columns' => array(
                'UserID' => 'u.ID',
                'Chooser' => 'u.Username',
                'TotalVotes' => 'SUM(h2.votes)',
                'TotalPlays' => 'COUNT(h2.trackid)'
            ),
            'tables' => array(
                'history AS h',
                'LEFT JOIN history AS h2 ON h.addedBy = h2.addedBy',
                'JOIN users AS u ON h.addedBy = u.ID'
            ),
            'group' => 'GROUP BY h.addedBy',
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
        'Chooser' => array('label' => '', 'column' => false),
        'Played' => array('label' => 'Played', 'column' => true),
        'Added' =>  array('label' => 'Time before Played', 'column' => true),
        'LastPlayed' => array('label' => 'Last Played', 'column' => true),
        'Tracks' => array('label' => 'Unique Songs', 'column' => true),
        'TotalPlays' => array('label' => 'Total Plays', 'column' => true),
        'TotalVotes' => array('label' => 'Total Votes', 'column' => true),
        'UserID' => array('label' => 'Added By', 'column' => true)
    );
    
    private $TableType;
    private $Cols = 0;
    private $Output;
    
    private function build_query() {
        $Cols = array();
        foreach($this->Tables[$this->TableType]['columns'] as $Column => $ColQuery) {
            $Cols[] = $ColQuery . ' AS ' . $Column;
        }
        $Query = "SELECT " . implode(', ', $Cols) . " FROM " . implode(' ', $this->Tables[$this->TableType]['tables']);
        if(array_key_exists('group', $this->Tables[$this->TableType])) $Query .= " " . $this->Tables[$this->TableType]['group'];
        if(array_key_exists('order', $this->Tables[$this->TableType])) $Query .= " " . $this->Tables[$this->TableType]['order'];

        return $Query;
    }

    private function add_placeholder_row() {
        $this->Output .= "<tr><td class='center' colspan='" . $this->Cols . "'>There are currently no songs to show.</td></tr>";
    }
    
    private function build_table_header() {
        $this->Output = '
            <span class="hidden" id="current-table">' . $this->TableType .'</span>
            <table id="history-table-' . $this->TableType . '">
                <thead>
                    <tr>';
        foreach($this->Tables[$this->TableType]['columns'] as $Col => $CQ) {
            if($this->Columns[$Col]['column']) {
                $this->Cols++;
                $this->Output .= '<th class="' . strtolower($Col) . '">' . $this->Columns[$Col]['label'] . '</th>';
            }
        }
        $this->Output .= '
                    </tr>
                </thead>
                <tbody>';
        
    }
    
    private function add_data($Data) {
        $a = 'even';
        foreach($Data as $D) {
            $a = ($a == 'even') ? 'odd' : 'even';
            $this->Output .= "<tr class='" . $a . "'>";
            foreach($D as $Col=>$Val) {
                if(!$this->Columns[$Col]['column']) continue;
                switch($Col) {
                    case 'Duration':
                        $this->Output .= "<td>" . Core::get_time($Val) . "</td>";
                        break;
                    case 'UserID':
                    case 'ChooserID':
                        $this->Output .= "<td>" . Core::linkUser($Val, $D['Chooser']) . "</td>";
                        break;
                    case 'Played':
                        $this->Output .= "<td>" . Core::timeDiff($Val) . "</td>";
                        break;
                    case 'Added':
                        $this->Output .= "<td>" . Core::timeDiff(strtotime($Val) - strtotime($D['Played'])) . "</td>";
                        break;
                    case 'Votes':
                    case 'PlayCount':
                        $this->Output .= "<td>" . number_format($Val) . "</td>";
                        break;
                    default:
                        $this->Output .= "<td>" . Core::displayStr($Val) . "</td>";
                }
            }
        }
    }
    
    private function end_table() {
        $this->Output .= "</tbody></table>";
    }
    
    private function error($E) {
        die($E);
    }
    
    public function run() {
        $Table = $this->arguments;
        
        if(!in_array($Table, array_keys($this->Tables))) $this->error('Table does not exist');
        $this->TableType = $Table;
        
        $DB = Core::get('DB');
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

?>