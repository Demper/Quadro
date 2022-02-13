<?php
/**
 * This file is part of the Quadro Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

/**
 * 1. Initialize Quadro Framework
 * -------------------------------------------------------------------------------------------------------------------
 */

/**
 * Get the path of the calling script and define this as the Application Folder
 * This can also already be defined by the application
 */
if(!defined('QUADRO_DIR_APPLICATION')) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    if (isset($backtrace[0]) && isset($backtrace[0]['file'])) {
        define('QUADRO_DIR_APPLICATION', dirname($backtrace[0]['file']) . DIRECTORY_SEPARATOR);
    }
    if (!defined('QUADRO_DIR_APPLICATION')) {
        exit('Quadro Initialization Error: Can not find a valid application path');
    }
}

/**
 * The path of the Quadro Framework and other handy short cuts
 */
const QUADRO_DIR             = __DIR__ . DIRECTORY_SEPARATOR;
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('NAMESPACE_SEPARATOR')) define('NAMESPACE_SEPARATOR', '\\');
if (!defined('NS')) define('NS', NAMESPACE_SEPARATOR);
if (!defined('PATH_SEPARATOR')) define('PATH_SEPARATOR', ';');
if (!defined('PS')) define('PS', PATH_SEPARATOR);

/**
 * The Application directory must not be the same as this directory
 */
if (QUADRO_DIR == QUADRO_DIR_APPLICATION){
    exit('Quadro Initialization Error: The Application directory can not be the same as Quadro source Directory');
}

/**
 * Quadro Constants for all directories inside the Quadro Api Framework
 */
const QUADRO_DIR_CONTROLLERS = QUADRO_DIR . 'controllers' . DS;
const QUADRO_DIR_LIBRARIES   = QUADRO_DIR . 'libraries' . DS;
const QUADRO_DIR_RESOURCES   = QUADRO_DIR . 'resources' . DS;
const QUADRO_DIR_TESTS       = QUADRO_DIR . 'tests' . DS;
const QUADRO_DIR_VENDOR      = QUADRO_DIR . 'vendor' . DS;
const QUADRO_ENV_INDEX       = 'APPLICATION_ENV';

/**
 * force production environment just to be save if we forgot to set the
 * environment variable
 */
const QUADRO_ENV_PRODUCTION = 'production';
const QUADRO_ENV_STAGING = 'staging';
const QUADRO_ENV_DEVELOPMENT = 'development';
if (false === getenv(QUADRO_ENV_INDEX) ) {
    putenv(QUADRO_ENV_INDEX . '=' . QUADRO_ENV_PRODUCTION);
}

/**
 * Add the default headers and add or overwrite with application specified headers
 * NOTE: this also can be done in the response object
 */
require_once 'headers.php';
if(is_file( QUADRO_DIR_APPLICATION . 'headers.php')) {
    require_once  QUADRO_DIR_APPLICATION . 'headers.php';
}

/**
 *  Default exception handler returns a Quadro API json response structure not the object.
 *  The latter may not be available yet.
 */
set_exception_handler(function(Throwable $thrown): void {
    $serverProtocol = (isset($_SERVER['SERVER_PROTOCOL']))? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
    header($serverProtocol . ' 500 Internal Server Error', true, 500);
    $response = [
        'return' => [
            'value' => null,
            'type' => null,
        ],
        'environment' => getenv('APPLICATION_ENV') ,
        'status' => [
            'code' => 500,
            'text' => 'Internal Server Error'
        ]
    ];
    if (getenv('APPLICATION_ENV') != 'production') {
        $response[ 'return']['value'] =  [
            'code' => $thrown->getCode(),
            'message' => $thrown->getMessage(),
            'file' => $thrown->getFile(),
            'line' => $thrown->getLine(),
        ];
        $response[ 'return']['type'] = 'Fatal Exception';
    }
    exit(json_encode($response));
});

/**
 * Default Error handler returns a Quadro API json response structure
 */
set_error_handler(function(int $number, string $message, string $file=null, int $line = null): void
{
    $serverProtocol = (isset($_SERVER['SERVER_PROTOCOL']))? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
    header($serverProtocol . ' 500 Internal Server Error', true, 500);
    $response = [
         'return' => [
             'value' => null,
             'type' => null,
         ],
         'environment' => getenv('APPLICATION_ENV') ,
         'status' => [
             'code' => 500,
             'text' => 'Internal Server Error'
         ]
    ];
    if (getenv('APPLICATION_ENV') != 'production') {
        $response[ 'return']['value'] =  [
            'code' => $number,
            'message' => $message,
            'file' => $file,
            'line' => $line,
        ];
        $response[ 'return']['type'] = 'Fatal Error';
    }
    exit(json_encode($response));
});

/**
 * Autoload stuff.
 * NOTE the library are loaded as wel as defined in the composer.json
 */
require_once __DIR__ . '/vendor/autoload.php';

//header_register_callback(function () {
//
//
//
//} );