<?php
require_once __DIR__ . '/app/Helpers/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);

// Load Routes
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/system.php';

require_once __DIR__ . '/bootstrap/app.php';

\TheFramework\App\Router::run();
