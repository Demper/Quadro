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

namespace Quadro\Http;

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use Quadro\Application as Application;
use Quadro\Application\Component as Component;
use Quadro\Http\ResponseInterface as IResponse;

/**
 * In the Quadro Restfull API application there can only be one response at
 * any time to the one request at any time. Hence the use of the singleton pattern.
 *
 * @package Quadro
 */
class Response extends Component implements IResponse
{
    /**
     * Response constructor.
     */
    public function __construct()
    {
        $this->setHeader('Content-Type: text/html');
    }

    // -----------------------------------------------------------------------------

    /**
     * @var int HTTP status code
     */
    private int $statusCode = 200;

    /**
     * @var string HTTP status text
     */
    private string $statusText = 'Ok';

    /**
     * When not a valid code the status code isset to 500. The statusText is
     * set aas well.
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): static
    {
        switch ($statusCode) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            case 500:
            default:
                $text = 'Internal Server Error';
                $statusCode = 500;
                break;
        }
        $this->statusCode = $statusCode;
        $this->statusText = $text;
        return $this;
    }

    /**
     * @return int The HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets the HTTP status text
     *
     * @param string $statusText
     * @return static
     */
    public function setStatusText(string $statusText): static
    {
        $this->statusText = $statusText;
        return $this;
    }

    /**
     * @return string The HTTP status text
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * @return string The full HTTP status code
     */
    #[Pure]
    public function getStatus(): string
    {
        $protocol = ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0');
        return $protocol . ' ' . $this->getStatusCode() . ' ' . $this->getStatusText();

    }

    // -----------------------------------------------------------------------------


    public function setHeader(string $header, bool $replace = true, int $response_code = 0): void
    {
        header($header, $replace, $response_code);
    }

    public function headerRemove(string $headerName)
    {
        header_remove($headerName);
    }

    public function getHeaders(): array
    {
        return headers_list();
    }

    // ------------------------------------------------------

    protected string $_body = "";
    public function getBody(): string
    {
        return (string) $this->_body;
    }
    public function setBody(mixed $body, bool $append = false): self
    {
        if ($append ) {
            $this->_body .= (string) $body;
        }  else {
            $this->_body = (string) $body;
        }
        return $this;
    }


    /**
     * Sends all the information in the object and closes(exit) the request
     */
    #[NoReturn]
    public function send(): void
    {
        $content =  $this->getBody();

        if (headers_sent())  {
            // TODO add environment condition, we do not want this in production!!
            echo PHP_EOL, 'UNEXPECTED QUADRO HEADERS SEND ERROR!!!' . PHP_EOL;
            echo PHP_EOL .  $content. PHP_EOL. PHP_EOL;
            foreach(debug_backtrace() as $index => $trace) {
                echo str_pad((string) $index, 3, ' ', STR_PAD_LEFT)  . ' ' .
                    str_pad((string) $trace['line'], 4, ' ', STR_PAD_LEFT)  . ' ' .
                    $trace['file'] .  PHP_EOL;
            }
            exit(1);
        }

        $this->setHeader('Content-Length: ' . strlen($content));
        $this->setHeader($this->getStatus(), true, $this->getStatusCode());

        echo $content;
        exit(0);
    }






} // class