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

enum EnumRequestSchemes: string
{
    case HTTP = 'HTTP';
    case HTTPS = 'HTTPS';
    case WS = 'WS';
    case WSS = 'WSS';

    /**
     * @throws Exception
     */
    public static function strToEnum(string $scheme): EnumRequestSchemes
    {
        $scheme = strtoupper($scheme);
        foreach(EnumRequestSchemes::cases() as $case){
            if ($scheme === $case->name) {
                return  $case;
            };
        }
        throw new Exception(sprintf('Only %s are supported, %s given', implode('|', EnumRequestSchemes::list()), $scheme));
    }

    /**
     * @return array<string>
     */
    public static function list(): array
    {
        $list = [];
        foreach(EnumRequestSchemes::cases() as $enum) {
            $list[] = $enum->name;
        }
        return $list;
    }

}