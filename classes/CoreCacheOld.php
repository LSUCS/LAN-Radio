<?php

class CoreCache extends Memcached {
	public $connected = false;
	public $CacheHits = array();
	public $ClearCache = false;
	private $parent;
	
	function __construct(&$parent) {
		$this->parent = $parent;
		$this->connect(MEMCACHE_HOST, MEMCACHE_PORT);
	}

    public function connect($host, $port) {
        $servers = $this->getServerList();
        if (is_array($servers)) {
            foreach ($servers as $server)
                if ($server['host'] == $host and $server['port'] == $port)
                    return true;
        }
        return $this->addServer($host, $port);
    }
	
	public function generateClearCacheURL() {
        $URI = (empty(Core::get('Core')->pieces)) ? 'index' : Core::get('Core')->pieces;
		return str_replace('/clearcache', '', $URI) . "/clearcache";
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
			apc_store($key, $ret, 10);
		$this->CacheHits[] = array('Key' => $key, 'Value' => $ret, 'Source' => 'memcache');
		return $ret;
	}
	
	public function set($key, $value, $flag = 0, $expire = 0) {
		apc_store($key, $value, $expire);
		$this->connected = true;
		return parent::set($key, $value, $flag, $expire);
	}
	
	public function delete($key, $timeout = 0) {
		apc_delete($key);
		$this->connected = true;
		return parent::delete($key, $timeout);
	}
}

?>