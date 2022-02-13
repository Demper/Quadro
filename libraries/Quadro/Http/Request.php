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

use JsonSerializable;
use Quadro\Application\Component as Component;
use Quadro\Exception as Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use stdClass;

/**
 * Singleton Request object
 *
 * @package Quadro
 */
class Request extends Component implements RequestInterface, JsonSerializable
{
    /**
     * The singleton pattern is often seen as an anti pattern. I believe the pattern is a valid pattern but difficult to
     * implement.
     *
     * One of the arguments to be it an anti-pattern is when it is used as global variables. As in the case of the a
     * HTTP request it kind a is. But we added parameters in the singleton methods and in the constructor to overcome this.
     *
     * Another is the inability to use in Unit test. We can change all the properties by passing a different URL to
     * the constructor
     *
     * All setters are protected and force to change or control the objects data through the constructor only this will
     * enforce the Single Responsibility Principle.
     */

    /**
     * Request constructor.
     * @param string|null $signature
     * @throws Exception
     */
    public function __construct(string $signature=null)
    {
        if(null !== $signature) {
            $this->setSignature($signature);
        }
    }

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH = 'PATCH';
    const METHOD_PURGE = 'PURGE';  // Squid and Varnish support
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';


    protected string $method;

    public function getMethod(): string
    {
        return $this->method ?? $_SERVER['REQUEST_METHOD'] ?? Request::METHOD_GET;
    }

    /**
     * @param string $method
     * @return $this
     */
    protected function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    const SCHEME_HTTP = 'HTTP';
    const SCHEME_HTTPS = 'HTTPS';

    protected string $scheme;

    /**
     * @see Request::isSecure()
     * @return string The used scheme, HTTP or HTTPS
     */
    #[Pure]
    public function getScheme(): string
    {
        return $this->scheme ?? ($this->isSecure() ? Request::SCHEME_HTTPS : Request::SCHEME_HTTP);
    }

    /**
     * @param string $scheme
     * @return $this
     */
    protected function setScheme(string $scheme): self
    {
        $this->scheme = strtoupper($scheme);
        return $this;
    }

    public function isSecure(): bool
    {
        $secure = false;
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)){
            $secure = true;
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $secure = true;
            } else{
                if(isset($this->scheme) && $this->scheme == Request::SCHEME_HTTPS) {
                    $secure = true;
                }
            }
        }
        return $secure;
    }

    // ---------------------------------------------------------------------------------------------------

    protected string $host;

    public function getHost(): string
    {
        return $this->host ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    protected function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    protected int $port;

    public function getPort(): int
    {
        $port = $this->port ?? $_SERVER['SERVER_PORT'] ?? 80;
        return (int) $port;
    }

    protected function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * @var string $path The path of the request, NULL when not manual set
     */
    protected string $_path;

    /**
     * @return string The path of the request
     */
    public function getPath(): string
    {
        if (!isset($this->_path)) {
            if (array_key_exists('REQUEST_URI', $_SERVER)
                && isset($_SERVER['REQUEST_URI'])) {
                $this->_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            }
        }
        return $this->_path;
    }



    /**
     * Sets the path manually
     *
     * @param string $path
     * @return Request
     */
    protected function setPath(string $path): self
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * The path as an array of the slugs the path is made of
     *
     * @return array
     */
    #[Pure]
    public function getSlugs(): array
    {
        $slugs = [];
        foreach(explode('/', ltrim($this->getPath(), '/')) as $slug)
        {
            //$slug = trim($slug, '/');
            //if (!empty($slug)){
                $slugs[] = $slug;
            //}
        }
        return $slugs;
    }

    // ---------------------------------------------------------------------------------------------------

    protected array $query;

    public function getGetData(string|null $key=null, int $flags = FILTER_DEFAULT, mixed $default=null): mixed
    {
        // if no key is specified return all
        if (null === $key) {
            return $this->query ?? $_GET ?? [];
        }

        // get the query array and check if the key exists
        $query =  $this->query ?? $_GET ?? [];
        if(array_key_exists($key, $query)) {
            return filter_var($query[$key], $flags);
        }

        // nothing found return default
        return $default;
    }

    /**
     * @param array|string $query
     * @return Request
     */
    protected function setGetData(array|string $query): self
    {
        if(is_array($query)) {
            $this->query = $query;
        } else {
            if(is_string($query)) {
                // parse_str expects passes the second parameter by reference
                // so we need to initialize the property first
                $this->query = [];
                parse_str($query, $this->query);
            }
        }
        return $this;
    }

    protected array $postData;

    public function getPostData(string|null $key=null, int $flags = FILTER_DEFAULT, mixed $default=null): mixed
    {
        // get the correct array and check if the key exists
        $postData =  $this->postData ?? $_POST ?? [];

        // if no key is specified return all
        if (null === $key) return $postData;

        if(array_key_exists($key, $postData)) {
            return filter_var($postData[$key], $flags);
        }

        // nothing found return default
        return $default;
    }

    /**
     * @param array $postData
     * @return Request
     */
    protected function setPostData(array $postData): self
    {
        $this->postData = $postData;
        return $this;
    }


    /**
     * We need store the read raw body because it can't be read again
     * @var string
     */
    protected string $rawBody;

    /**
     * Gets HTTP raw request body
     */
    public function getRawBody(): string
    {
        if (null === $this->rawBody) {
            $this->rawBody = file_get_contents("php://input");
        }
        return $this->rawBody;
    }
    protected function setRawBody(string $rawBody): self
    {
        $this->rawBody = $rawBody;
        return $this;
    }

    /**
     * Gets decoded JSON HTTP raw request body
     *
     * @param bool $associative
     * @return stdClass|array|bool
     */
    public function getRawBodyAsJson(bool $associative = false): stdClass | array | bool
    {
        $rawBody = $this->getRawBody();
        if(gettype($rawBody) != 'string') {
            return false;
        }
        return json_decode($rawBody, $associative);
    }

    // ---------------------------------------------------------------------------------------------------

    public function getSignature(): string
    {

        $signature  = $this->getMethod() ;
        $signature .= ' ' . $this->getScheme() . '://';

        $signature .= $this->getHost();
        if($this->getPort() <> 80 ) {
            $signature .= ':' . $this->getPort();
        }
        $signature .= $this->getPath();
        if (!empty($this->getGetData())) {
            $signature .= '?' . http_build_query($this->getGetData());
        }
        return $signature;
    }

    /**
     * @param string $signature
     * @return $this
     * @throws Exception
     */
    protected function setSignature(string $signature): self
    {
        $this->setRequestTime(microtime(true));
        $signature = trim($signature);
        $allMethods =
            Request::METHOD_GET . '|' .
            Request::METHOD_POST . '|' .
            Request::METHOD_PUT . '|' .
            Request::METHOD_DELETE . '|' .
            Request::METHOD_HEAD . '|' .
            Request::METHOD_OPTIONS . '|' .
            Request::METHOD_PATCH . '|' .
            Request::METHOD_PURGE . '|' .
            Request::METHOD_TRACE . '|' .
            Request::METHOD_CONNECT;

        // get the method
        $pattern = '/^('.$allMethods.').*/i';
        $matches = [];
        if(preg_match($pattern, $signature, $matches)){
            $this->setMethod(trim($matches[1]));
            $signature = trim(str_replace($matches[1], '', $signature));
        }

        // parse the rest and set defaults
        $parsedUrl = array_merge([
            'scheme' => Request::SCHEME_HTTP,
            'host' => 'localhost',
            'port' => 80,
            'path' => '',
            'query' => ''
        ], parse_url($signature));

        $parsedUrl['scheme'] = strtoupper($parsedUrl['scheme']);
        if(isset($parsedUrl['user']) || isset($parsedUrl['pass'])){
            throw new Exception('Authentication through URl not supported');
        }
        if ($parsedUrl['scheme'] != Request::SCHEME_HTTP && $parsedUrl['scheme'] != Request::SCHEME_HTTPS) {
            throw new Exception(sprintf('Only HTTP and HTTPS schemes are supported, %s given', $parsedUrl['scheme']));
        }

        $this->setScheme($parsedUrl['scheme']);
        $this->setHost($parsedUrl['host']);
        $this->setPort($parsedUrl['port']);
        $this->setPath($parsedUrl['path']);
        $this->setGetData($parsedUrl['query']);

        // NOTE :
        // The fragment part returned by parse_url(after the number sign #) wil not
        // be send to the server and is only available at the client side
        return $this;
    }

    public function __toString()
    {
        return $this->getSignature();
    }

    // ---------------------------------------------------------------------------------------------------

    protected string $remoteAddress;

    public function getRemoteAddress(): string
    {
        return $this->remoteAddress ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    protected function setRemoteAddress(string $remoteAddress): self
    {
        $this->remoteAddress = $remoteAddress;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    protected float $requestTime;

    public function getRequestTime(): float
    {
        if ($this->requestTime) {
            $this->requestTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? Time();
        }
        return  $this->requestTime;
    }

    protected function setRequestTime(float $requestTime): self
    {
        $this->requestTime = $requestTime;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    protected array $headers;

    public function getHeaders(string $headerName=null): string|array
    {
         $headers = $this->headers
             ?? (function_exists('getallheaders')) ? getallheaders():null
             ?? [];

         if(null !== $headerName) {
             foreach ($headers as $name => $value) {
                 if ($headerName == $name) return $value;
             }
             return '';
         }

         return $headers;
    }

    protected function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Returns a Json representation of this object
     * @return array
     */
    #[ArrayShape([
        'headers' => "array",
        'scheme' => "string",
        'host' => "string",
        'uri' => "string",
        'slugs' => "string",
        'method' => "string",
        'query' => "mixed"
    ])]
    public function jsonSerialize(): array
    {
        return [
            'headers' => $this->getHeaders(),
            'scheme' => $this->getScheme(),
            'host' => $this->getHost(),
            'uri' => $this->getPath(),
            'slugs' => $this->getSlugs(),
            'method' => $this->getMethod(),
            'query' => $this->getGetData(),
        ];
    }


} // class