<?php

declare(strict_types=1);

// Preload do autoloader do Composer para aquecer o opcache na inicializacao do FPM.
$autoload = __DIR__.'/../../vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}
