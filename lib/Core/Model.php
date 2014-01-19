<?php

namespace Core;
 
abstract class Model {
}

class ModelException extends \Exception {}

class ModelNoSuchRecordException extends ModelException {}