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
 * add controllers directory
 */
$app->getDefaultDispatcher()->addPath(__DIR__ . '/../controllers');


/**
 * Register the Authentication component
 */
$app->addComponent(new Quadro\Authentication\Jwt());


/**
 * Register the Authorization component and add the rules
 */
$app->addComponent(new Quadro\Authorization());


/**
 * By setting the environment variable to "development" we enable DEBUG mode
 * Uncomment next line to go in debug mode...
 */
//$debug = $_GET['debug']??false;
//if ($debug)
putenv(Quadro\Application::ENV_INDEX . '=' . Quadro\Application::ENV_DEVELOPMENT);

/**
 * Let the application handle the request
 */
$app->run();