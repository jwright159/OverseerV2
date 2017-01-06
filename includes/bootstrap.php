<?php
error_reporting(E_ALL);

require_once __DIR__.'/../vendor/autoload.php'; // Run Composer's autoloader.
require_once __DIR__.'/../build/orm/conf/config.php'; // Connect to the databas and initialize Propel

// Load global state and functions required by Overseer v2.5 backend logic
require_once __DIR__.'/global_functions.php';
require_once __DIR__.'/accrow.php';

// A global flasher. Call $flash->info, error, etc to display flash messages on the next loaded page.
// Useful for form endpoints, which can set an error and redirect back to the form.
$flash = new \Plasticbrain\FlashMessages\FlashMessages();

