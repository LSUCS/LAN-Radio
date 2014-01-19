<?php

namespace Core\Model;

use \Core as Core;
use Core\Core as C;

class User extends Core\Model {

    public $ID; // int(11) NOT NULL AUTO_INCREMENT,
    public $username; // varchar(25) NOT NULL,
    public $email; // varchar(30) NOT NULL,
    public $avatarURL; // varchar(255) DEFAULT NULL,
    public $isAdmin = false; // int(1) NOT NULL,
    public $theme = null;

    public function __construct() {
        $this->ID = null;
        $this->Username = null;
        $this->Email = '';
        $this->avatarURL = '';
        $this->isAdmin = false;
    }

    protected function _loadFromRecord($record) {
        $this->ID = $record['userid'];
        $this->username = $record['username'];
        $this->email = $record['email'];
        $this->avatarURL = $record['avatar'];
        $this->isAdmin = $record['moderator'];
    }

    /**
     * Method for fetching a users by it's ID.
     *
     * @static
     * @param $id
     * @return Model_User
     */
    public static function loadFromID($id) {
        $inst = new self();
        return $inst->_loadFromID($id);
    }

    /**
     * @throws CoreModelNoSuchRecordException
     * @param $id
     * @return Model_User
     */
    protected function _loadFromID($id) {
        $cK = 'user_' . $id;
        $out = $this->_getCache()->get($cK);
        if (!$out) {
            Core::requireLibrary('LANAuth');
            $Auth = new LANAuth;
            $out = $Auth->getUserByID($id);
            
            if(array_key_exists('error', $out)) {
                throw new CoreModelNoSuchRecordException();
            }
            
            $this->_getCache()->set($cK, $out);
        }
        $this->_loadFromRecord($out);
        return $this;
    }

    /**
     * Get the ID for this object
     *
     * @return int Database ID
     */
    public function getID() {
        if (is_null($this->ID)) return false;
        return $this->ID;
    }

    public static function getVisitorUser() {
        $lu = Core::get('Core')->LoggedUser;
        $vis = new self;
        $vis->_loadFromRecord($lu);
        return $vis;
    }

    public static function fetchArrayFromDBSet($dbSet) {
        $output = array();
        foreach ($dbSet as $record) {
            $me = new self();
            $me->_loadFromRecord($record);
            array_push($output, $me);
        }
        return $output;
    }
}
