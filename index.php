<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Helpers/helpers.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_DIR', __DIR__);

// 1. Load Environment & Core Services
require_once __DIR__ . '/bootstrap/app.php';

// 2. Load Routes (Environment is now available)
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/system.php';

// 3. Run Application
\TheFramework\App\Router::run();
