<?php
/*
// include vendor autoload
$vendorAutoload =  realpath(
    __DIR__ . DIRECTORY_SEPARATOR .
     '..' . DIRECTORY_SEPARATOR .
     '..' . DIRECTORY_SEPARATOR .
     '..' . DIRECTORY_SEPARATOR .
     '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR .
    'autoload.php'
);
require_once $vendorAutoload;
*/

$vendorAutoload =  realpath(
    __DIR__ . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR .
    '..' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR .
    'Quadro' . DIRECTORY_SEPARATOR . 'Application.php'
);

require_once $vendorAutoload;

putenv(Quadro\Application::ENV_INDEX . '=' . Quadro\Application::ENV_DEVELOPMENT);
Quadro\Application::handleRequest();