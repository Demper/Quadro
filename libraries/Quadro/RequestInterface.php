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

use Quadro\Request\EnumRequestMethods;
use Quadro\Request\EnumRequestSchemes;

/**
 * The HTTP Request interface
 *
 * @package Quadro\Http
 */
interface RequestInterface
{

    /**
     * @return float
     */
    public function getRequestTime(): float;

    /**
     * @return string
     */
    public function getRemoteAddress(): string;

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array;

    /**
     * @param string $headerName
     * @return string
     */
    public function getHeader(string $headerName): string;

    /**
     * @return EnumRequestSchemes
     */
    public function getScheme(): EnumRequestSchemes;

    /**
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * @return string
     */
    public function getHost(): string;

    /**
     * @return int
     */
    public function getPort(): int;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return array<int, string>
     */
    public function getSlugs(): array;

    /**
     * @return EnumRequestMethods
     */
    public function getMethod(): EnumRequestMethods;

    /**
     * @param string|null $key
     * @param int $flags
     * @param mixed|null $default
     * @return int|float|string|array<int|string, mixed>
     */
    public function getGetData(string|null $key=null, int $flags = 0, mixed $default=null): int|float|string|array;

    /**
     * @param string|null $key
     * @param int $flags
     * @param mixed|null $default
     * @return int|float|string|array<int|string, mixed>
     */
    public function getPostData(string|null $key=null, int $flags = 0, mixed $default=null): int|float|string|array;

    /**
     * @return string
     */
    public function getRawBody(): string;

    /**
     * @return string
     */
    public function getSignature(): string;

}