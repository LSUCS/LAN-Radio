<?php

class CoreHelper{
	public static $instance;
	public $parent;
	public $arguments;
	
	function __construct(&$parent, $arguments = array()){
		$this->parent = $parent;
		$this->arguments = $arguments;
		
		self::$instance = $this;
	}
	
	public function run() { }
}

?>