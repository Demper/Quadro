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

namespace Quadro\Request;

use Quadro\Request\Exception as Exception;

enum EnumRequestMethods: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
    case PURGE = 'PURGE';  // Squid and Varnish support
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * @throws Exception
     */
    public static function strToEnum(string $scheme): EnumRequestMethods
    {
        $scheme = strtoupper($scheme);
        foreach(EnumRequestMethods::cases() as $case){
            if ($scheme === $case->name) {
                return  $case;
            };
        }
        throw new Exception(sprintf('Only %s are supported, %s given', implode('|', EnumRequestMethods::list()), $scheme));
    }

    /**
     * @return array<string>
     */
    public static function list(): array
    {
        $list = [];
        foreach(EnumRequestMethods::cases() as $enum) {
            $list[] = $enum->name;
        }
        return $list;
    }

}