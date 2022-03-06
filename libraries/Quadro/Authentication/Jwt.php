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
use Quadro\Authentication\Exception as Exception;
use Quadro\Config as Config;
use Quadro\Helpers\Text;
use Quadro\Request\EnumRequestMethods;
use Quadro\RequestInterface;
use Quadro\Response\EnumLinkRelations as Link;

/**
 * Authentication with JWT
 */
class Jwt extends Authentication
{

    /**
     * @var string $_secret Internal storage for the private key.
     */
    protected string $_secret;

    /**
     * Returns the private key, defaults to "Humpty Dumpty Set On A Wall".
     *
     * @return string
     * @throws Config\Exception
     */
    #[Config\Key('authentication.secret', 'Humpty Dumpty Set On A Wall', 'Private encryption key')]
    public function getSecret(): string
    {
        if(!isset($this->_secret)) {
            $this->_secret = Application::getInstance()->getConfig()->getOption('authentication.secret', 'Humpty Dumpty Set On A Wall');
        }
        return $this->_secret;
    }

    /**
     * Changes the private key
     *
     * @param string $secret
     * @return static
     */
    public function setSecret(string $secret): static
    {
        $this->_secret = $secret;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * @var string
     */
    protected string $_fileName;

    /**
     * @return string
     * @throws Config\Exception
     */
    #[Config\Key('authentication.filename', 'users.csv', 'Name of the file to store users credentials in absence of a database')]
    public function getFileName(): string
    {
        if(!isset($this->_fileName)) {
            $this->_fileName = Application::getInstance()->getConfig()->getOption('authentication.filename', 'users.csv');
        }
        return $this->_fileName;
    }

    /**
     * @param string $fileName
     * @return static
     */
    public function setFileName(string $fileName): static
    {
        $this->_fileName = $fileName;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * @param string $event
     * @param mixed|null $context
     * @return void
     * @throws Config\Exception
     * @throws \Quadro\Authentication\Exception
     * @throws \Quadro\Exception
     */
    public function onEvent(string $event, mixed $context = null): void
    {
        if ($event === Application::EVENT_BEFORE_DISPATCH) {

            // shortcut to the singleton instance
            $app = Application::getInstance();

            // sign-up and sign-in are always allowed
            if ( $app->getRequest()->getPath() !== $this->getAuthenticateUrl() &&
                 $app->getRequest()->getPath() !== $this->getRegisterUrl()
            ) {

                // get the JWT in the header if any, defaults to an empty string
                $jwt = '';
                $header = explode(' ', $app->getRequest()->getHeader('Authorization'));
                if (count($header) == 2 && $header[0] == 'Bearer') {
                    $jwt = $header[1];
                }

                // validate the jwt
                $jwtValidation = $this->jwtIsValid($jwt);

                // when valid renew the JWT and send in the WWW-Authenticate header
                if ($jwtValidation == EnumAuthenticateErrors::None) {
                    $app->getResponse()->setHeader('WWW-Authenticate: Bearer '.  $this->_jwtRenew($jwt));
                } else {
                    $app->getResponse()
                        ->addLink(
                            Link::Next,
                            $app->getUrlRoot() . $this->getRegisterUrl(),
                            EnumRequestMethods::POST->name
                        )
                        ->addLink(
                            Link::Next,
                            $app->getUrlRoot() . $this->getAuthenticateUrl(),
                            EnumRequestMethods::POST->name
                        )->setHeader(
                            'WWW-Authenticate: Bearer realm="'.$app->getUrlRoot().'", error="Invalid Token", error-description="'.$jwtValidation->getMessage().'"',
                            true, 401
                        );
                    throw new Exception($context . ' is unauthorized', 401);
                }

                // TODO if we have a valid JWT check ACL
            }
        }
    }

    // -----------------------------------------------------------------------------

    /**
     * @param string $oldJwt
     * @return string
     * @throws Config\Exception|\Quadro\Authentication\Exception
     */
    protected function _jwtRenew(string $oldJwt): string
    {
        $tokenParts = explode('.', $oldJwt);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        return $this->_jwtCreate($payload['data'], (int) $payload['version']++);
    }

    /**
     * @param array<string,string>|string $data
     * @param int $version
     * @return string
     * @throws Config\Exception
     * @throws \Quadro\Authentication\Exception
     */
    #[Config\Key('authentication.expirationPeriod', (60 * 15), 'Expiration time of the token in seconds')]
    protected function _jwtCreate(array|string $data, int $version=1): string
    {
        $headers = [
            'alg' => 'HS256',
            'type' => 'JWT',
        ];
        $issuedAt = time();
        $expirationPeriod = Application::getInstance()->getConfig()->getOption('authentication.expirationPeriod',  (60 * 15));// default 15 minutes
        $payload = [
            'data' => $data,
            'version' => $version,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expirationPeriod
        ];

        $jwt = $this->_jwtGet($headers, $payload);
        return  $jwt['token'];
    }

    /**
     * @param string $jwt
     * @return EnumAuthenticateErrors
     * @throws Config\Exception|\Quadro\Authentication\Exception
     */
    public function jwtIsValid(string $jwt): EnumAuthenticateErrors
    {
        if (trim($jwt) == '') return EnumAuthenticateErrors::TokenIsEmpty;

        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3 ) return EnumAuthenticateErrors::TokenInvalidFormat;

        $headers = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signatureProvided = $tokenParts[2];

        // check headers
        if(!is_array($headers)) return EnumAuthenticateErrors::TokenDecodeError;
        if(!is_array($payload)) return EnumAuthenticateErrors::TokenDecodeError;

        // check existence user
        if (false === $this->getUserData($payload['data']))  return EnumAuthenticateErrors::UnknownUser;

        // check expiration
        if(!isset($payload['exp'])) return EnumAuthenticateErrors::TokenExpirationMissing;
        if (($payload['exp'] - time()) < 0) return EnumAuthenticateErrors::TokenExpired;

        // check signature
        $jwt = $this->_jwtGet($headers, $payload);
        if ($jwt['signature'] !== $signatureProvided) return EnumAuthenticateErrors::TokenInvalid;

        return EnumAuthenticateErrors::None;
    }

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $payload
     * @return array{header: string, payload: string, signature: string, token: string}
     * @throws Config\Exception
     * @throws \Quadro\Authentication\Exception
     */
    protected function _jwtGet(array $headers, array $payload): array
    {
        $payloadJsonEncoded = json_encode($payload);
        $headersJsonEncoded = json_encode($headers);

        if ( $payloadJsonEncoded === false || $headersJsonEncoded === false ) {
            throw new Exception('JWT Headers or payload not properly formatted to be Json-ized');
        }

        $jwt = [];
        $jwt['header'] = Text::base64UrlEncode($headersJsonEncoded);
        $jwt['payload'] = Text::base64UrlEncode($payloadJsonEncoded);
        $signature = hash_hmac(
            'SHA256',
            "{$jwt['header'] }.{$jwt['payload']}",
            $_SERVER['REMOTE_ADDR'] . $this->getSecret(),
            true
        );
        $jwt['signature'] = Text::base64UrlEncode($signature);
        $jwt['token'] = "{$jwt['header']}.{$jwt['payload']}.{$jwt['signature']}";
        return $jwt;
    }


    // -----------------------------------------------------------------------------
    //  registration and authentication hooks
    // -----------------------------------------------------------------------------

    /**
     * @return bool
     */
    protected function _exceedsMaxLoginAttempts(): bool
    {
        // For now, we allow all attempts
        return false;
    }

    /**
     * @return bool
     */
    protected function _exceedsMaxRegisterAttempts(): bool
    {
        // For now, we allow all attempts
        return false;
    }

    /**
     * # 1.
     * @param array<int|string, string> $credentials
     * @return bool
     * @throws Config\Exception
     */
    protected function _getCredentials(array &$credentials): bool
    {
        $request = Application::getInstance()->getRequest();
        $postData = $request->getRawBody();
        $success = false;
        if ($request->getMethod() === EnumRequestMethods::POST) {
            $data = json_decode($postData, true);
            if (false !== $data){
                $emailIndex = 'email';
                $passIndex = 'pass';
                $credentials[$emailIndex] = strtolower($data[$emailIndex]??'');
                $credentials[$passIndex] = $data[$passIndex]??'';
                $success = true;
            }
        }

        return $success;
    }

    /**
     * # 2.
     * @param array<int|string, string> $credentials
     * @return bool
     */
    protected function _meetRequirements(array &$credentials): bool
    {
        reset($credentials);  $emailIndex = key($credentials);
        next($credentials); $passIndex = key($credentials);
        $credentials[$emailIndex] = filter_var($credentials[$emailIndex] , FILTER_SANITIZE_EMAIL);
        $credentials[$emailIndex] = filter_var($credentials[$emailIndex] , FILTER_VALIDATE_EMAIL);
        $credentials[$passIndex]  = (strlen((string) $credentials[$passIndex]) < 8 ) ? false : $credentials[$passIndex];
        return ! ($credentials[$emailIndex] === false || $credentials[$passIndex] === false);
    }

    /**
     *  # 3.
     * @param array<int|string, string> $credentials
     * @return bool
     * @throws Config\Exception
     */
    protected function _isUnique(array $credentials): bool
    {
        $email = (string) reset($credentials);
        $userData = $this->getUserData($email);
        return (false === $userData);
    }

    /**
     * # 4.
     *
     * @param array<int|string, string> $credentials
     * @return bool|array{jwt: string}
     * @throws Config\Exception|\Quadro\Authentication\Exception
     */
    protected function _register(array $credentials): bool|array
    {
        $email = (string) reset($credentials);
        $pass = (string) next($credentials);
        $pass =  hash_hmac('SHA256', $pass,$email . $_SERVER['REMOTE_ADDR'] . $this->getSecret() );
        $userData = $this->setUserData(['email' => $email, 'pass' => $pass]);

        if (is_array($userData)) {
            return ['jwt' => $this->_jwtCreate((string) reset($userData))];
        }
        return false;
    }

    /**
     * @param array<string, string> $userData
     * @return bool|array<string, string>
     * @throws Config\Exception
     */
    public function setUserData(array $userData): bool|array
    {
        if (!is_writable(QUADRO_DIR_APPLICATION)) return false;

        // create file if not exists
        $fileHandle = fopen(QUADRO_DIR_APPLICATION . $this->getFileName(), 'a+');
        if (is_resource($fileHandle)) {
            $email    = (string) reset($userData);
            $password = (string) next($userData);
            if (false === fwrite($fileHandle, "{$email},{$password}\n")) return false;
            if (false === fclose($fileHandle)) return false;
            return $userData;
        }
        return false;
    }

    /**
     * @param mixed $identifier
     * @return bool|array<string, string>
     * @throws Config\Exception
     */
    public function getUserData(mixed $identifier): bool|array
    {
        if (!is_writable(QUADRO_DIR_APPLICATION)) return false;
        if (!is_file(QUADRO_DIR_APPLICATION . $this->getFileName())) return false;

        $userData = false;
        $fileHandle = fopen(QUADRO_DIR_APPLICATION . $this->getFileName(), 'a+');
        if (is_resource($fileHandle)) {
            while (($userData = fgetcsv($fileHandle, 1000, ",")) !== false) {
                $email = reset($userData);
                if ($email == $identifier) {
                    break;
                }
            }
            fclose($fileHandle);
        }

        return $userData;
    }

    /**
     * @param array<int|string, string> $credentials
     * @return bool|array{jwt: string}
     * @throws Config\Exception|\Quadro\Authentication\Exception
     */
    protected function _authenticate(array $credentials): bool|array
    {
        $login = false;
        $emailGiven = (string) reset($credentials);
        $passGiven = (string) next($credentials);
        $userData = $this->getUserData($emailGiven);

        if (is_array($userData) && count($userData) == 2) {
            $emailStored = (string) reset($userData);
            $passStored  = (string) next($userData);
            if ($passStored ==  hash_hmac(
                    'SHA256',
                    $passGiven,
                    $emailGiven . $_SERVER['REMOTE_ADDR'] . $this->getSecret()
                )) {
                $login =  ['jwt' => $this->_jwtCreate($emailStored)];
            };
        }
        return  $login;
    }


}




/**
 * Reset waiting period after multiple log in attempts
 * /
public static int $_nrOfLoginThrottlePeriod = 60 * 60 * 10; // 10 minutes

/**
 * Attempts before throttling is started
 * /
public static int $_nrOfFreeLoginAttempts = 3;
 */