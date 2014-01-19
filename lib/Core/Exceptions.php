<?php

namespace Core;

/**
 * Centralised hold-all for exceptions
 */

class HTTPException extends \Exception {
    const HTTPErrorCode = 500;
    const HTTPDescription = 'Internal Server Error';
}

class _404Exception extends HTTPException {
    const HTTPErrorCode = 404;
    const HTTPDescription = 'Page Not Found';
}

class ClassNotInRegistryException extends \Exception { }