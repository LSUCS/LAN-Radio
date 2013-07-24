<?php

class Model_User extends NebModel {

    private $ID; // int(11) NOT NULL AUTO_INCREMENT,
    public $Username; // varchar(25) NOT NULL,
    public $IP; // varchar(15) NOT NULL DEFAULT '0.0.0.0',
    public $Class; // int(10) NOT NULL DEFAULT '1',
    public $ClassName; // varchar(32) DEFAULT NULL,
    public $Downloaded; // bigint(20) unsigned NOT NULL,
    public $Uploaded; // bigint(20) unsigned NOT NULL,
    protected $_Password; // varchar(50) NOT NULL,
    public $Email; // varchar(30) NOT NULL,
    public $AvatarURL; // varchar(255) DEFAULT NULL,
    public $Title; // varchar(255) DEFAULT NULL,
    public $Profile; // text,
    public $DateSignedUp; // datetime NOT NULL,
    public $Theme = DEFAULT_STYLE; // varchar(25) NOT NULL,
    public $IsAdmin = false; // int(1) NOT NULL,
    public $ShowClassSymbols = true; // int(1) NOT NULL,
    public $PermissionGroup = 1; // int(3) NOT NULL,
    protected $_Inviter = null;
    protected $_InviterID = null;

    public function __construct() {
        $this->ID = null;
        $this->Username = null;
        $this->IP = '0.0.0.0';
        $this->Class = '1';
        $this->ClassName = null;
        $this->Downloaded = 0;
        $this->Uploaded = 0;
        $this->_Password = '';
        $this->Email = '';
        $this->AvatarURL = '';
        $this->Title = '';
        $this->Profile = '';
        $this->DateSignedUp = time();
        $this->IsAdmin = false;
        $this->ShowClassSymbols = true;
        $this->PermissionGroup = 1;
        $this->_Inviter = $this->_InviterID = null;
    }

    public function setPassword($password) {
        $pHasher = new NebPasswordHasher();
        $this->_Password = $pHasher->hashPassword($password);
        return true;
    }


    public function save() {
        if (is_null($this->Username)) throw new Exception("There is no username!");
        $db = $this->_getDB();
        if (is_null($this->ID)) { // new insert
            $db->query("INSERT INTO `users`
                            (`ID`, `Username`, `IP`, `Class`, `ClassName`, `Downloaded`, `Uploaded`, `Password`, `Email`, `AvatarURL`, `Title`, `Profile`, `DateSignedUp`, `Theme`, `IsAdmin`, `ShowClassSymbols`, `PermissionGroup`, `Inviter`)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                       array($this->ID, $this->Username, $this->IP, $this->Class, $this->ClassName, $this->Downloaded, $this->Uploaded, $this->_Password, $this->Email, $this->AvatarURL, $this->Title, $this->Profile, $this->DateSignedUp, $this->Theme, $this->IsAdmin, $this->ShowClassSymbols, $this->PermissionGroup, $this->_InviterID)
            );
            $this->ID = $db->inserted_id();
        } else { // magic
            $db->query("UPDATE `users` SET
                            
                `Username` = ?,
                `IP` = ?,
                `Class` = ?,
                `ClassName` = ?,
                `Downloaded` = ?,
                `Uploaded` = ?,
                `Password` = ?,
                `Email` = ?,
                `AvatarURL` = ?,
                `Title` = ?,
                `Profile` = ?,
                `DateSignedUp` = ?,
                `Theme` = ?,
                `IsAdmin` = ?,
                `ShowClassSymbols` = ?,
                `PermissionGroup` = ?,
                `Inviter` = ?
                WHERE `ID` = ?
            ", array(
                    $this->Username,
                    $this->IP,
                    $this->Class,
                    $this->ClassName,
                    $this->Downloaded,
                    $this->Uploaded,
                    $this->_Password,
                    $this->Email,
                    $this->AvatarURL,
                    $this->Title,
                    $this->Profile,
                    $this->DateSignedUp,
                    $this->Theme,
                    $this->IsAdmin,
                    $this->ShowClassSymbols,
                    $this->PermissionGroup,
                    $this->_InviterID,
                    $this->ID
               )
            );
			
            $this->_getCache()->delete('user_'.$this->ID);
        }
    }

    protected function _loadFromRecord($record) {
        $this->ID = $record['ID'];
        $this->Username = $record['Username'];
        $this->IP = $record['IP'];
        $this->Class = $record['Class'];
        $this->ClassName = $record['ClassName'];
        $this->Downloaded = $record['Downloaded'];
        $this->Uploaded = $record['Uploaded'];
        $this->_Password = $record['Password'];
        $this->Email = $record['Email'];
        $this->AvatarURL = $record['AvatarURL'];
        $this->Title = $record['Title'];
        $this->Profile = $record['Profile'];
        $this->DateSignedUp = $record['DateSignedUp'];
        $this->Theme = $record['Theme'];
        $this->IsAdmin = $record['IsAdmin'];
        $this->ShowClassSymbols = $record['ShowClassSymbols'];
        $this->PermissionGroup = $record['PermissionGroup'];
        $this->_InviterID = $record['Inviter'];
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
     * @throws NebModelNoSuchRecordException
     * @param $id
     * @return Model_User
     */
    protected function _loadFromID($id) {
        $cK = 'user_' . $id;
        $out = $this->_getCache()->get($cK);
        if (!$out) {
            $this->_getDB()->query('SELECT * FROM `users` WHERE `ID` = ?', array($id));
            if ($this->_getDB()->record_count() == 0)
                throw new NebModelNoSuchRecordException();

            $out = $this->_getDB()->next_record();
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
        $lu = Nebula::get('Nebula')->LoggedUser;
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

    public function setInviterByUser(Model_User $inviter) {
        $this->_Inviter = $inviter;
        $this->_InviterID = $inviter->getID();
        return true;
    }

    public function getInviter() {
        if (is_null($this->ID)) throw new NebModelException("Model not yet initialised");
        if (is_null($this->_Inviter) && !is_null($this->_InviterID))
            $this->_Inviter = self::loadFromID($this->_InviterID);
        return $this->_Inviter;
    }

}
