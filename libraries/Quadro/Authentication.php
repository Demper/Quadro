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
use Quadro\Authentication\EnumRegisterErrors;
use Quadro\Http\Request as Request;
use Quadro\Application\ComponentInterface;
use Quadro\Config as Config;
use Quadro\Application\ObserverInterface;
use Quadro\Authentication\Exception as Exception;
use Quadro\Http\Response\EnumLinkRelations as Link;

use \Firebase\JWT\JWT;


/**
 * Handles JWT authentication
 */
abstract class Authentication implements ComponentInterface, ObserverInterface, AuthenticationInterface
{

    public function __construct()
    {
        Application::getInstance()->attachObserver($this);
    }

    protected string $_authenticateUri;
    public function getAuthenticateUri(): string
    {
        if(!isset($this->_authenticateUri)) {
            $this->_authenticateUri = Application::getInstance()->getConfig()->getOption('authentication.authenticateUri', '/accounts/authenticate');
        }
        return $this->_authenticateUri;
    }
    public function setAuthenticateUri(string $authenticateUri): self
    {
        $this->_authenticateUri = $authenticateUri;
        return $this;
    }

    protected string $_registerUri;
    public function getRegisterUri(): string
    {
        if(!isset($this->_registerUri)) {
            $this->_registerUri = Application::getInstance()->getConfig()->getOption('authentication.registerUri', '/accounts/register');
        }
        return $this->_registerUri;
    }
    public function setRegisterUri(string $registerUri): self
    {
        $this->_registerUri = $registerUri;
        return $this;
    }

    /**
     * @param array $credentials
     * @return EnumRegisterErrors|array Error or array with newly registered user info
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

        if (false === ($user = $this->_register($credentials))) {
            return EnumRegisterErrors::Unexpected;
        }

        return $user;
    }

    /**
     * @param array $credentials
     * @return bool|string
     */
    final public function authenticate(array $credentials = [] ): bool|string
    {
        if ($this->_exceedsMaxLoginAttempts()) {
            return false;
        }

        if (count($credentials) < 2){
            if( !$this->_getCredentials($credentials)) {
                return false;
            }
        }

        return $this->_authenticate($credentials);
    }

    // register and authentication hooks
    abstract protected function _exceedsMaxRegisterAttempts(): bool;
    abstract protected function _exceedsMaxLoginAttempts(): bool;
    abstract protected function _getCredentials(array &$credentials): bool;
    abstract protected function _meetRequirements(array &$credentials): bool;
    abstract protected function _isUnique(array $credentials): bool;
    abstract protected function _register(array $credentials): bool|array;
    abstract protected function _authenticate(array $credentials): bool|string;

    /**
     * @return string
     */
    public static function getComponentName(): string
    {
        return 'Quadro\Authentication';
    }



}