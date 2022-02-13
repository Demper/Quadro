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

/**
 * The HTTP Request interface
 *
 * @package Quadro\Http
 */
interface RequestInterface
{
    public function getRequestTime(): float;
    public function getRemoteAddress(): string;
    public function getHeaders(string $headerName=null): array|string;
    public function getScheme(): string;
    public function getHost(): string;
    public function getPort(): int;
    public function getPath(): string;
    public function getGetData(string|null $key=null, int $flags = 0, mixed $default=null): mixed;
    public function getPostData(string|null $key=null, int $flags = 0, mixed $default=null): mixed;
    public function getRawBody(): string;
    public function getSignature(): string;
}