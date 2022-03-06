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


use JsonSerializable;
use Quadro\Application\ComponentInterface as IComponent;
use Quadro\Application\ObserverInterface as IObserver;
use Quadro\Application\Registry as Registry;
use Quadro\Config as Config;
use Quadro\Config\Exception as ConfigurationException;
use Quadro\Dispatcher\DefaultDispatcherException as DefaultDispatcherException;
use Quadro\DispatcherInterface as IDispatcher;
use Quadro\Exception as Exception;
use Quadro\RequestInterface as IRequest;
use Quadro\ResponseInterface as IResponse;
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
     * @param array<string, string|string[]>|\Quadro\Config|null $config
     * @return Application
     * @throws Config\Exception
     */
    public static function getInstance(array|Config $config = null): Application
    {
        if(!isset(Application::$_instance)) {
            Application::_init();
            if (is_array($config)) {
                $config = new Config($config);
            }
            Application::$_instance = new Application($config);
        }
        return Application::$_instance;
    }

    // -----------------------------------------------------------------------------------------------------------

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
     * @throws Registry\Exception
     * @see Registry::add()
     */
    public function addComponent(IComponent|callable $component, string $index = null): IComponent
    {
        return $this->getRegistry()->add($component, $index);
    }

    /**
     * Shortcut to Registry::get();
     *
     * @param string $index
     * @return IComponent
     * @throws Registry\Exception
     * @see Registry::get()
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
     * @throws Registry\Exception
     */
    public function removeComponent(string $index): bool
    {
        return $this->getRegistry()->remove($index);
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var IDispatcher Internal reference to default dispatcher
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
    #[Config\Key('dispatchers.default.class', 'Quadro\Dispatcher\Files', 'Default dispatcher class')]
    public function getDefaultDispatcher(): IDispatcher
    {
        if (!isset($this->_defaultDispatcher)) {
            $componentClass = $this->getConfig()->getOption('dispatchers.default.class', 'Quadro\Dispatcher\Files');
            if (false === $this->_checkRequiredInterfaces($componentClass, [IDispatcher::class]) ) {
                throw new Config\Exception(sprintf('Missing Interface %s in dispatchers.default.class: %s', IDispatcher::class, $componentClass));
            }
            $this->_defaultDispatcher = new $componentClass($this->getConfig()->getOption('dispatchers.default', [
                'path' => QUADRO_DIR_CONTROLLERS
            ]));
        }
        return $this->_defaultDispatcher;
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @var array<string, IDispatcher> Protected list of custom dispatchers
     */
    protected array $_dispatchers;

    /**
     * @return array<string, IDispatcher>
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
     * @return static
     * @throws ConfigurationException
     */
    #[Config\Key('dispatchers.default', 'default', 'Default dispatcher')]
    protected function _initCustomDispatchers(): static
    {
        if (!isset($this->_dispatchers)) {
            $this->_dispatchers = [];
            foreach($this->getConfig()->getOption('dispatchers', []) as $dispatcherIndex => $dispatcherOptions) {
                if ($dispatcherIndex  == 'default') continue;
                $componentClass = $this->getConfig()->getOption('dispatchers.'.$dispatcherIndex.'.class');
                if (false === $this->_checkRequiredInterfaces($componentClass, [IDispatcher::class]) ) {
                    throw new Config\Exception(sprintf(
                        'Missing Interface %s in dispatchers.%s.class: %s',
                        IDispatcher::class,
                        $dispatcherIndex,
                        $componentClass)
                    );
                }
                $dispatcher = new  $componentClass($dispatcherOptions);
                $this->_dispatchers[$dispatcherIndex] = $dispatcher;
            }
        }
        return $this;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @param string|null $index
     * @return static
     * @throws Config\Exception
     */
    public function addDispatcher(IDispatcher $dispatcher, string $index = null): static
    {
        $this->_initCustomDispatchers(); // lazy load configuration defined dispatchers
        $this->_dispatchers[((empty($index))? get_class($dispatcher): $index)] = $dispatcher;
        return $this;
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var Response Internal reference to the default Response object
     */
    protected Response $_response;

    /**
     * Returns the Response Component.
     * If not already added creates and adds a default Response Component
     *
     * @return IResponse
     * @throws Exception
     */
    #[Config\Key('response.class', 'Quadro\Response\Json', 'Default Response class')]
    public function getResponse(): IResponse
    {
        if (!isset($this->_response)) {
            $componentClass = $this->getConfig()->getOption('response.class', 'Quadro\Response\Json');
            if (false === $this->_checkRequiredInterfaces($componentClass, [IResponse::class])) {

                // when adding a custom response class results in an error we don't have a response class
                // so in case of a Config\Exception we set the response class to a default JSON Response
                $this->_response = new Response\Json();
                throw new Config\Exception(sprintf(
                        'Missing Interface %s in response.class: %s',
                        IDispatcher::class,
                        $componentClass)
                );
            }
            $this->_response = new $componentClass();
        }
        return $this->_response;
    }

    /**
     * @throws \Quadro\Exception
     * @throws ConfigurationException
     */
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
    #[Config\Key('request.class', 'Quadro\Request\Http', 'Default Request class')]
    public function getRequest(): IRequest
    {
        if (!isset($this->_request)) {
            $componentClass = $this->getConfig()->getOption('request.class', 'Quadro\Request\Http');
            if (false === $this->_checkRequiredInterfaces($componentClass,[IRequest::class]) ) {
                throw new Config\Exception(sprintf(
                    'Missing Interface %s in request.class: %s',
                    IRequest::class,
                    $componentClass)
                );
            }
            $this->_request = new $componentClass();
        }
        return $this->_request;
    }

    /**
     * @throws ConfigurationException
     */
    public static function request(): IRequest
    {
        return self::getInstance()->getRequest();
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated docQuadro\Application::ENV_INDEXumentation)
     * @var array<string, bool> Internal cache for already checked interfaces
     */
    protected array $_checkClassesInterfacesCache = [];

    /**
     * @ignore (do not show up in generated documentation)
     * @param string $class
     * @param array<int, string> $requiredInterfaces
     * @return bool
     */
    protected function _checkRequiredInterfaces(string $class, array $requiredInterfaces): bool
    {
        if(!isset($this->_checkClassesInterfacesCache[$class])) {
            $this->_checkClassesInterfacesCache[$class] = false;
            $implementedInterfaces = class_implements($class);
            $counter = 0;

            if(is_array($implementedInterfaces)) {
                foreach ($requiredInterfaces as $requiredInterface) {
                    if (isset($implementedInterfaces[(string)$requiredInterface])) {
                        $counter++;
                    }
                }
            }

            $this->_checkClassesInterfacesCache[$class] = ($counter == count($requiredInterfaces));
        }

        return $this->_checkClassesInterfacesCache[$class];
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
        return (string) getenv(Application::ENV_INDEX);
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
     * @return never
     */
    public function exceptionHandler(Throwable $thrown ): never
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
        exit();
    }

    /**
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     *
     * @param int $number
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @return never
     * @throws \Quadro\Exception
     */
    public function errorHandler( int $number, string $message, string $file=null, int $line = null): never
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
        exit();
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
            } else if($response instanceof IResponse) {
                $this->notifyObservers(self::EVENT_BEFORE_SEND, $response);
                $response->send();

            // c) We receive some data, add this to the default response object
            } else {

                if (is_string($response)) {
                    $response = trim($response);
                    if (strlen($response)) {
                        $this->getResponse()->setContent($response, true);
                    }
                }
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

        // Call sent on the default response object. Either filled with data(c) or with the
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
    public static function handleRequest():bool
    {
        return self::getInstance()->run();
    }

    /**
     * @param string $applicationPath
     * @return void
     */
    protected static function _init(string $applicationPath = ''): void
    {
        if (defined('QUADRO_DIR')) return;
        require_once __DIR__ . '/../../bootstrap.php';
    }

    // -----------------------------------------------------------------------------------------------------------

    /**
     * @see https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed
     * @throws ConfigurationException
     */
    public function jsonSerialize(): mixed
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
     * @var array<string, IObserver> List of observers
     */
    protected array $_observers = [];

    /**
     * @var array<string, array<int, string>> List of events the observer is subscribed to to listen
     */
    protected array $_observersEvents = [];

    /**
     * Adds an observer to the flow of the Application request handling process
     *
     * @param IObserver $observer
     * @param array<int, string> $events
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
                if (is_array($this->_observersEvents[spl_object_hash($observer)])) {
                    if (in_array($event, $this->_observersEvents[spl_object_hash($observer)])) {
                        $observer->onEvent($event, $context);
                    }
                }
            } else {
                $observer->onEvent($event, $context);
            }
        }
    }

    // -----------------------------------------------------------------------------------------------------------

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
        return $this->getRequest()->getScheme()->name. '://' .  $this->getRequest()->getHost();
    }


} // class