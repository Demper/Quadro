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

use Quadro\Http\RequestInterface as IRequest;
use Quadro\Http\ResponseInterface as IResponse;

/**
 * Interface DispatcherInterface
 *
 * Any (custom) Dispatcher must implement this interface
 * @package Quadro
 */
interface DispatcherInterface
{
    /**
     * Returns the response for the given request.
     *
     * The return Response value can be:
     * a) FALSE     No match
     * b) IResponse There is a match and the returned (customized) Response object is ready to be send
     * c) other     Data is returned to be handled by the default Response object
     *
     * @param IRequest $request
     * @return mixed Returns the response on the request
     */
    public function handleRequest(IRequest $request):  mixed;

} // interface
