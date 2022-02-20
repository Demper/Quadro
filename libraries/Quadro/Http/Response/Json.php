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

namespace Quadro\Http\Response;

use ArrayAccess;
use Countable;
use DateTime;
use Exception;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Quadro\Application as Application;
use Quadro\Http\RequestInterface as IRequest;
use Quadro\Http\Response as BaseResponse;
use Quadro\Http\Response\EnumLinkRelations as EnumLinkRelations;
use Serializable;
use stdClass;
use Traversable;

/**
 * In the Quadro Restfull API application there can only be one response at
 * any time to the one request at any time. Hence the use of the singleton pattern.
 *
 * @package Quadro
 */
class Json extends BaseResponse implements JsonSerializable
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
        $this->setHeader('Access-Control-Expose-Headers: '. $this->_getRequest()->getHeaders('Access-Control-Request-Headers'));

    }

    // -----------------------------------------------------------------------------

    /**
     * Shortcut to get the request
     */
    protected function _getRequest(): IRequest
    {
        return Application::request();
    }

    // -----------------------------------------------------------------------------

    protected mixed $_returnValue = null;

    public function getReturnValue(): mixed
    {
        return $this->_returnValue;
    }

    public function setReturnValue(mixed $returnValue): static
    {
        $this->_returnValue = $returnValue;
        return $this;
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

    protected ?string $_returnType = null;

    public function getReturnType(): string
    {
        if (null === $this->_returnType) {
            return match (gettype($this->_returnValue)) {
                'object' => get_class($this->_returnValue),
                default => gettype($this->_returnValue),
            }; // match
        }
        return $this->_returnType;
    }

    public function setReturnType(string $returnType): static
    {
        $this->_returnType = $returnType;
        return $this;
    }

    // -----------------------------------------------------------------------------

    protected ?int $_returnTotal = null;

    /**
     * When countable it will return the total of items in the result set
     * @return int|null
     */
    public function getReturnTotal() : ?int
    {
        if (null === $this->_returnTotal || count($this->getReturnValue()) > $this->_returnTotal) {
            $this->_returnTotal = count($this->getReturnValue());
        }
        return $this->_returnTotal;
    }

    /**
     * @param int|null $returnTotal
     * @return static
     */
    public function setReturnTotal(int $returnTotal = null): static
    {
        $this->_returnTotal = $returnTotal;
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * Whether the result value is iterable or countable
     * @return bool
     */
    #[Pure] protected function returnIsCountable(): bool
    {
        return
            is_array($this->getReturnValue())
            ||
            (
                $this->_returnValue instanceof ArrayAccess &&
                $this->_returnValue instanceof Traversable &&
                $this->_returnValue instanceof Serializable &&
                $this->_returnValue instanceof Countable
            );
    }

    // -----------------------------------------------------------------------------

    /**
     * The start point in the result set
     * @return int
     */
    public function getReturnOffset(): int
    {
        return (int)  $this->_getRequest()->getGetData('offset', FILTER_VALIDATE_INT);
    }


    /**
     * Default value for the items per page
     */
    const DEFAULT_LIMIT = 20;

    /**
     * The items per page
     * @return int
     */
    public function getReturnLimit(): int
    {
        $limit = (int) $this->_getRequest()->getGetData('limit', FILTER_VALIDATE_INT, static::DEFAULT_LIMIT);
        return  ($this->getReturnCount() < $limit) ?  $this->getReturnCount() : $limit;
    }

    // -----------------------------------------------------------------------------

    public function setContent(mixed $content, bool $append = false): static
    {

        if ($append) {
            $this->appendContent($content);
        } else {
            $this->_returnValue = $content;
        }
        return $this;
    }

    public function appendContent(mixed $content): static
    {
        if (is_array($this->getReturnValue())) {
            if (is_array($content)) {
                $this->setReturnValue(array_merge($this->getReturnValue(), $content));
            } else {
                $this->setReturnValue($this->getReturnValue()[] = $content);
            }
        } else {
            if (is_numeric($this->getReturnValue())) {
                $this->setReturnValue($this->getReturnValue() + $content);
            } else {
                if (is_string($this->getReturnValue())) {
                    $this->setReturnValue($this->getReturnValue() . $content);
                } else {
                    $this->setReturnValue([$this->getReturnValue(), $content]);
                }
            }
        }

        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * Returns jsonSerializable data for the value __$value__
     * @param mixed $value
     * @return mixed
     */
    #[Pure]
    protected function jsonable(mixed $value): mixed
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

    public function addMessage(string $message, ?string $index = null): static
    {
        $i = count($this->messages);
        $this->messages[$i] =  ['text' => $message];
        if(is_string($index)) {
            $this->messages[$i]['name'] = $index;
        }
        return $this;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function setMessages(array $messages = []): static
    {
        foreach($messages as $index => $message) {
            $this->addMessage($message, $index);
        }
        return $this;
    }

    // -----------------------------------------------------------------------------

    /**
     * The number of seconds the resource took to load
     * @return float
     * @throws \Quadro\Config\Exception
     */
    public function getExecutionTime(): float
    {
        return (float)number_format(Application::getInstance()->getDeltaTime(), 10);
    }

    // -----------------------------------------------------------------------------

    /**
     * Adds links for pagination if we have a countable return value
     * @return static
     */
    public function addPaginationLinks(): static
    {
        if ($this->returnIsCountable()) {
            $limit = (int) $this->_getRequest()->getGetData('limit', FILTER_VALIDATE_INT, Response::DEFAULT_LIMIT);
            $offset = (int) $this->_getRequest()->getGetData('offset', FILTER_VALIDATE_INT, 0);
            $urlBase =  $this->_getRequest()->getScheme() . '://' .  $this->_getRequest()->getHost() . parse_url( $this->_getRequest()->getPath(),PHP_URL_PATH);
            $total = $this->getReturnTotal();
            if ($limit > $total) {
                $limit = $total;
            }
            if ($offset >  0) {
                $this->addLink(
                    EnumLinkRelations::First,
                    $urlBase . sprintf('?offset=%d&limit=%d', 0, $limit),
                    $this->_getRequest()->getMethod()
                );
            }
            if (($total-$offset) > $limit) {
                $this->addLink(
                    EnumLinkRelations::Last,
                    $urlBase . sprintf('?offset=%d&limit=%d', $total - $limit, $limit),
                     $this->_getRequest()->getMethod()
                );
            }
            if ($offset - $limit >= 0) {
                $this->addLink(
                    EnumLinkRelations::Prev,
                    $urlBase . sprintf('?offset=%d&limit=%d', $offset - $limit, $limit),
                    $this->_getRequest()->getMethod()
                );
            }
            if ($offset + $limit < $total) {
                $this->addLink(
                    EnumLinkRelations::Next,
                    $urlBase . sprintf('?offset=%d&limit=%d', $offset + $limit, $limit),
                    $this->_getRequest()->getMethod()
                );
            }
        }
        return $this;
    }

    // ----------------------------------------------------------------------------------------------------------------

    /**
     * Returns the Response object as a json encoded string
     * @return array
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

        if(count($this->getMessages())) {
            $response['messages'] = $this->getMessages();
        }

        if (count($this->getLinks())) {
            $links = [];
            foreach ($this->getLinks() as $rel => $link) {
                $links[$rel] = $link;
            }
            try {
                $response['linksTo'] = $links;
            } catch (\Throwable $e) {

            }
        }

        if (Application::environment() != Application::ENV_PRODUCTION) {
            $response['debug']['status']['code']    = $this->getStatusCode();
            $response['debug']['status']['text']    = $this->getStatusText();
            $t = Application::getInstance()->getStartTime();
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
            $d = new DateTime(date('Y-m-d H:i:s.' . $micro, (int)$t));
            $response['debug']['execution']['start'] = $d->format("Y-m-d H:i:s.u");
            $delta = Application::getInstance()->getDeltaTime();
            $t = $t + $delta;
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
            $d = new DateTime(date('Y-m-d H:i:s.' . $micro, (int)$t));
            $response['debug']['execution']['end'] = $d->format("Y-m-d H:i:s.u");
            $response['debug']['execution']['duration'] = number_format($delta, 6);
            $response['debug']['environment'] = Application::getInstance()->getEnvironment();
            $response['debug']['headers'] = $this->getHeaders();
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

    /**
     * @throws \JsonException|Exception
     */
    public function getContent(): string
    {
        try {
            $body = json_encode(
                $this,
                JSON_THROW_ON_ERROR |
                JSON_NUMERIC_CHECK |
                JSON_UNESCAPED_SLASHES |
                JSON_INVALID_UTF8_IGNORE |
                JSON_UNESCAPED_UNICODE
            );
        } catch(\Throwable $t){
            print_r($t);
            print_r($this->toArray());
            exit();
        }
        return $body;
    }




    /**
     * @return array
     * @throws Application\RegistryException|Exception
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


    /*
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

    */

} // class