<?php

/**
 * Database system
 */
class CoreDatabase{
    /**
     * Queries that this class has run.
     * @var array
     */
	public $Queries = array();

    /**
     * Main class instance
     * @var \Main Main "parent" class
     */
	private $parent;

    /**
     * Stores the resource that points to the MySQL link
     * @var resource Link ID
     */
	private $LinkID = false;

    /**
     * Stores the resource pointing to the last MySQL query
     * @var resource Query ID
     */
	private $QueryID = false;

    /**
     * Dunno what this is for, but it's used as a temporary variable by next_record
     *
     * @var array Last record returned with next_record
     */
	private $Record = array();

    /**
     * Constructor for Database
     *
     * @param $parent Main
     */
	function __construct(&$parent){
		$this->parent = $parent;
	}

    /**
     * Run a query with the provided parameters.
     *
     * @param string $Query SQL query to run
     * @param array $params
     * @return void
     */
	public function query($Query, $params = array()){
		$this->_connect();
		if(count($params) != 0){
			foreach($params as &$v){ $v = mysqli_real_escape_string($this->LinkID, $v); }
			$Query = vsprintf(str_replace("?","'%s'",$Query), $params);
		}
		$start = microtime(true);
		$this->QueryID = mysqli_query($this->LinkID, $Query);
		$end = microtime(true);
		$this->Queries[] = array('Query' => $Query, 'ExecutionTime' => sprintf('%.08f', $end - $start));
		if(mysqli_errno($this->LinkID)){
			Core::get('Error')->halt(mysqli_errno($this->LinkID) . ' - ' . mysqli_error($this->LinkID), true);
		}
	}

    /**
     * Return the next record.
     *
     * @return array
     */
	public function next_record($method=MYSQLI_BOTH){
		if($this->LinkID){
			$this->Record = mysqli_fetch_array($this->QueryID, $method);
			if (!is_array($this->Record)) {
				$this->QueryID = false;	
			}
			return $this->Record;
		}
	}

    /**
     * Return the inserted row's ID.
     *
     * @return int Row ID
     */
	public function inserted_id(){
		if($this->LinkID){
			return mysqli_insert_id($this->LinkID);
		}
	}

    /**
     * Return the number of returned rows.
     *
     * @return int Number of returned rows
     */
	public function record_count(){
		if($this->QueryID){
			return mysqli_num_rows($this->QueryID);
		}
	}

    /**
     * Return the number of affected rows.
     *
     * @return int Number of affected rows
     */
	public function affected_rows(){
		if($this->LinkID){
			return mysqli_affected_rows($this->LinkID);
		}
	}

    /**
     * Return *ALL* records in one massive array.
     *
     * @param bool $Key Key thingy
     * @param int $Type Type of MYSQLI thing to return
     * @return array
     */
	public function to_array($Key = false, $Type = MYSQLI_ASSOC){
		if($Key) {
			$Type = MYSQLI_BOTH;
		}
		$Return = array();
		while($Row = mysqli_fetch_array($this->QueryID, $Type)) {
			if($Key)
				$Return[$Row[$Key]] = $Row;
			else
				$Return[] = $Row;
		}
		mysqli_data_seek($this->QueryID, 0);
		return $Return;
	}

    /**
     * Actually do the connection to MySQL.
     *
     * @return void
     */
	private function _connect(){
		// Connect to MySQL
		$this->LinkID = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASSWORD, SQL_DATABASE, SQL_PORT);
		if(!$this->LinkID){
			Core::get('Error')->halt(mysqli_connect_errno() . ' - ' . mysqli_connect_error(), true);
		}
	}
}
