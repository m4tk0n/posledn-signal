<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Env.php';
require_once BASE_PATH . '/app/Core/Database.php';
require_once BASE_PATH . '/app/Security/Session.php';

\App\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) \App\Core\Env::get('APP_TIMEZONE', 'Europe/Prague'));

\App\Security\Session::start();
