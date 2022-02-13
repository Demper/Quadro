<?php
putenv('APPLICATION_ENV=development');
require_once realpath(__DIR__ . '/../init.php');

Quadro\Application::getInstance([
    'dispatcher' =>  [
        'path' => realpath(__DIR__ . '/data/controllers/')
    ],
    'database' => [
        'dsn' => 'sqlite:' . realpath(__DIR__ . '/data/db.sqlite')
    ],
    'authentication' =>[
        'class' => ''
    ]



]);

// unset change error handlers
set_error_handler(null);
set_exception_handler(null);

