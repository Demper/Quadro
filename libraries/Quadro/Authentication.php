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

use Quadro\Application as Application;
use Quadro\Application\ComponentInterface;
use Quadro\Config as Config;
use Quadro\Application\ObserverInterface;
use Quadro\Authentication\Exception as Exception;

/**
 * Handles JWT authentication
 */
class Authentication implements ComponentInterface, ObserverInterface, AuthenticationInterface
{

    protected string $_authenticateUri;
    public function getAuthenticateUri(): string
    {
        if(!isset($this->_authenticateUri)) {
            $this->_authenticateUri = Application::getInstance()->getConfig()->getOption('authentication.authenticateUri', '/authenticate');
        }
        return $this->_authenticateUri;
    }
    public function setAuthenticateUri(string $authenticateUri): self
    {
        $this->_authenticateUri = $authenticateUri;
        return $this;
    }




    /**
     * Receives application events
     *
     * @param string $event
     * @param mixed|null $context
     * @throws Application\RegistryException
     * @throws Config\Exception
     * @throws Exception
     * @throws \Quadro\Exception
     */
    public function onEvent(string $event, mixed $context = null): void
    {
        if ($event === Application::EVENT_BEFORE_HANDLE_REQUEST) {
            if (Application::getInstance()->getRequest()->getPath() !== $this->getAuthenticateUri()) {
                if (!$this->validateAccessToken()) {
                    $objResponse = Application::getInstance()->getResponse();
                    $objResponse->addMessage("Event = $event");
                    $objResponse->addMessage("Context = $context");
                    $objResponse->addMessage("No Valid AccessToken");
                    $objResponse->addLink(
                        'authentication',
                        $this->getAuthenticateUri(),
                        \Quadro\Http\Request::METHOD_POST
                    );
                    throw new Exception($context . ' is unauthorized', 401);
                }
            }
        }
    }



    public function authenticate(string $publicPhrase, string $privatePhrase): bool|string
    {
        if (Application::getInstance()->getEnvironment() === Application::ENV_DEVELOPMENT) {
            if ($publicPhrase == 'bogus@localhost.com' && $privatePhrase == hash('sha256', 'administrator')){
                return 'QUADRO-DEVELOPER';
            }
        }
        return false;
    }



    public function validateAccessToken(): bool
    {
        if (Application::getInstance()->getEnvironment() === Application::ENV_DEVELOPMENT) {
            return true;
            return (Application::getInstance()->getRequest()->getHeaders('Authorization') == 'QUADRO-DEVELOPER');
        }
        return false;
    }



    /**
     * @return string
     */
    public static function getComponentName(): string
    {
        return 'Quadro\Authentication';
    }



}