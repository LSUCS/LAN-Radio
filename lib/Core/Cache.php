<?php

namespace Core;

class Cache {
	public static $CacheHits = array();
	public static $ClearCache = false;
	
	public static function generateClearCacheURL() {
        $URI = (empty(Core::get('Core')->pieces)) ? 'index' : Core::get('Core')->pieces;
		return str_replace('/clearcache', '', $URI) . "/clearcache";
	}
	
	public static function get($key, &$flags = NULL) {
		if(self::$ClearCache) return false;
        
		$ret = apc_fetch($key, $succ);
		if($succ) {
			self::$CacheHits[] = array('Key' => $key, 'Value' => $ret, 'Source' => 'apc');
			return $ret;
		}
        return false;
	}
	
	public static function set($key, $value, $expire = 0) {
		return apc_store($key, $value, $expire);
	}
	
	public static function delete($key, $timeout = 0) {
		return apc_delete($key);
	}
}

?>