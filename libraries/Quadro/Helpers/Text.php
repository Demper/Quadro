<?php
declare(strict_types=1);

namespace Quadro\Helpers;

class Text
{


    /**
     * @param string $text
     * @return string
     */
    public static function base64UrlEncode(string $text): string
    {
        return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
    }


}