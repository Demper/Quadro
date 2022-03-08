<?php
declare(strict_types=1);

/**
 * Include the Quadro\Application class
 */
$s = DIRECTORY_SEPARATOR;
$applicationClassFile =  realpath(
    __DIR__."{$s}..{$s}..{$s}..{$s}libraries{$s}Quadro{$s}Application.php"
);
require_once $applicationClassFile;
$app = Quadro\Application::getInstance();

/**
 * Register the Authentication component
 */
$app->addComponent(new Quadro\Authentication\Jwt());

/**
 * Let the application handle the request
 */
$app->run();