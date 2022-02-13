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

namespace Quadro\Http\Json;

use JetBrains\PhpStorm\Pure;
use Quadro\Http\RequestInterface as IRequest;
use Quadro\Http\Response as BaseResponse;

use Exception;
use JsonException;
use Quadro\Application as Application;
use ArrayAccess;
use Countable;
use JsonSerializable;
use Serializable;
use stdClass;
use Traversable;
use DateTime;

/**
 * In the Quadro Restfull API application there can only be one response at
 * any time to the one request at any time. Hence the use of the singleton pattern.
 *
 * @package Quadro
 */
class Response extends BaseResponse implements JsonSerializable
{

    /**
     * Response constructor.
     */
    public function __construct()
    {

        $this->setHeader('Content-type: application/json');
        $this->setHeader('Access-Control-Allow-Origin: *');
        $this->setHeader('Access-Control-Allow-Methods: OPTIONS' );
        $this->setHeader('Access-Control-Allow-Credentials: true');

        // #1  The following headers are always allowed no need to add them
        //     Accept, Accept-Language, Content-Language, Content-Type ,

        // #2  The "Request URL" and "Request Method" headers gives an error in firefox....
        //
        $this->setHeader(
            'Access-Control-Allow-Headers: Authorization, Origin, DNT, Referer, User-Agent, Quadro-GUID'
        );
        $this->setHeader('Access-Control-Expose-Headers: '. $this->getRequest()->getHeaders('Access-Control-Request-Headers'));

    }

    public function getRequest(): IRequest
    {
        return Application::getInstance()->getRequest();
    }

    #[Pure]
    public function getReturnCount(): ?int
    {
        if (!$this->returnIsCountable()) {
            return null;
        }
        return count($this->getReturnValue());
    }



    // -----------------------------------------------------------------------------

    protected mixed $returnValue = null;

    public function setReturnValue(mixed $returnValue): self
    {
        $this->returnValue = $returnValue;
        return $this;
    }

    public function setBody(mixed $body, bool $append = false): self
    {
        $this->returnValue = $body;
        return $this;
    }

    public function getReturnValue(): mixed
    {
        return $this->returnValue;
    }

    // -----------------------------------------------------------------------------



    protected ?string $returnType = null;

    public function setReturnType(string $returnType): self
    {
        $this->returnType = $returnType;
        return $this;
    }

    public function getReturnType(): string
    {
        if (null === $this->returnType) {
            return match (gettype($this->returnValue)) {
                'object' => get_class($this->returnValue),
                default => gettype($this->returnValue),
            }; // match
        }
        return $this->returnType;
    }
    // -----------------------


    // -----------------------------------------------------------------------------

    /**
     * Returns jsonSerializable data for the value __$value__
     * @param $value
     * @return mixed
     */
    #[Pure]
    protected function jsonable($value): mixed
    {

        // if we have an object...
        if ('object' === gettype($value)) {
            //var_dump($value);

            // ...and we can json-serialize it returns the object
            if ($value instanceof JsonSerializable) {
                return $value;
            }

            // ... or get the public properties if any
            $array = get_object_vars($value);
            if (count($array)) {
                $class = new stdClass();
                foreach ($array as $prop => $val) {
                    $class->$prop = $val;
                }
                return $class;
            }

            // ... or the __toString method is implemented
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }

            // ... or return the internal ID
            return get_class($value) . '[objectHash=' . spl_object_hash($value) . ']';
        }

        // in all other cases return the value as is
        return $value;

    } // jsonSerialize()

    // -----------------------------------------------------------------------------

    protected array $messages = [];
    public function addMessage(string $message, ?string $index = null): Response
    {
        if (null === $index) $index = count($this->messages);
        $this->messages[$index] =  $message;
        return $this;
    }



    public function setMessages(array $messages = []): Response
    {
        $this->messages = $messages;
        return $this;
    }



    protected string $messageString = '';
    protected function messageToString(mixed $message): string
    {
        if(is_string($message)) {
            $this->messageString .= $message;
        } else {
            if (is_array($message)) {
                $this->messageString .= '[';
                foreach ($message as $prop => $val) {
                    $this->messageString .= $prop . '=>' . $this->messageToString($val) . ', ';
                }
                $this->messageString .= ']';

            } else {
                if (is_object($message)) {
                    $this->messageString .= '{';
                    foreach (get_object_vars($message) as $prop => $val) {
                        $this->messageString .= $prop . ':' . $this->messageToString($val)  . ', ';
                    }
                    $this->messageString .= '}';
                }
            }
        }
        return $this->messageString;
    }



    public function getMessages(): array
    {
        return $this->messages;
    }

    // -----------------------------------------------------------------------------

    protected ?int $returnTotal = null;

    /**
     * @param int|null $returnTotal
     * @return Response
     */
    public function setReturnTotal(int $returnTotal = null): Response
    {
        $this->returnTotal = $returnTotal;
        return $this;
    }



    /**
     * When countable it will return the total of items in the result set
     * @return int|null
     */
    public function getReturnTotal() : ?int
    {
        if (null === $this->returnTotal || count($this->returnValue) > $this->returnTotal) {
            $this->returnTotal = count($this->returnValue);
        }
        return $this->returnTotal;
    }



    /**
     * Whether the result value is iterable or countable
     * @return bool
     */
    protected function returnIsCountable(): bool
    {
        return
            is_array($this->returnValue)
            ||
            (
                $this->returnValue instanceof ArrayAccess &&
                $this->returnValue instanceof Traversable &&
                $this->returnValue instanceof Serializable &&
                $this->returnValue instanceof Countable
            );
    }


    /**
     * The start point in the result set
     * @return int
     * @throws Application\RegistryException
     */
    public function getReturnOffset(): int
    {
        return (int)  $this->getRequest()->getGetData('offset', FILTER_VALIDATE_INT);
    }


    /**
     * The items per page
     * @return int
     * @throws Application\RegistryException
     */
    public function getReturnLimit(): int
    {
        $limit = (int) $this->getRequest()->getGetData('limit', FILTER_VALIDATE_INT);
        return ($limit) ?: $this->getReturnCount();
    }

    // -----------------------------------------------------------------------------

    /**
     * The number of seconds the resource took to load
     * @return float
     */
    public function getExecutionTime(): float
    {
        return (float)number_format(Application::getInstance()->getDeltaTime(), 10);
    }

    // -----------------------------------------------------------------------------

    /**
     * Cache for storing the links for this resource
     * @var array
     */
    protected array $links = [];

    public function addLink($rel, $href, $method, $type = 'application/json'): Response
    {
        $this->setHeader('Link:' . "<$href>; rel=$rel");
        $this->links[$rel] = ['href' => $href, 'rel' => $rel, 'type' => $type, 'method' => $method];
        return $this;
    }



    /**
     * Returns all links for this response
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }



    /**
     * Shortcut for adding a link to the home page
     * @return Response
     * @return $this
     */
    public function addLinkToHome(): Response
    {
        $this->addLink('home',  $this->getRequest()->getScheme() . '://' .  $this->getRequest()->getHost() . '/', 'GET');
        return $this;
    }



    /**
     * Shortcut for adding a link to the current page
     * @return Response
     */
    public function addLinkToSelf(): Response
    {
        $this->addLink(
            'self',
            $this->getRequest()->getScheme() . '://' .  $this->getRequest()->getHost() .  $this->getRequest()->getPath(),
            $this->getRequest()->getMethod()
        );
        return $this;
    }

    

    /**
     * Default value for the items per page
     */
    const DEFAULT_LIMIT = 20;



    /**
     * Adds links for pagination if we have a countable return value
     * @return Response
     */
    public function addPaginationLinks(): Response
    {
        if ($this->returnIsCountable()) {
            $limit = (int) $this->getRequest()->getGetData('limit', FILTER_VALIDATE_INT, Response::DEFAULT_LIMIT);
            $offset = (int) $this->getRequest()->getGetData('offset', FILTER_VALIDATE_INT, 0);
            $urlBase =  $this->getRequest()->getScheme() . '://' .  $this->getRequest()->getHost() . parse_url( $this->getRequest()->getPath(),PHP_URL_PATH);
            $total = $this->getReturnTotal();
            if ($limit > $total) {
                $limit = $total;
            }
            if ($offset >  0) {
                $this->addLink(
                    'first',
                    $urlBase . sprintf('?offset=%d&limit=%d', 0, $limit),
                    $this->getRequest()->getMethod()
                );
            }
            if (($total-$offset) > $limit) {
                $this->addLink(
                    'last',
                    $urlBase . sprintf('?offset=%d&limit=%d', $total - $limit, $limit),
                     $this->getRequest()->getMethod()
                );
            }
            if ($offset - $limit >= 0) {
                $this->addLink(
                    'previous',
                    $urlBase . sprintf('?offset=%d&limit=%d', $offset - $limit, $limit),
                    $this->getRequest()->getMethod()
                );
            }
            if ($offset + $limit < $total) {
                $this->addLink(
                    'next',
                    $urlBase . sprintf('?offset=%d&limit=%d', $offset + $limit, $limit),
                    $this->getRequest()->getMethod()
                );
            }
        }
        return $this;
    }

    // ----------------------------------------------------------------------------------------------------------------

    /**
     * Returns the Response object as a json encoded string
     * @return array
     * @throws Application\RegistryException
     * @throws Exception
     */
    protected function toArray(): array
    {
        $response['return']['value']   = $this->jsonable($this->getReturnValue());
        $response['return']['type']    = $this->getReturnType();
        if ($this->returnIsCountable()) {
            $response['return']['count']   = $this->getReturnCount();
            $response['return']['offset']  = $this->getReturnOffset();
            $response['return']['limit']   = $this->getReturnLimit();
            $response['return']['total']   = $this->getReturnTotal();
        }


        $t = Application::getInstance()->getStartTime();
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new DateTime( date('Y-m-d H:i:s.'.$micro, (int)$t) );
        $response['execution']['start'] =  $d->format("Y-m-d H:i:s.u");
        $delta = Application::getInstance()->getDeltaTime();
        $t = $t + $delta;
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new DateTime( date('Y-m-d H:i:s.'.$micro, (int)$t) );
        $response['execution']['end'] =  $d->format("Y-m-d H:i:s.u");
        $response['execution']['duration'] = number_format($delta, 6);

        if(count($this->getMessages()))
            $response['messages']          = $this->getMessages();
        $response['status']['code']    = $this->getStatusCode();
        $response['status']['text']    = $this->getStatusText();
        if (Application::environment() != Application::ENV_PRODUCTION) {
            $response['environment'] = Application::getInstance()->getEnvironment();
            $response['headers'] = $this->getHeaders();
        }
        if (count($this->getLinks())) {
            $links = [];
            foreach ($this->getLinks() as $rel => $link) {
                $links[$rel] = $link;
            }
            $response['linksTo'] = $links;
        }
        return $response;
    }



    /**
     * Returns the last json error as string
     * @return string
     */
    protected function jsonError(): string
    {
        return  match (json_last_error()) {
            JSON_ERROR_NONE => 'JSON ERROR NONE - No errors found',
            JSON_ERROR_DEPTH => 'JSON ERROR DEPTH - Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'JSON ERROR STATE MISMATCH - Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'JSON ERROR CTRL CHAR - Unexpected control character found',
            JSON_ERROR_SYNTAX => 'JSON ERROR SYNTAX - Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'JSON ERROR UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded',
            default => 'JSON ERROR UNKNOWN - Unknown error',
        };
    }

    // ----------------------------------------------------------------------------------------------------------------
    public function getBody(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR| JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE );
    }




    /**
     * @return array
     * @throws Application\RegistryException
     */
    public function jsonSerialize(): array
    {
        return  $this->toArray();
    }


    public function getProtocol():string
    {
        return (isset($_SERVER['SERVER_PROTOCOL']))
            ? $_SERVER['SERVER_PROTOCOL']
            : 'HTTP/1.0';
    }


//    /**
//     * Use it for json_encode some corrupt UTF-8 chars
//     * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
//     */
//    protected function utf8ize( string|array $mixed ): string|array
//    {
//        if (is_array($mixed)) {
//            foreach ($mixed as $key => $value) {
//                $mixed[$key] = $this->utf8ize($value);
//            }
//        } elseif (is_string($mixed)) {
//            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
//        }
//        return $mixed;
//    }




//    public function appendContent($content)
//    {
//        if (is_array($this->getReturnValue())) {
//            if (is_array($content)) {
//                $this->setReturnValue(array_merge($this->getReturnValue(), $content));
//            } else {
//                $this->setReturnValue($this->getReturnValue()[] = $content);
//            }
//        } else {
//            if (is_numeric($this->getReturnValue())) {
//                $this->setReturnValue($this->getReturnValue() + $content);
//            } else {
//                if (is_string($this->getReturnValue())) {
//                    $this->setReturnValue($this->getReturnValue() . $content);
//                } else {
//                    $this->setReturnValue([$this->getReturnValue(), $content]);
//                }
//            }
//        }
//
//        return $this;
//    }

} // class