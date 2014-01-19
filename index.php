<?php

namespace Core;

//Auto loading
require(dirname(__FILE__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "Core" . DIRECTORY_SEPARATOR . "Autoloader.php");

//Run
AutoLoader::initialise();
AutoLoader::debug();

Core::initialise();

$router = Router::getInstance();
try {
    $router->run();    
} catch (HTTPException $he) {
    header('HTTP/1.1 ' . $he::HTTPErrorCode . ' ' . $he::HTTPDescription);
    // TODO: actual HTTP error page
    exit($he::HTTPErrorCode . ': ' . $he::HTTPDescription . '<br /><br />' . $he->getMessage());
} catch (Exception $e) {
    Core::get('Error')->haltException($e);
}