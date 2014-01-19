<?php

namespace Core;

abstract class Helper {
    public static $instance;
    public $parent;
    public $arguments;
    
    abstract public function run();
    
    function __construct($arguments = array()){
        $this->arguments = $arguments;
    }
    
    protected function error($message) {
        exit(json_encode(array('error'=>$message)));
    }
}