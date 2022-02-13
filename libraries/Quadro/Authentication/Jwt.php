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

namespace Quadro\Authentication;

use Quadro\Application;
use Quadro\Authentication;
use Quadro\Config as Config;
use DateTimeImmutable;
use \Firebase\JWT\JWT as FireBaseJwt;

class Jwt extends Authentication
{


    protected string $_secretKey;
    public function getSecretKey(): string
    {
        if (!isset($this->_secretKey)) {
            $config = Application::getInstance()->getComponent(Config::getComponentName());
            $this->_secretKey = $config->getOption('authentication.jwt.secretKey', 'DefaultSecretKeyPleaseChange!');
        }
        return $this->_secretKey;
    }
    public function setSecretKey(string $secretKey): self
    {
        $this->_secretKey = $secretKey;
        return $this;
    }



    protected DateTimeImmutable $_issuedAt;
    public function getIssuedAt(): DateTimeImmutable
    {
        if(!isset($this->_issuedAt)) {
            $this->_issuedAt  = new DateTimeImmutable();
        }
        return $this->_issuedAt;
    }



    protected string $_expirePeriod;
    public function getExpirePeriod(): string
    {
        if(!isset($this->_expirePeriod)) {
            $config = Application::getInstance()->getComponent(Config::getComponentName());
            $this->_expirePeriod = $config->getOption('authentication.jwt.expire', '+6 minutes');
        }
        return $this->_expirePeriod;
    }
    public function setExpirePeriod(string $expirePeriod): self
    {
        $this->_expirePeriod = $expirePeriod;
        return $this;
    }



    protected int $_expire;
    public function getExpire(): int
    {
        if (!isset($this->_expire)) {
            $this->_expire = $this->getIssuedAt()->modify($this->getExpirePeriod())->getTimestamp();
        }
        return $this->_expire;
    }



    protected string $serverName;
    public function getServerName(): string
    {
        return $this->serverName ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }



    public function authenticate(string $publicPhrase, string $privatePhrase): bool|string
    {
        $data = [
            'iat'  => $this->getIssuedAt()->getTimestamp(),         // Issued at: time when the token was generated
            'iss'  => $this->getServerName(),                       // Issuer
            'nbf'  => $this->getIssuedAt()->getTimestamp(),         // Not before
            'exp'  => $this->getExpire(),                           // Expire
            'userName' => null,                                     // User name
        ];

        if (Application::getInstance()->getEnvironment() === Application::ENV_DEVELOPMENT) {
            if ($publicPhrase == 'bogus@localhost.com' && $privatePhrase == hash('sha256', 'administrator')){
                $data['userName'] = 'QUADRO-DEVELOPER';
            }
        } else {
            // todo get the username from the database of things

        }

        if(null ==  $data['userName']) return false;

        return FireBaseJwt::encode(
            $data,
            $this->getSecretKey(),
            'HS512'
        );
    }



    /**
     * Reset waiting period after multiple log in attempts
     */
    public static int $nrOfLoginThrottlePeriod = 60 * 60 * 10; // 10 minutes

    /**
     * Attempts before throttling is started
     */
    public static int $nrOfFreeLoginAttempts = 3;



}