<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require('classes/CoreAutoLoader.php');

// Here goes
CoreAutoLoader::initialise();
//CoreAutoLoader::debug();
Core::initialise();

/*
 * Main entry point
 */

$router = CoreRouter::getInstance();
try {
    $router->run();
} catch (CoreHTTPException $he) {
    header('HTTP/1.1 ' . $he::HTTPErrorCode . ' ' . $he::HTTPDescription);
    // TODO: actual HTTP error page
    exit($he::HTTPErrorCode . ': ' . $he::HTTPDescription . '<br /><br />' . $he->getMessage());
} catch (Exception $e) {
    Core::get('Error')->haltException($e);
}
