<?php

class Helper_Toolbox_Global extends CoreHelper {
    public function run() {
        $db = Core::get('DB');

        $s = array();
        $s[] = (isset($_POST['spotify'])) ? 1 : 0;
        $s[] = (isset($_POST['youtube'])) ? 1 : 0;
        $s[] = (isset($_POST['local'])) ? 1 : 0;
        $s[] = (isset($_POST['upload'])) ? 1 : 0;
        
        $db->query("UPDATE site_config SET spotify = ?, youtube = ?, local = ?, upload = ?", $s);

    }
}