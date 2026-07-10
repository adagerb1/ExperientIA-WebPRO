<?php
/** Bootstrap del backend: autoload PSR-4 simple, .env, errores, BD. */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoloader PSR-4 (Core\, Controllers\, Services\, Models\ → app/)
spl_autoload_register(function (string $class): void {
    $prefixes = ['Core\\' => 'Core/', 'Controllers\\' => 'Controllers/', 'Services\\' => 'Services/', 'Models\\' => 'Models/'];
    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = __DIR__ . '/' . $dir . $rel . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

use Core\Env;
use Core\ErrorHandler;

Env::load(BASE_PATH . '/.env');
ErrorHandler::register();
date_default_timezone_set('UTC');
