<?php
/**
 * @author Rob <rob@jaribio.com>
 * @file start-webserver.php;
 * @see https://www.php.net/manual/en/features.commandline.webserver.php
 *
 * Shortcut for starting build in PHP webserver.
 *
 * Warning
 *   This web server is designed to aid application development. It may also be
 *   useful for testing purposes or for application demonstrations that are run
 *   in controlled environments. It is not intended to be a full-featured web
 *   server. It should not be used on a public network.
 *
 * This file should be executed from within this directory with the following
 * commands:
 *
 *    > php start_webserver.php
 *    > php start_webserver.php default
 *    > php start_webserver.php default production
 *
 * The command excepts two optional arguments
 * - Argument 1 will be the name of the example application. An error message is
 *   displayed when this application does not exist. If not given it defaults to
 *   the "default" example application
 *
 * - Argument 2 will be the state of the environment and can be
 *   production, staging or development. In production all error handling is
 *   suppressed. If not given it defaults to the "development" state
 *
 */
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * - First argument is the name of the sample application. Defaults to "default"
 * - Second argument the state of the application (production|staging|development)
 *   Defaults to 'development'
 */
$appName = strtolower($argv[1] ?? 'default');
$appState = strtolower($argv[2] ?? 'development');
echo 'Example Application = ' . $appName . PHP_EOL;
echo 'Application State   = ' . $appState . PHP_EOL;

/**
 * In case of typos, check for existence of the application
 */
$appDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . $appName);
if (false === $appDir) {
    exit(PHP_EOL . 'Example Application "' . $appName . '" not found!' . PHP_EOL);
}

/**
 * Include the Quadro\Application class
 */
$s = DIRECTORY_SEPARATOR;
$applicationClassFile =  realpath(
    __DIR__."{$s}..{$s}libraries{$s}Quadro{$s}Application.php"
);
require_once $applicationClassFile;

/**
 * By setting the environment variable to "development" or "staging"
 * we enable error reporting in the chosen application
 */
if ($appState !== Quadro\Application::ENV_DEVELOPMENT &&
    $appState !== Quadro\Application::ENV_PRODUCTION &&
    $appState !== Quadro\Application::ENV_STAGING
) {
    exit(PHP_EOL . '"'.$appState.'" Is not a valid state. Accepting production, staging or development' . PHP_EOL);
}
echo Quadro\Application::ENV_INDEX . '=' . $appState . PHP_EOL;
putenv(Quadro\Application::ENV_INDEX . '=' . $appState);

/**
 * Start the WebServer
 */
$cmd = 'php -S localhost:8080 ./' . $appName . '/public/index.php';
echo $cmd . PHP_EOL;
shell_exec($cmd);
exit();


