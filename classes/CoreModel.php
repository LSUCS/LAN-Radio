<?php
 
abstract class CoreModel {
    /**
     * @return CoreDatabase
     */
    protected function _getDB() {
        return Core::get('DB');
    }

    /**
     * @return CoreCache
     */
    protected function _getCache() {
        return Core::get('Cache');
    }

    abstract protected function _loadFromRecord($record);
}

class CoreModelException extends Exception {}

class CoreModelNoSuchRecordException extends CoreModelException {}