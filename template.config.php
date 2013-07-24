<?php

//Not really a template, just a config file with sensitive information removed. Will generalise it at a later date

// Server Information
define('CORE_SERVER', 'http://lan-radio/');
define('STATIC_SERVER', 'http://lan-radio/static/');
define('INSTALL_PATH', '/home/michael/Documents/ht_docs/lan-radio/');
define('CLASS_PATH', '/home/michael/Documents/ht_docs/lan-radio/classes/');

// General Site Options
define('DEBUG_MODE', true);
define('PHP_ERROR_REPORTING', E_ERROR | E_NOTICE);
define('SITE_NAME', 'LAN Radio');
define('SHORT_NAME', 'LR');
define('DEFAULT_STYLE', 'default');
define('SHOW_RENDERTIME', true);
define('BCRYPT_COST', null); // Cost of Bcrypt: set to between 4 and 31, where 31 is the hardest.

define('ENCKEY', null);

// MySQL Server Information
define('SQL_HOST', null);
define('SQL_PORT', null);
define('SQL_USER', null);
define('SQL_PASSWORD', null);
define('SQL_DATABASE',  null);

// Memcache Settings
define('MEMCACHED_HOST', null);
define('MEMCACHED_PORT', null);

// Authentication API
define('LANAUTH_API_KEY', null);
define('LANAUTH_API_URL', null);

// WebSocket Settings
define('WEBSOCKET_HOST', null);
define('WEBSOCKET_PORT', null);
define('WEBSOCKET_SERVICE', null);
