<?php
 
abstract class CoreModel {
    /**
     * @return NebDatabase
     */
    protected function _getDB() {
        return Core::get('DB');
    }

    /**
     * @return NebCache
     */
    protected function _getCache() {
        return Core::get('Cache');
    }

    abstract protected function _loadFromRecord($record);
}

class CoreModelException extends Exception {}

class CoreModelNoSuchRecordException extends CoreModelException {}