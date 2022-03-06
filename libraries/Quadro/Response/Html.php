<?php

namespace Quadro\Response;

use Quadro\Response as BaseResponse;

/**
 * In the Quadro Restfull API application there can only be one response at
 * any time to the one request at any time.
 *
 * @package Quadro
 */
class Html extends BaseResponse
{
    /**
     * Response constructor.
     */
    public function __construct()
    {
        $this->setHeader('Content-Type: text/html');
    }

}