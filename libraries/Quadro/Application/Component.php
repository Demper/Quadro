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

namespace Quadro\Application;

/**
 * Class Component
 *
 * Components are the building blocks of the Quadro API framework.
 *
 * @package Quadro\Application
 */
abstract class Component implements ComponentInterface
{
    /**
     * @see ComponentInterface::getComponentName()
     * @return string
     */
    public static function getComponentName(): string
    {
        return static::class;
    }


}