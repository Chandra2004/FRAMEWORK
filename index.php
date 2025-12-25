<?php
require_once __DIR__ . '/app/Helpers/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);

require_once __DIR__ . '/bootstrap/app.php';

\TheFramework\App\Router::run();
