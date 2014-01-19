<?php

namespace Core\Model;

use \Core as Core;
use Core\Core as C;
use Core\Cache;

class User extends Core\Model {

    public $ID; // int(11) NOT NULL AUTO_INCREMENT,
    public $username; // varchar(25) NOT NULL,
    public $email; // varchar(30) NOT NULL,
    public $avatarURL; // varchar(255) DEFAULT NULL,
    public $isAdmin = false; // int(1) NOT NULL,
    public $theme = null;

    public function __construct($id) {
        $this->ID = null;
        $this->Username = null;
        $this->Email = '';
        $this->avatarURL = '';
        $this->isAdmin = false;
        
        $this->_loadFromID($id);
    }

    protected function _loadFromRecord($record) {
        $this->ID = $record['userid'];
        $this->username = $record['username'];
        $this->email = $record['email'];
        $this->avatarURL = $record['avatar'];
        $this->isAdmin = $record['moderator'];
    }

    /**
     * @throws CoreModelNoSuchRecordException
     * @param $id
     * @return Model_User
     */
    protected function _loadFromID($id) {
        $cK = 'user_' . $id;
        $out = Cache::get($cK);
        if (!$out) {
            $Auth = new \LAN\Auth;
            $out = $Auth->getUserByID($id);
            
            if(array_key_exists('error', $out)) {
                throw new CoreModelNoSuchRecordException();
            }
            
            Cache::set($cK, $out);
        }
        $this->_loadFromRecord($out);
        return $this;
    }
    
    /**
     * Creates a full hyperlink to the user
     * @return string
     */
    public function link() {
        return "<a href='//lsucs.org.uk/members/" . $this->ID . "'>" . $this->username . "</a>";
    }
    
    /**
     * Checks if a user is an admin
     * @return boolean
     */
    public function isAdmin() {
        return (bool)$this->isAdmin;
    }
     
}
