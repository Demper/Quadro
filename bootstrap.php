<?php
declare(strict_types=1);

use Quadro\Application;

if (defined('QUADRO_DIR')) return;
$applicationPath = $applicationPath ?? '';
/**
 * Get the path of the calling script and define this as the Application Folder
 * This can also already be defined by the application
 */
if(!defined('QUADRO_DIR_APPLICATION')) {
    if (is_dir($applicationPath)) {
        // do not question, apparently this is set by the programmer
        // we trust him or just let it explode :-)
        define('QUADRO_DIR_APPLICATION', rtrim($applicationPath, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR);
    } else {

        // when the first callee is the index.php we assume one step up is the Application directory
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (count($backtrace) > 0 ) {
            $backtraceIndex = count($backtrace) - 1;
            $firstCallee = $backtrace[$backtraceIndex]['file'];
            if (basename($firstCallee) == 'index.php') {
                $applicationPath = realpath( dirname($firstCallee) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
                if(false !== $applicationPath) {
                    define('QUADRO_DIR_APPLICATION', $applicationPath . DIRECTORY_SEPARATOR);
                }
            }
        }

        if (!defined('QUADRO_DIR_APPLICATION')) {
            exit('Quadro Initialization Error: Can not find a valid application path. Use define("QUADRO_DIR_APPLICATION", "/application/path")');
        }
    }
}

/**
 * Some handy shortcuts
 */
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('NAMESPACE_SEPARATOR')) define('NAMESPACE_SEPARATOR', '\\');
if (!defined('NS')) define('NS', NAMESPACE_SEPARATOR);
if (!defined('PATH_SEPARATOR')) define('PATH_SEPARATOR', ';');
if (!defined('PS')) define('PS', PATH_SEPARATOR);

/**
 * The path of the Quadro Framework
 */
const QUADRO_DIR = __DIR__ . DS;

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
const QUADRO_DIR_LIBRARIES = QUADRO_DIR . 'libraries' . DS;
const QUADRO_DIR_RESOURCES = QUADRO_DIR . 'resources' . DS;
const QUADRO_DIR_TESTS = QUADRO_DIR . 'tests' . DS;
const QUADRO_DIR_VENDOR = QUADRO_DIR . 'vendor' . DS;

/**
 * force production environment just to be save if we forgot to set the
 * environment variable
 */
if (false === getenv(Application::ENV_INDEX) ) {
    putenv(Application::ENV_INDEX . '=' . Application::ENV_PRODUCTION);
}

/**
 * Add the default headers and add or overwrite with application specified headers
 * NOTE: this also can be done in the response object
 */
require_once QUADRO_DIR . 'headers.php';
if(is_file( QUADRO_DIR_APPLICATION . 'headers.php')) {
    require_once  QUADRO_DIR_APPLICATION . 'headers.php';
}

/**
 * Autoload stuff.
 */
if (is_file(QUADRO_DIR .'vendor'.DS.'autoload.php')) {
    require_once QUADRO_DIR . 'vendor' . DS . 'autoload.php';
}