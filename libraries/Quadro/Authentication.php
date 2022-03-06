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
use Quadro\Authentication\EnumAuthenticateErrors;
use Quadro\Authentication\EnumRegisterErrors;
use Quadro\Application\ComponentInterface;
use Quadro\Application\ObserverInterface;

/**
 * Handles authentication
 */
abstract class Authentication implements ComponentInterface, ObserverInterface, AuthenticationInterface
{

    /**
     * Observe Application on  initialization
     *
     * @throws Config\Exception
     */
    public function __construct()
    {
        Application::getInstance()->attachObserver($this);
    }

    // -----------------------------------------------------------------------------

    /**
     * @var string
     */
    protected string $_authenticateUrl;

    /**
     * @return string
     * @throws Config\Exception
     */
    #[Config\Key('authentication.authenticateUrl', '/accounts/authenticate', 'The URL to receive authentication details.')]
    public function getAuthenticateUrl(): string
    {
        if(!isset($this->_authenticateUrl)) {
            $this->_authenticateUrl = Application::getInstance()->getConfig()->getOption('authentication.authenticateUrl', '/accounts/authenticate');
        }
        return $this->_authenticateUrl;
    }

    /**
     * Sets the Authentication Url
     *
     * @param string $authenticateUrl
     * @return $this
     */
    public function setAuthenticateUrl(string $authenticateUrl): self
    {
        $this->_authenticateUrl = $authenticateUrl;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * @var string
     */
    protected string $_registerUrl;

    /**
     * @return string
     * @throws Config\Exception
     */
    #[Config\Key('authentication.registerUrl', '/accounts/register', 'The URL to receive authentication registration details.')]
    public function getRegisterUrl(): string
    {
        if(!isset($this->_registerUrl)) {
            $this->_registerUrl = Application::getInstance()->getConfig()->getOption('authentication.registerUrl', '/accounts/register');
        }
        return $this->_registerUrl;
    }

    /**
     * Sets the Authentication Registration Url
     *
     * @param string $registerUrl
     * @return $this
     */
    public function setRegisterUrl(string $registerUrl): self
    {
        $this->_registerUrl = $registerUrl;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * Returns an array with account information on success, an EnumRegisterErrors otherwise
     *
     * @param array<int|string, string> $credentials
     * @return EnumRegisterErrors|array<string, string>
     * @throws Config\Exception
     */
    final public function register(array $credentials = [] ): EnumRegisterErrors|array
    {
        if ($this->_exceedsMaxRegisterAttempts()) {
            return EnumRegisterErrors::ExceedsMaxAttempts;
        }

        if (count($credentials) < 2){
            if( !$this->_getCredentials($credentials)) {
                return EnumRegisterErrors::NoCredentials;
            }
        }

        if (!$this->_meetRequirements($credentials)) {
            return EnumRegisterErrors::CredentialsDoesNotMeetRequirements;
        }

        if (!$this->_isUnique($credentials)) {
            return EnumRegisterErrors::NotUnique;
        }

        $userData = $this->_register($credentials);
        if (!is_array($userData)) {
            return EnumRegisterErrors::Unexpected;
        }

        return $userData;
    }

    /**
     * Returns an array with account information on success, an EnumAuthenticateErrors otherwise
     *
     * @param array<int|string, string> $credentials
     * @return EnumAuthenticateErrors|array<string, string>
     * @throws Config\Exception
     */
    final public function authenticate(array $credentials = [] ): EnumAuthenticateErrors|array
    {
        if ($this->_exceedsMaxLoginAttempts()) {
            return EnumAuthenticateErrors::ExceedsMaxAttempts;
        }

        if (count($credentials) < 2){
            if( !$this->_getCredentials($credentials)) {
                return EnumAuthenticateErrors::NoCredentials;
            }
        }

        $userData = $this->_authenticate($credentials);
        if (!is_array($userData)) {
            return EnumAuthenticateErrors::Failed;
        }

        return $userData;
    }

    // register and authentication hooks

    /**
     * @return bool
     */
    abstract protected function _exceedsMaxRegisterAttempts(): bool;

    /**
     * @return bool
     */
    abstract protected function _exceedsMaxLoginAttempts(): bool;

    /**
     * @param array<int|string, string> $credentials
     * @return bool
     * @throws Config\Exception
     */
    abstract protected function _getCredentials(array &$credentials): bool;

    /**
     * @param array<int|string, string> $credentials
     * @return bool
     */
    abstract protected function _meetRequirements(array &$credentials): bool;

    /**
     * @param array<int|string, string> $credentials
     * @return bool
     * @throws Config\Exception
     */
    abstract protected function _isUnique(array $credentials): bool;

    /**
     * @param array<int|string, string> $credentials
     * @return bool|array{jwt: string}
     * @throws Config\Exception
     */
    abstract protected function _register(array $credentials): bool|array;

    /**
     * @param array<int|string, string> $credentials
     * @return bool|array{jwt: string}
     * @throws Config\Exception
     */
    abstract protected function _authenticate(array $credentials): bool|array;

    /**
     * @return string
     */
    public static function getSingletonName(): string
    {
        return 'Quadro\Authentication';
    }


}