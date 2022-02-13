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

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Quadro\Application\Component;
use Quadro\Config\OptionsTrait as Options;
use Quadro\Http\RequestInterface as IRequest;
use Quadro\Http\ResponseInterface as IResponse;

/** 
 * The dispatcher takes an url and tries to find a handler for it in the given folders
 * 
 * @license <http://www.wtfpl.net/about/> WTFPL
 * @package libraries\Quadro
 * @author  Rob <rob@jaribio.nl>
 */
class Dispatcher extends Component implements DispatcherInterface, JsonSerializable
{
    use Options;

    public function __construct(array $options=[])
    {
        $this->setOptions($options);
        $this->_initialize_();
    }
    protected function _initialize_()
    {}


    /**
     * Remove unwanted elements in the request URI
     *
     * @param string $requestUri
     * @return string
     */
    public function sanitizeRequestUri(string $requestUri): string{

        $sanitizedRequestUri = str_replace('..', '', $requestUri);
        return str_replace(['//', '\\\\'] , '//', $sanitizedRequestUri);
    }

    /**
     * Looks for the URI handlers for the URI __$requestUri__
     *
     * @param IRequest $request THe URI to find a handler for
     * @return mixed
     */
    public function handleRequest(IRequest $request): mixed
    {
        return false;
    }


    #[Pure]
    #[ArrayShape(['path' => "int[]|string[]"])]
    public function jsonSerialize(): array
    {
        return [
        ];
    }


    
} // class
