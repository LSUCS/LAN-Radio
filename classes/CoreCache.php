<?php

class CoreCache {
	public $CacheHits = array();
	public $ClearCache = false;
	
	public function generateClearCacheURL() {
        $URI = (empty(Core::get('Core')->pieces)) ? 'index' : Core::get('Core')->pieces;
		return str_replace('/clearcache', '', $URI) . "/clearcache";
	}
	
	public function get($key, &$flags = NULL) {
		if($this->ClearCache) return false;
        
		$ret = apc_fetch($key, $succ);
		if($succ) {
			$this->CacheHits[] = array('Key' => $key, 'Value' => $ret, 'Source' => 'apc');
			return $ret;
		}
        return false;
	}
	
	public function set($key, $value, $expire = 0) {
		return apc_store($key, $value, $expire);
	}
	
	public function delete($key, $timeout = 0) {
		return apc_delete($key);
	}
}

?>