<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

phpinfo();

class Cache extends Memcache {
	public $connected = false;
	public $CacheHits = array();
	public $ClearCache = false;
	private $parent;
	
	function __construct(&$parent) {
		$this->parent = $parent;
		$this->connect('localhost', 11211);
	}
	
	public function generateClearCacheURL() {
		return str_replace('/clearcache', '', Nebula::get('Nebula')->pieces) . "/clearcache";
	}
	
	public function cache_value($key, $value, $duration = 3600) {
		$this->set($key, $value, 0, $duration);
	}
	
	public function get_value($key) {
		$val = $this->get($key);
		return $val;
	}
	
	public function get($key, &$flags = NULL) {
		$succ = false;
		if($this->ClearCache)
			return false;
		$ret = apc_fetch($key, $succ);
		if($succ) {
			$this->CacheHits[] = array('Key' => $key, 'Value' => $ret, 'Source' => 'apc');
			return $ret;
		}
		$this->connected = true;
		$ret = @parent::get($key, $flags);
		if($ret !== false)
			apc_store($key, $ret);
		$this->CacheHits[] = array('Key' => $key, 'Value' => $ret, 'Source' => 'memcache');
		return $ret;
	}
	
	public function set($key, $value, $flag = 0, $expire = 0) {
		apc_store($key, $value);
		$this->connected = true;
		return parent::set($key, $value, $flag, $expire);
	}
	
	public function delete($key, $timeout = 0) {
		apc_delete($key);
		$this->connected = true;
		return parent::delete($key, $timeout);
	}
}

$C = new Cache;
/*

$test = session_start();

var_dump($_SESSION);

IF(isset($_SESSION['testing'])) {
    $_SESSION['testing']++;
} else {
    $_SESSION['testing'] = 1;
}

var_dump($_SESSION);

session_write_close();
*/