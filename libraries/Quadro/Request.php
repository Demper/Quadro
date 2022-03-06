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
use Quadro\Application\Component as Component;
use Quadro\Exception as Exception;
use Quadro\Request\EnumRequestSchemes;
use Quadro\Request\EnumRequestMethods;
use stdClass;

/**
 * Singleton Request object
 *
 * @package Quadro
 */
abstract class Request extends Component implements RequestInterface, JsonSerializable
{
    /**
     * The singleton pattern is often seen as an "anti-pattern". I believe the pattern is a valid pattern but difficult to
     * implement.
     *
     * One of the arguments to be it an "anti-pattern" is when it is used as global variables. As in the case of an
     * HTTP request it kind a is. But we added parameters in the singleton methods and in the constructor to overcome this.
     *
     * Another is the inability to use in Unit test. We can change all the properties by passing a different URL to
     * the constructor
     *
     * All setters are protected and force changing or control the objects' data through the constructor only this will
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
            $this->_setSignature($signature);
        }
    }

    // ---------------------------------------------------------------------------------------------------

    protected EnumRequestMethods $_method;

    public function getMethod(): EnumRequestMethods
    {
        if (!isset($this->_method)) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                $this->_method = EnumRequestMethods::strToEnum($_SERVER['REQUEST_METHOD']);
            }  else {
                $this->_method = EnumRequestMethods::GET;
            }
        }
        return $this->_method;
    }

    /**
     * @param EnumRequestMethods $method
     * @return $this
     */
    protected function _setMethod(EnumRequestMethods $method): static
    {
        $this->_method = $method;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    protected int $_port;

    public function getPort(): int
    {
        $port = $this->_port ?? $_SERVER['SERVER_PORT'] ?? 80;
        return (int) $port;
    }

    protected function _setPort(int $port): static
    {
        $this->_port = $port;
        return $this;
    }


    // ---------------------------------------------------------------------------------------------------

    protected string $_host;

    abstract public function getHost(): string;

    protected function _setHost(string $host): static
    {
        $this->_host = $host;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * @var string $_path The path of the request, NULL when not manual set
     */
    protected string $_path;

    /**
     * @return string The path of the request
     * @throws Exception
     */
    public function getPath(): string
    {
        if (!isset($this->_path)) {
            if (array_key_exists('REQUEST_URI', $_SERVER)
                && isset($_SERVER['REQUEST_URI'])) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                if (!is_string($path) ){
                   throw new Exception('Not found', 404);
                } else {
                    $this->_path = $path;
                }
            }
        }
        return $this->_path;
    }

    /**
     * Sets the path manually
     *
     * @param string $path
     * @return static
     */
    protected function _setPath(string $path): static
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * The path as an array of the slugs the path is made of
     *
     * @return array<int, string>
     * @throws Exception
     */
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

    /**
     * @var array<string, mixed>
     */
    protected array $_query;

    /**
     * @param string|null $key
     * @param int $flags
     * @param mixed|null $default
     * @return int|float|string|array<int|string, mixed>
     */
    public function getGetData(string|null $key=null, int $flags = FILTER_DEFAULT, mixed $default=null): int|float|string|array
    {
        // defaults set to $default in case nothing is found
        $data = $default;

        // if no key is specified return all
        if (null === $key) {
            $data = $this->_query ?? $_GET ?? []; /* @phpstan-ignore-line */

        // get the query array and check if the key exists
        } else {
            $query = $this->_query ?? $_GET ?? []; /* @phpstan-ignore-line */
            if (array_key_exists($key, $query)) {
                $data = filter_var($query[$key], $flags);
            }
        }

        // nothing found return default or all ( when there is no key given
        return $data;
    }

    /**
     * @param array<string, mixed>|string $query
     * @return static
     */
    protected function _setGetData(array|string $query): static
    {
        if(is_array($query)) {
            $this->_query = $query;
        } else {
            if(is_string($query)) {
                // parse_str expects passes the second parameter by reference
                // so we need to initialize the property first
                $this->_query = [];
                parse_str($query, $this->_query);
            }
        }
        return $this;
    }

    /**
     * @var array<string, mixed>
     */
    protected array $_postData;

    /**
     * @param string|null $key
     * @param int $flags
     * @param mixed|null $default
     * @return int|float|string|array<int|string, mixed>
     */
    public function getPostData(string|null $key=null, int $flags = FILTER_DEFAULT, mixed $default=null): int|float|string|array
    {
        // get the correct array and check if the key exists

        /** @phpstan-ignore-next-line */
        $postData =  $this->_postData ?? $_POST ?? [];

        // if no key is specified return all
        if (null === $key) return $postData;

        if(array_key_exists($key, $postData)) {
            return filter_var($postData[$key], $flags);
        }

        // nothing found return default
        return $default;
    }

    /**
     * @param array<string, mixed> $postData
     * @return static
     */
    protected function _setPostData(array $postData): static
    {
        $this->_postData = $postData;
        return $this;
    }


    /**
     * We need store the read raw body because it can't be read again
     * @var string
     */
    protected string $_rawBody;

    /**
     * Gets HTTP raw request body
     */
    public function getRawBody(): string
    {
        if (!isset($this->_rawBody)) {
            $this->_rawBody = '';
            $input = file_get_contents("php://input");
            if (is_string($input)) $this->_rawBody = $input;
        }
        return $this->_rawBody;
    }

    /**
     * @param string $rawBody
     * @return $this
     */
    protected function _setRawBody(string $rawBody): static
    {
        $this->_rawBody = $rawBody;
        return $this;
    }

    /**
     * Gets decoded JSON HTTP raw request body
     *
     * @param bool $associative
     * @return stdClass|array<string>|bool
     */
    public function getRawBodyAsJson(bool $associative = true): stdClass | array | bool
    {
        $rawBody = $this->getRawBody();
        if(gettype($rawBody) != 'string') {
            return false;
        }

        $json = json_decode($rawBody, $associative);
        if (!is_array($json)) return false;
        return $json;
    }

    // ---------------------------------------------------------------------------------------------------

    public function getSignature(): string
    {
        $signature  = $this->getMethod()->name; ;
        $signature .= ' ' . $this->getScheme()->name . '://';

        $signature .= $this->getHost();
        //if($this->getPort() <> 80 ) {
        //    $signature .= ':' . $this->getPort();
        //}
        $signature .= $this->getPath();
        $getData = $this->getGetData();
        if (is_array($getData )) {
            $signature .= '?' . http_build_query($getData );
        }
        return $signature;
    }

    /**
     * @param string $signature
     * @return static
     * @throws Exception
     */
    protected function _setSignature(string $signature): static
    {
        $this->_setRequestTime(microtime(true));
        $signature = trim($signature);

        // get the method from the signature if any.
        // if found remove from the signature to leave us with a valid URL
        $allMethods = implode('|', EnumRequestMethods::list());
        $pattern = '/^('.$allMethods.').*/i';
        $matches = [];
        if(preg_match($pattern, $signature, $matches)){
            $this->_setMethod(EnumRequestMethods::strToEnum(trim($matches[1])));
            $signature = trim(str_replace($matches[1], '', $signature));
        }

        // parse the rest and set defaults
        // parse_url may return false on seriously malformed URLs
        $parsedSignature = parse_url($signature);
        if(!is_array($parsedSignature)) {
            $parsedSignature = [];
        }
        $parsedUrl = array_merge([
            'scheme' => EnumRequestSchemes::HTTP->name,
            'host' => 'localhost',
            'port' => 80,
            'path' => '',
            'query' => ''
        ], $parsedSignature);

        if(isset($parsedUrl['user']) || isset($parsedUrl['pass'])){
            throw new Exception('Authentication through URl not supported');
        }

        $this->_setScheme(EnumRequestSchemes::strToEnum((string) $parsedUrl['scheme']));
        $this->_setHost((string) $parsedUrl['host']);
        $this->_setPort((int) $parsedUrl['port']);
        $this->_setPath((string) $parsedUrl['path']);
        $this->_setGetData((string) $parsedUrl['query']);

        // NOTE :
        // The fragment part returned by parse_url(after the number sign #) wil not
        // be sent to the server and is only available at the client side
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSignature();
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * @var string
     */
    protected string $_remoteAddress;

    /**
     * @return string
     */
    public function getRemoteAddress(): string
    {
        return $this->_remoteAddress ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * @param string $remoteAddress
     * @return $this
     */
    protected function _setRemoteAddress(string $remoteAddress): static
    {
        $this->_remoteAddress = $remoteAddress;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * @var float
     */
    protected float $_requestTime;

    /**
     * @return float
     */
    public function getRequestTime(): float
    {
        if ($this->_requestTime) {
            $this->_requestTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? Time();
        }
        return  $this->_requestTime;
    }

    /**
     * @param float $requestTime
     * @return static
     */
    protected function _setRequestTime(float $requestTime): static
    {
        $this->_requestTime = $requestTime;
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * @var array<string, string> $_headers
     */
    protected ?array $_headers = null;

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        if(null === $this->_headers) {
            if (function_exists('getallheaders')) {
                $this->_headers =  getallheaders();
            } else {
                $this->_headers = [];
            }
        }
        return $this->_headers;
    }

    /**
     * @param string $headerName
     * @return string
     */
    public function getHeader(string $headerName): string
    {
        foreach ($this->getHeaders() as $name => $value) {
            if ($headerName == $name) {
                return  $value;
            }
        }
        return '';
    }

    // ---------------------------------------------------------------------------------------------------

    abstract public function getScheme(): EnumRequestSchemes;
    abstract protected function _setScheme(EnumRequestSchemes $scheme): static;

    // ---------------------------------------------------------------------------------------------------

    abstract public function isSecure(): bool;

    // ---------------------------------------------------------------------------------------------------

    public static function getSingletonName(): string
    {
        return 'Quadro\Request';
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * Returns a Json representation of this object
     * @return mixed
     * @throws Exception
     */
    public function jsonSerialize(): mixed
    {
        return [
            'headers' => $this->getHeaders(),
            'scheme' => $this->getScheme()->name,
            'host' => $this->getHost(),
            'uri' => $this->getPath(),
            'slugs' => $this->getSlugs(),
            'method' => $this->getMethod()->name,
            'query' => $this->getGetData(),
        ];
    }


} // class