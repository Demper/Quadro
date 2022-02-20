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
use Quadro\Http\Request as Request;
use Quadro\Http\Response\EnumLinkRelations as Link;

class Jwt extends Authentication
{

    protected string $_secret;
    public function getSecret(): string
    {
        if(!isset($this->_secret)) {
            $this->_secret = Application::getInstance()->getConfig()->getOption('authentication.secret', 'Humpty Dumpty Set On A Wall');
        }
        return $this->_secret;
    }
    public function setSecret(string $secret): self
    {
        $this->_secret = $secret;
        return $this;
    }

    protected string $_fileName;
    public function getFileName(): string
    {
        if(!isset($this->_fileName)) {
            $this->_fileName = Application::getInstance()->getConfig()->getOption('authentication.filename', 'users.csv');
        }
        return $this->_fileName;
    }
    public function setFileName(string $fileName): self
    {
        $this->_fileName = $fileName;
        return $this;
    }

    protected string $_publicFieldName;
    public function getPublicFieldName(): string
    {
        if(!isset($this->_publicFieldName)) {
            $this->_publicFieldName = Application::getInstance()->getConfig()->getOption('authentication.publicFieldName', 'email');
        }
        return $this->_publicFieldName;
    }
    public function setPublicFieldName(string $publicFieldName): self
    {
        $this->_publicFieldName = $publicFieldName;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * @throws Config\Exception
     * @throws \Quadro\Exception
     * @throws \Quadro\Authentication\Exception
     */
    public function onEvent(string $event, mixed $context = null): void
    {
        if ($event === Application::EVENT_BEFORE_DISPATCH) {

            // shortcut to the singleton instance
            $app = Application::getInstance();

            // sign-up and sign-in are always allowed
            if ( $app->getRequest()->getPath() !== $this->getAuthenticateUri() &&
                 $app->getRequest()->getPath() !== $this->getRegisterUri()
            ) {

                // get the JWT in the header if any, defaults to an empty string
                $jwt = '';
                $header = explode(' ',$app->getRequest()->getHeaders('Authorization'));
                if (count($header) == 2 && $header[0] == 'Bearer') {
                    $jwt = $header[1];
                }

                // validate the jwt
                if (!$this->jwtIsValid($jwt)) {
                    $app->getResponse()
                        ->addLink(
                            Link::Next,
                            $app->getUrlRoot() . $this->getRegisterUri(),
                            Request::METHOD_POST
                        )
                        ->addLink(
                            Link::Next,
                            $app->getUrlRoot() . $this->getAuthenticateUri(),
                            Request::METHOD_POST
                        );
                    throw new Exception($context . ' is unauthorized', 401);
                }

                // TODO if we have a valid JWT check ACL
            }
        }
    }

    // -----------------------------------------------------------------------------

    public function _authenticate(array $credentials): bool|string
    {
        $login = false;
        if (is_file(QUADRO_DIR_APPLICATION . $this->getFileName())) {
            $fileHandle = fopen(QUADRO_DIR_APPLICATION . $this->getFileName(), 'a+');
            while (($userData = fgetcsv($fileHandle, 1000, ",")) !== FALSE) {
                if ($userData[0] == $credentials['email']) {
                    if ($userData[2] ==  hash_hmac(
                        'SHA256',
                        $credentials['pass'],
                        $credentials['email'] . $_SERVER['REMOTE_ADDR'] . $this->getSecret()
                    )) {
                        $login = $this->jwtCreate($userData[0]);
                        break;
                    };
                }
            }
            fclose($fileHandle);
        }

        return $login;
    }

    // -----------------------------------------------------------------------------

    public function jwtCreate(array|string $data): string
    {
        $headers = [
            'alg' => 'HS256',
            'type' => 'JWT',
        ];
        $issuedAt = time();
        $payload = [
            'data' => $data,
            'iat' => $issuedAt,
            'exp' => $issuedAt + (60 * 60 * 24) // 24 hours
        ];
        $headersEncoded = Text::base64UrlEncode(json_encode($headers));
        $payloadEncoded = Text::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac(
            'SHA256',
            "{$headersEncoded}.{$payloadEncoded}",
            $_SERVER['REMOTE_ADDR'] . $this->getSecret(),
            true
        );
        $signatureEncoded = Text::base64UrlEncode($signature);

        return "{$headersEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    public function jwtIsValid(string $jwt): bool
    {
        $tokenParts = explode('.', $jwt);
        $headers = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signatureProvided = $tokenParts[2];

        // check expiration
        if(!isset($payload['exp'])) return false;
        if (($payload['exp'] - time()) < 0) return false;

        // check signature
        $headersEncoded = Text::base64UrlEncode(json_encode($headers));
        $payloadEncoded = Text::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac(
            'SHA256',
            "{$headersEncoded}.{$payloadEncoded}",
            $_SERVER['REMOTE_ADDR'] . $this->getSecret(),
            true
        );
        $signatureEncoded = Text::base64UrlEncode($signature);
        if ($signatureEncoded !== $signatureProvided) return false;

        return true;
    }

    // -----------------------------------------------------------------------------
    //  registration and authentication hooks
    // -----------------------------------------------------------------------------

    protected function _exceedsMaxLoginAttempts(): bool
    {
        // For now, we allow all
        return false;
    }

    protected function _exceedsMaxRegisterAttempts(): bool
    {
        // For now, we allow all
        return false;
    }

    protected function _getCredentials(array &$credentials): bool
    {
        $request = Application::getInstance()->getRequest();
        $postData = $request->getRawBody();
        $success = false;
        if ($request->getMethod() === $request::METHOD_POST) {
            if (false  !== ($data = json_decode($postData, true)) ){
                $credentials['email'] = $data['email']??'';
                $credentials['pass'] = $data['pass']??'';
                $success = true;
            }
        }
        return $success;
    }

    protected function _meetRequirements(array &$credentials): bool
    {
        $credentials['email'] = filter_var($credentials['email'] , FILTER_SANITIZE_EMAIL);
        $credentials['email'] = filter_var($credentials['email'] , FILTER_VALIDATE_EMAIL);
        $credentials['pass']  = (strlen($credentials['pass']) < 8 ) ? false : $credentials['pass'];
        return ! ($credentials['email'] === false || $credentials['pass'] === false);
    }

    protected function _isUnique(array $credentials): bool
    {
        $unique = true;
        if (is_writable(QUADRO_DIR_APPLICATION)) {
            $fileHandle = fopen(QUADRO_DIR_APPLICATION . $this->getFileName(), 'a+');
            while (($userData = fgetcsv($fileHandle, 1000, ",")) !== FALSE) {
                if ($userData[0] == $credentials['email']) {
                    $unique = false;
                    break;
                }
            }
            fclose($fileHandle);
        }
        return $unique;
    }

    protected function _register(array $credentials): bool|array
    {
        if (!is_writable(QUADRO_DIR_APPLICATION)) {
            return false;
        }
        $credentials['pass'] =  hash_hmac(
            'SHA256',
            $credentials['pass'],
            $credentials['email'] . $_SERVER['REMOTE_ADDR'] . $this->getSecret()
        );
        $fileHandle = fopen(QUADRO_DIR_APPLICATION . $this->getFileName(), 'a+');
        if (false === fwrite($fileHandle, "{$credentials['email']},0,{$credentials['pass']}\n" )) return false;
        if ( false === fclose($fileHandle)) return false;
        return ['jwt' => $this->jwtCreate($credentials['email'])];
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