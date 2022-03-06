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
use Quadro\Application\Component as Component;
use Quadro\Response\EnumLinkRelations as EnumLinkRelations;
use Quadro\ResponseInterface as ResponseInterface;

/**
 * In the Quadro Restfull API application there can only be one response at
 * any time to the one request at any time.
 *
 * @package Quadro
 */
class Response extends Component implements ResponseInterface
{

    // -----------------------------------------------------------------------------

    /**
     * @var int HTTP status code
     */
    private int $_statusCode = 200;

    /**
     * @var string HTTP status text
     */
    private string $_statusText = 'Ok';

    /**
     * When not a valid code the status code isset to 500. The statusText is
     * set aas well.
     *
     * @see https://restfulapi.net/http-status-codes/
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     * @param int $code
     * @return static
     */
    public function setStatusCode(int $code): static
    {
        switch ($code) {
            // Information responses
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 103: $text = 'Early Hints'; break;

            // Successful responses
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;

            // Redirection messages
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 307: $text = 'Temporary Redirect'; break;
            case 308: $text = 'Permanent Redirect'; break;

            // Client error responses
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
            case 416: $text = 'Range not Satisfiable'; break;
            case 417: $text = 'Expectation Failed'; break;
            case 418: $text = 'I\'m a Teapot'; break;
            case 422: $text = 'Unprocessable Entity'; break;
            case 425: $text = 'To Early'; break;
            case 426: $text = 'Upgrade Required'; break;
            case 428: $text = 'Precondition Required'; break;
            case 429: $text = 'Too Many Request'; break;
            case 431: $text = 'Request Header Fields Too Large'; break;
            case 451: $text = 'Unavailable For Legal reasons'; break;

            // Server error responses
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            case 506: $text = 'Variant Also Negotiates'; break;
            case 507: $text = 'Insufficient Storage'; break;

            case 500:
            default:
                $text = 'Internal Server Error';
                $code = 500;
                break;
        }
        $this->_statusCode = $code;
        $this->setStatusText($text);
        return $this;
    }

    /**
     * @return int The HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->_statusCode;
    }

    /**
     * Sets the HTTP status text
     *
     * @param string $text
     * @return static
     */
    public function setStatusText(string $text): static
    {
        $this->_statusText = $text;
        return $this;
    }

    /**
     * @return string The HTTP status text
     */
    public function getStatusText(): string
    {
        return $this->_statusText;
    }

    /**
     * @return string The full HTTP status code
     */
    public function getStatus(): string
    {
        $protocol = ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0');
        return $protocol . ' ' . $this->getStatusCode() . ' ' . $this->getStatusText();
    }

    // -----------------------------------------------------------------------------

    /**
     * Cache for storing the links for this resource
     * @var array<int, array<string, string>>
     */
    protected array $_links = [];

    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Link
     * @param EnumLinkRelations $rel
     * @param string $href
     * @param string $method
     * @param string $type
     * @return static
     */
    public function addLink(EnumLinkRelations $rel, string $href, string $method = 'GET', string $type = 'application/json'): static
    {
        $this->_links[] = ['href' => $href, 'rel' => $rel->value, 'type' => $type, 'method' => $method];
        $linkHeader = '';
        foreach($this->_links as $link) {
            $linkHeader .= ($linkHeader  == '') ? ''  : ',';
            $linkHeader .= "<{$link['href']}>; rel={$link['rel']}; type={$link['type']}";
        }
        $this->setHeader('Link:' . $linkHeader);
        return $this;
    }

    /**
     * Returns all links for this response
     * @return array<int, array<string, string>>
     */
    public function getLinks(): array
    {
        return $this->_links;
    }

    // -----------------------------------------------------------------------------

    /**
     * @var  array<int, array<string, string>>
     */
    protected array $_messages = [];

    /**
     * @param string $message
     * @param int|string|null $index
     * @return $this
     */
    public function addMessage(string $message, int|string|null $index = null): static
    {
        if (is_int($index)) {
            $i = $index;
        } else {
            $i = count($this->_messages);
        }
        $this->_messages[$i] =  ['text' => $message];
        if(is_string($index)) {
            $this->_messages[$i]['name'] = $index;
        }
        return $this;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getMessages(): array
    {
        return $this->_messages;
    }

    /**
     * @param array<int|string, string> $messages
     * @return static
     */
    public function setMessages(array $messages = []): static
    {
        foreach($messages as $index => $message) {
            $this->addMessage($message, $index);
        }
        return $this;
    }

    /**
     * Add this header to the headers to be sent
     *
     * @param string $header
     * @param bool $replace
     * @param int $response_code
     * @return void
     */
    public function setHeader(string $header, bool $replace = true, int $response_code = 0): void
    {
        header($header, $replace, $response_code);
    }

    /**
     * Removes header from the headers to be sent
     *
     * @param string $headerName
     * @return void
     */
    public function headerRemove(string $headerName)
    {
        header_remove($headerName);
    }

    /**
     * Returns the headers to be sent
     *
     * @return  array<int, string>
     */
    public function getHeaders(): array
    {
        return headers_list();
    }

    // ------------------------------------------------------

    /**
     * @var string $_content the content for the response
     */
    protected string $_content = "";

    /**
     * Returns the content of the response
     * @return string
     */
    public function getContent(): string
    {
        return  $this->_content;
    }

    /**
     * Sets the content of the Response
     *
     * @param mixed $content
     * @param bool $append
     * @return $this
     */
    public function setContent(mixed $content, bool $append = false): static
    {
        if ($append ) {
            $this->_content .= (string) $content;
        }  else {
            $this->_content = (string) $content;
        }
        return $this;
    }

    // ------------------------------------------------------

    /**
     * Sends the content and closes(exit) the request
     *
     * @throws \Quadro\Config\Exception
     */
    public function send(): void
    {

        $content =  $this->getContent();



        if (headers_sent())  {
            if (Application::getInstance()->debug()) {
                echo PHP_EOL, 'UNEXPECTED QUADRO HEADERS SEND ERROR!!!' . PHP_EOL;
                echo PHP_EOL . $content . PHP_EOL . PHP_EOL;
                foreach (debug_backtrace() as $index => $trace) {
                    $line = (string) ($trace['line'] ?? '-');
                    $file = (string) ($trace['file'] ?? 'unknown file');
                    echo str_pad((string)$index, 3, ' ', STR_PAD_LEFT) . ' ' .
                        str_pad($line, 4, ' ', STR_PAD_LEFT) . ' ' .
                        $file . PHP_EOL;
                }
                exit(1);
            }
        }

        $this->setHeader('Content-Length: ' . strlen($content));
        $this->setHeader($this->getStatus(), true, $this->getStatusCode());
        echo $content;



        exit(0);
    }





} // class