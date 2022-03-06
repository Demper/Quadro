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

use Quadro\Request;

class Http extends Request
{


    // ---------------------------------------------------------------------------------------------------

    protected EnumRequestSchemes $_scheme;

    /**
     * @return EnumRequestSchemes The used scheme, HTTP or HTTPS
     */
    public function getScheme(): EnumRequestSchemes
    {
        return $this->_scheme ?? ($this->isSecure() ? EnumRequestSchemes::HTTPS : EnumRequestSchemes::HTTP);
    }

    /**
     * @param EnumRequestSchemes $scheme
     * @return $this
     */
    protected function _setScheme(EnumRequestSchemes $scheme): static
    {
        if ($scheme == EnumRequestSchemes::HTTP || $scheme == EnumRequestSchemes::HTTPS) {
            $this->_scheme = $scheme;
        } else {
            $this->_scheme = EnumRequestSchemes::HTTPS;
        }
        return $this;
    }

    // ---------------------------------------------------------------------------------------------------

    public function isSecure(): bool
    {
        $secure = false;
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)){
            $secure = true;
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                $secure = true;
            } else{
                if(isset($this->_scheme) && $this->_scheme == EnumRequestSchemes::HTTPS) {
                    $secure = true;
                }
            }
        }
        return $secure;
    }

    // ---------------------------------------------------------------------------------------------------

    public function getHost(): string
    {
        return $this->_host ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    // ---------------------------------------------------------------------------------------------------

    public static function getSingletonName(): string
    {
        return 'Quadro\Request';
    }


}