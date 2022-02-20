<?php
/**
 * This file is part of the Quadro RestFull Framework which is released under WTFPL.
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

namespace Quadro;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Quadro\Application\ComponentInterface as IComponent;
use Quadro\Application\ObserverInterface as IObserver;
use Quadro\Application\Registry as Registry;
use Quadro\Config as Config;
use Quadro\Config\Exception as ConfigurationException;
use Quadro\Dispatcher\DefaultDispatcherException as DefaultDispatcherException;
use Quadro\DispatcherInterface as IDispatcher;
use Quadro\Exception as Exception;
use Quadro\Http\RequestInterface as IRequest;
use Quadro\Http\ResponseInterface as IResponse;
use Throwable;

/**
 * The Quadro\Application is the core of Quadro Restfull.
 *
 * As it is not acceptable and/because it is resource consuming to initialize some Components/objects more then one time we
 * implement the registry pattern in the the Quadro\Application class to prevent a clutter of singletons
 * as global objects. But to ensure there will be only one we implement the singleton pattern in the Quadro\Application
 * class. This wll be the only singleton we wil be using!
 */
class Application implements JsonSerializable
{


    /**
     * Shorthand for PHP constant __DIRECTORY_SEPARATOR__
     * @see https://www.php.net/manual/en/dir.constants.php
     */
    const DS = DIRECTORY_SEPARATOR;

    /**
     * Shorthand for one directory up : ../
     */
    const DU = '..'.DIRECTORY_SEPARATOR;

    /**
     * Environment value when in development mode
     */
    const ENV_DEVELOPMENT = 'development';

    /**
     * Environment value when in production mode
     */
    const ENV_PRODUCTION  = 'production';

    /**
     * Environment value when in staging/testing mode
     */
    const ENV_STAGING     = 'staging';

    const ENV_INDEX =  'APPLICATION_ENV';

    /**
     * Called in the getInstance() method.
     * Basic error handling is set.
     *
     * @ignore (do not show up in generated documentation)
     */
    #[NoReturn]
    protected function __construct(Config $config = null)
    {

        // check if application is initialized
        if (!defined('QUADRO_DIR')) {
            exit('Quadro Initialization Error: Quadro Framework not correctly initialized');
        }

        if (isset($config)) {
            $this->setConfig($config);
        }

        // start timer
        $this->_startTime = $this->_deltaTime = microtime(true);

        // show all errors if not in production mode
        if ($this->getEnvironment()!= self::ENV_PRODUCTION) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        // and set error and exception handling
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
    }

    /**
     * Textual representation for this object
     *
     * @return string
     */
    #[Pure]
    public function __toString(): string
    {
        return "Quadro Application started at " . $this->getStartTime();
    }

    /**
     * Called when the object instance is used as a function
     *
     * @throws Config\Exception
     * @throws \Quadro\Exception
     */
    #[NoReturn]
    public function __invoke(): void
    {
        $this->run();
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var Application $_instance The instance of the Application
     */
    protected static Application $_instance;

    /**
     * @param array|\Quadro\Config|null $config
     * @return Application
     * @throws Config\Exception
     */
    public static function getInstance(array|Config $config = null): Application
    {
        if(!isset(Application::$_instance) || null === Application::$_instance) {

            Application::_init();

            if (is_array($config)) {
                $config = new Config($config);
            }
            Application::$_instance = new Application($config);
        }
        return Application::$_instance;
    }

    // ----------------------------------------Quadro\Application::ENV_INDEX-------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var Registry Collection of components for this application
     */
    protected Registry  $_registry;

    /**
     * Returns the collection of components for this application
     *
     * Will be initialized in constructor
     * @return Registry
     */
    public function getRegistry(): Registry
    {
        if(!isset($this->_registry)) {
            $this->_registry = new Registry();
        }
        return $this->_registry;
    }

    /**
     * Registry::add();
     *
     * Checks when default components are added such as the
     * Request, Response or Dispatcher Components
     *
     * @param IComponent|callable $component
     * @param string|null $index
     * @return IComponent
     * @throws Application\RegistryException
     * @see Registry::add()
     */
    public function addComponent(IComponent|callable $component, string $index = null): IComponent
    {
        return $this->getRegistry()->add($component, $index);
    }

    /**
     * Shortcut to Registry::get();
     *
     * @see Registry::get()
     * @param string $index
     * @return IComponent
     * @throws Application\RegistryException
     */
    public function getComponent(string $index): IComponent
    {
        return $this->getRegistry()->get($index);
    }

    /**
     * Shortcut to Registry::has();
     *
     * @param string $index
     * @return bool
     */
    public function hasComponent(string $index): bool
    {
        return $this->getRegistry()->has($index);
    }

    /**
     * Shortcut to Registry::remove();
     *
     * @param string $index
     * @return bool
     * @throws Application\RegistryException
     */
    public function removeComponent(string $index): bool
    {
        return $this->getRegistry()->remove($index);
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var DispatcherInterface Internal reference to default dispatcher
     */
    protected IDispatcher $_defaultDispatcher;

    /**
     * Returns the Fallback Dispatcher.Quadro\Application::ENV_INDEX
     *
     * If not already added creates and adds a default Dispatcher
     *
     * @return IDispatcher
     * @throws Config\Exception
     */
    public function getDefaultDispatcher(): IDispatcher
    {
        if (!isset($this->_defaultDispatcher)) {
            $componentClass = $this->getConfig()->getOption('dispatchers.default.class', 'Quadro\Dispatcher\Files');
            $this->_checkConfigClassesInterfaces($componentClass, [IDispatcher::class]);
            $this->_defaultDispatcher = new $componentClass($this->getConfig()->getOption('dispatchers.default', [
                'path' => QUADRO_DIR_CONTROLLERS
            ]));
        }
        return $this->_defaultDispatcher;
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @var array Protected list of custom dispatchers
     */
    protected array $_dispatchers;

    /**
     * @return array
     * @throws Config\Exception
     */
    public function getDispatchers(): array
    {
        $this->_initCustomDispatchers(); // lazy load configuration defined dispatchers
        return $this->_dispatchers;
    }

    /**
     * Lazy-load all configuration defined dispatchers
     *
     * @ignore (do not show up in generated documentation)
     * @throws Config\Exception
     */
    protected function _initCustomDispatchers()
    {
        if (!isset($this->_dispatchers)) {
            $this->_dispatchers = [];
            foreach($this->getConfig()->getOption('dispatchers', []) as $dispatcherIndex => $dispatcherOptions) {
                if ($dispatcherIndex  == 'default') continue;
                $componentClass = $this->getConfig()->getOption('dispatchers.'.$dispatcherIndex.'.class');
                $this->_checkConfigClassesInterfaces($componentClass, [IDispatcher::class]);
                $dispatcher = new  $componentClass($this->getConfig()->getOption('dispatchers.'.$dispatcherIndex));
                $this->_dispatchers[$dispatcherIndex] = $dispatcher;
            }
        }
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @param string|null $index
     * @return $this
     * @throws Config\Exception
     */
    public function addDispatcher(IDispatcher $dispatcher, string $index = null): self
    {
        $this->_initCustomDispatchers(); // lazy load configuration defined dispatchers
        $this->_dispatchers[((empty($index))? get_class($dispatcher): $index)] = $dispatcher;
        return $this;
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var IResponse Internal reference to the default Response object
     */
    protected IResponse $_response;

    /**
     * Returns the Response Component.
     * If not already added creates and adds a default Response Component
     *
     * @return IResponse
     * @throws Exception
     */
    public function getResponse(): IResponse
    {
        if (!isset($this->_response)) {
            try {
                $this->_checkConfigClassesInterfaces('response.class', [IResponse::class]);
            } catch (Config\Exception $e) {

                // when adding a custom response class results in an exception we don have a response class
                // so in case of an Config\Exception we set the response class to a default JSON Response
                $this->getConfig()->setOption('response.class', 'Quadro\Http\Response\Json', true);
                throw $e;
            }
            $componentClass = $this->getConfig()->getOption('response.class', 'Quadro\Http\Response\Json');
            $this->_response = new $componentClass();
        }
        return $this->_response;
    }

    public static function response(): IResponse
    {
        return self::getInstance()->getResponse();
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var IRequest Internal reference to the default Request object
     */
    protected IRequest $_request;

    /**
     * Returns Request Component
     * If not already added creates and adds a default Request Component
     *
     * @return IRequest
     * @throws Config\Exception
     */
    public function getRequest(): IRequest
    {
        if (!isset($this->_request)) {
            $this->_checkConfigClassesInterfaces('request.class', [IRequest::class]);
            $componentClass = $this->getConfig()->getOption('request.class', 'Quadro\Http\Request');
            $this->_request = new $componentClass();
        }
        return $this->_request;
    }
    public static function request(): IRequest
    {
        return self::getInstance()->getRequest();
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated docQuadro\Application::ENV_INDEXumentation)
     * @var array Internal cache for already checked interfaces
     */
    protected array $_checkConfigClassesInterfacesCache = [];

    /**
     * @ignore (do not show up in generated documentation)
     * @param string $configKey
     * @param array $requiredInterfaces
     * @return bool
     * @throws Config\Exception     *
     */
    protected function _checkConfigClassesInterfaces(string $configKey, array $requiredInterfaces): bool
    {
        if(!isset($this->_checkConfigClassesInterfacesCache[$configKey])) {
            $this->_checkConfigClassesInterfacesCache[$configKey] = false;
            if ($this->getConfig()->hasOption($configKey)) {
                $configClass = $this->getConfig()->getOption($configKey);
                $implementedInterfaces = class_implements($configClass);
                foreach ($requiredInterfaces as $requiredInterface) {
                    if (!isset($implementedInterfaces[$requiredInterface])) {
                        throw new ConfigurationException(sprintf(
                            'Configuration Class "%s" does not implements "%s"',
                            $configClass, $requiredInterface
                        ));
                    }
                }
                $this->_checkConfigClassesInterfacesCache[$configKey] = true;
            }
        }
        return $this->_checkConfigClassesInterfacesCache[$configKey] ;
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * Internal reference to the configuration object
     *
     * @ignore (do not show up in generated documentation)
     * @var \Quadro\Config
     */
    protected Config $_config;

    /**
     * Returns Configuration object
     * If not already added creates and adds a default Config Component
     * @return Config
     * @throws Config\Exception
     */
    public function getConfig(): Config
    {
        if (!isset($this->_config)) {
            $this->_config = new Config([]);
        }
        return $this->_config;
    }
    public function setConfig(Config $config): self
    {
        $this->_config = $config;
        return $this;
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var float $_startTime Timestamp the application request is initialized
     */
    private float $_startTime;

    /**
     * Returns the time when the application is initialized
     *
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->_startTime;
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @var float $deltaTime Stores the time passed from the last timestamp
     */
    private float $_deltaTime;

    /**
     * Returns the time passed since the initialization or when __$fromLastCall__ == TRUE  the time past since
     * the last time this function is called.
     * @param  bool $fromLastCall
     * @return float
     */
    public function getDeltaTime(bool $fromLastCall = false): float
    {
        if ($fromLastCall){
            $deltaTime =  microtime(true) - $this->_deltaTime;
            $this->_deltaTime = microtime(true);
        } else {
            $this->_deltaTime = microtime(true);
            $deltaTime =  $this->_deltaTime - $this->_startTime;
        }
        return $deltaTime;
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * The current type of environment: Local/Development, Staging or Production. Defaults to production
     * @return string
     */
    public function getEnvironment(): string
    {
       return Application::environment();
    }
    public static function environment(): string
    {
        if (false === getenv(Application::ENV_INDEX) ) {
            putenv(Application::ENV_INDEX .'=' . Application::ENV_PRODUCTION);
        }
        return getenv(Application::ENV_INDEX);
    }

    public function debug() : bool
    {
        return ($this->getEnvironment() ==  Application::ENV_DEVELOPMENT);
    }


    // -----------------------------------------------------------------------------------------------------------

    /**
     * @see https://www.php.net/manual/en/function.set-exception-handler.php
     *
     * @param Throwable $thrown
     * @throws \Quadro\Exception
     */
    #[NoReturn]
    public function exceptionHandler(Throwable $thrown ): void
    {
        if ($this->debug()) {
            $this->getResponse()->setContent($thrown);
        } else {
            $this->getResponse()->setContent(
                sprintf('%s: %s - %s',
                    $thrown::class,
                    $thrown->getCode(),
                    $thrown->getMessage()
                )
            );
        }
        $this->getResponse()->setStatusCode($thrown->getCode());
        $this->getResponse()->send();
    }

    /**
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     *
     * @param int $number
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @throws \Quadro\Exception
     */
    #[NoReturn]
    public function errorHandler( int $number, string $message, string $file=null, int $line = null): void
    {
        if ($this->getEnvironment() !== Application::ENV_PRODUCTION ) {
            $this->getResponse()->setContent(
                sprintf('ERROR : %s - %s (%s @ %d)',
                   $number,
                   $message,
                   $file,
                   $line
                )
            );
        }
        $this->getResponse()->setStatusCode($number);
        $this->getResponse()->send();
    }

    // -----------------------------------------------------------------------------------------------------------

    const EVENT_BEFORE_DISPATCH = 'Application:BeforeDispatch';
    const EVENT_DISPATCHER_EXCEPTION = 'Application:DispatchException';
    const EVENT_DISPATCHER_NO_MATCH = 'Application:BeforeNoMatch';
    const EVENT_BEFORE_SEND = 'Application:BeforeSend';

    /**
     * @throws Config\Exception
     * @throws \Quadro\Exception
     */
    #[NoReturn]
    public function run(): bool
    {
        try {
            $this->notifyObservers(self::EVENT_BEFORE_DISPATCH, $this->getRequest());

            // Dispatch the request.
            // First Loop through custom dispatchers to handle the request.
            // Dispatchers are treated on a first in first out bases
            $response = false;
            foreach($this->getDispatchers() as $dispatcher) {
                $response = $dispatcher->handleRequest($this->getRequest());
                if ($response !== false) break;
            }

            // Secondly when nothing is found, handle by default dispatcher
            if ($response == false) {
                //print_r( $this->getDefaultDispatcher());
                //exit("Default dispatcher ...");
                $response = $this->getDefaultDispatcher()->handleRequest($this->getRequest());
            }

            // A Dispatcher response can be either a) false(not found), b) IResponse or c) other
            // a) Response still false, there is no match; return a 404
            if ($response === false) {
                $this->notifyObservers(self::EVENT_DISPATCHER_NO_MATCH, $this->getRequest());
                throw new DefaultDispatcherException('Not Found', 404);

            // b) We get a response object back; call send() on it
            } else if(is_object($response) && $response instanceof IResponse) {
                $this->notifyObservers(self::EVENT_BEFORE_SEND, $response);
                $response->send();

            // c) We receive some data, add this to the default response object
            } else {
                $this->getResponse()->setContent($response);
                //print_r($this->getResponse());
                //exit("Default Response ...");
            }

        // Only catch Dispatch exception. Other exceptions are assumed to be
        // caught by the main exception handler
        } catch(Dispatcher\Exception $e){

            // Add the exception to default respond object
            $this->notifyObservers(self::EVENT_DISPATCHER_EXCEPTION, $e);
            $this->getResponse()->setStatusCode($e->getCode());
            $this->getResponse()->setContent($e);
        }

        // Call send on the default response object. Either filled with data(c) or with the
        // Dispatcher error information
        $this->notifyObservers(self::EVENT_BEFORE_SEND, $this->getResponse());
        $this->getResponse()->send();
        return true;

    } // run()

    /**
     * Static shortcut for Application::run()
     *
     * @throws Config\Exception
     * @throws \Quadro\Exception
     */
    #[NoReturn]
    public static function handleRequest():bool
    {
        return self::getInstance()->run();
    }

    protected static function _init(string $applicationPath = ''): void
    {
        if (defined('QUADRO_DIR')) return;

        /**
         * Get the path of the calling script and define this as the Application Folder
         * This can also already be defined by the application
         */
        if(!defined('QUADRO_DIR_APPLICATION')) {
            if (is_dir($applicationPath)) {
                define('QUADRO_DIR_APPLICATION', rtrim($applicationPath, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR);
            } else {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                if (isset($backtrace[0]) && isset($backtrace[0]['file'])) {
                    if (dirname($backtrace[0]['file']) != __DIR__) {
                        define('QUADRO_DIR_APPLICATION', dirname($backtrace[0]['file']) . DIRECTORY_SEPARATOR);
                    }
                }
                if (!defined('QUADRO_DIR_APPLICATION')) {
                    exit('Quadro Initialization Error: Can not find a valid application path. Use define("QUADRO_DIR_APPLICATION", "/application/path")');
                }
            }
        }

        /**
         * Some handy short cuts
         */
        if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
        if (!defined('NAMESPACE_SEPARATOR')) define('NAMESPACE_SEPARATOR', '\\');
        if (!defined('NS')) define('NS', NAMESPACE_SEPARATOR);
        if (!defined('PATH_SEPARATOR')) define('PATH_SEPARATOR', ';');
        if (!defined('PS')) define('PS', PATH_SEPARATOR);

        /**
         * The path of the Quadro Framework
         */
        define(
            'QUADRO_DIR',
            realpath(__DIR__ . DS . '..' . DS . '..' . DS)  .DS
        );

        /**
         * The Application directory must not be the same as this directory
         */
        if (QUADRO_DIR == QUADRO_DIR_APPLICATION){
            exit('Quadro Initialization Error: The Application directory can not be the same as Quadro source Directory');
        }

        /**
         * Quadro Constants for all directories inside the Quadro Api Framework
         */
        define('QUADRO_DIR_CONTROLLERS' , QUADRO_DIR . 'controllers' . DS);
        define('QUADRO_DIR_LIBRARIES' , QUADRO_DIR . 'libraries' . DS);
        define('QUADRO_DIR_RESOURCES' , QUADRO_DIR . 'resources' . DS);
        define('QUADRO_DIR_TESTS' , QUADRO_DIR . 'tests' . DS);
        define('QUADRO_DIR_VENDOR' , QUADRO_DIR . 'vendor' . DS);

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

    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @see https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     * @throws Config\Exception
     */
    #[ArrayShape(
        ['environment' => "string",
            'startTime' => "float",
            'registry' => "\Quadro\Application\Registry",
            'request' => "string",
            'config' => "\Quadro\Config",
        ]
    )]
    public function jsonSerialize(): array
    {
        return [
            'environment' => $this->getEnvironment(),
            'startTime' => $this->getStartTime(),
            'registry' => $this->getRegistry(),
            'request' => $this->getRequest(),
            'config' => $this->getConfig(),
        ];
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @var array List of observers
     */
    protected array $_observers = [];

    /**
     * @var array List of events the observer is subscribed to to listen
     */
    protected array $_observersEvents = [];

    /**
     * Adds an observer to the flow of the Application request handling process
     *
     * @param IObserver $observer
     * @param array $events
     * @return $this
     */
    public function attachObserver(IObserver $observer, array $events = []): self
    {
        $this->_observers[spl_object_hash($observer)] = $observer;
        $this->_observersEvents[spl_object_hash($observer)] = $events;
        return $this;
    }

    /**
     * Removes an observer to the flow of the Application request handling process
     *
     * @param IObserver $observer
     * @return $this
     */
    public function detachObserver(IObserver $observer): self
    {
        unset(
            $this->_observers[spl_object_hash($observer)],
            $this->_observersEvents[spl_object_hash($observer)]
        );
        return $this;
    }

    /**
     * Updates all the observers for __$event__
     *
     * @param string $event
     * @param mixed|null $context
     */
    protected function notifyObservers(string $event, mixed $context = null):void
    {
        foreach($this->_observers as $observer) {

           if ( count($this->_observersEvents[spl_object_hash($observer)])) {
                if (isset($this->_observersEvents[spl_object_hash($observer)][$event])) {
                    $observer->onEvent($event, $context);
                }
            } else {
                $observer->onEvent($event, $context);
            }
        }
    }


    /**
     * Returns the schema and domain for the current application
     *
     * Examples:
     *   https://www.example.com
     *   http://localhost:8080
     *
     * @return string
     * @throws Config\Exception
     */
    public function getUrlRoot(): string
    {
        return strtolower($this->getRequest()->getScheme() ). '://' .  $this->getRequest()->getHost();
    }


} // class