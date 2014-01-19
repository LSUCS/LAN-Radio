<?php

/**
 * Centralised hold-all for exceptions
 */

class CoreHTTPException extends Exception {
    const HTTPErrorCode = 500;
    const HTTPDescription = 'Internal Server Error';
}

class Core404Exception extends CoreHTTPException {
    const HTTPErrorCode = 404;
    const HTTPDescription = 'Page Not Found';
}

class ClassNotInRegistryException extends Exception { }