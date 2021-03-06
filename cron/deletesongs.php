<?php

namespace Core;

//Auto loading
require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "Core" . DIRECTORY_SEPARATOR . "AutoLoader.php");

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

echo "deleting " . count($removeSongs) . "\n";

foreach($removeSongs as $r) {
    $db->query("DELETE FROM voting_list WHERE trackid = ?", $r['trackid']);
    $db->query("DELETE FROM votes WHERE trackid = ?", $r['trackid']);
}                        

?>
