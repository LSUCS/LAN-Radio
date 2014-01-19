<?php

namespace Core;

//Auto loading
require(dirname(__FILE__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "Core" . DIRECTORY_SEPARATOR . "Autoloader.php");

// Here goes
AutoLoader::initialise();
//CoreAutoLoader::debug();
Core::initialise();


$db = Core::get('DB');

$db->query("SELECT *, @rownum:=@rownum+1 as row_position FROM (
                            SELECT * FROM songlist
                        ) user_rank, (SELECT @rownum:=0) r
");
            
//All Songs
$songs = $db->to_array();
$removeSongs = array_slice($songs, 50);

foreach($removeSongs as $r) {
    $db->query("DELETE FROM voting_list WHERE trackid = ?", array($r['trackid']));
    $db->query("DELETE FROM votes WHERE trackid = ?", array($r['trackid']));    
}                        

?>