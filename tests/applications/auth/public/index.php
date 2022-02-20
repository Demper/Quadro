<?php
/**
 * Show all errors
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * set the application path manually to avoid Application Path Error
 */
define('QUADRO_DIR_APPLICATION', realpath(__DIR__ .  '/../'). '/' );

/**
 * Include the Quadro\Application class
 */
$s = DIRECTORY_SEPARATOR;
$applicationClassFile =  realpath(
    __DIR__."{$s}..{$s}..{$s}..{$s}..{$s}libraries{$s}Quadro{$s}Application.php"
);
require_once $applicationClassFile;
$app = Quadro\Application::getInstance();

/**
 * Register the Authentication component
 */
$app->addComponent(new Quadro\Authentication\Jwt());

/**
 * By setting the environment variable to "development" we enable DEBUG mode
 * Uncomment next line to go in debug mode...
 */
putenv(Quadro\Application::ENV_INDEX . '=' . Quadro\Application::ENV_DEVELOPMENT);

/**
 * Let the application handle the request
 */
$app->run();