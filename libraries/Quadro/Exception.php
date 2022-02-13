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
use JsonSerializable;

/**
 * The base Exception for all the Quadro Exceptions
 * @package Quadro
 */
class Exception extends \Exception implements JsonSerializable
{
    /**
     * Returns a Json array presentation off the Exception
     *
     * The information differs based on the environment(production or other)
     *
     * @return array
     * @throws Config\Exception
     */
    #[ArrayShape([
        'code' => "int|mixed",
        'message' => "string",
        'file' => "string",
        'line' => "int",
        'previous' => "\Exception",
        'trace' => "array"
    ])]
    public function jsonSerialize(): array
    {
        if (Application::getInstance()->getEnvironment() === QUADRO_ENV_PRODUCTION) {
            return [
                'code' => $this->getCode(),
                'message' => 'Exception'
            ];
        }  else {
            return [
                'code' => $this->getCode(),
                'message' => $this->getMessage(),
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'previous' => $this->getPrevious(),
                'trace' => $this->getTrace()
            ];
        }
    }

    /**
     * @see __toString()
     * @return string
     */
    public function __invoke(): string
    {
        return $this->__toString();
    }

    /**
     * Returns a string representation off the Exception
     *
     * The information differs based on the environment(production or other)
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->_inProduction()) {
            return 'Exception, code = ' .  $this->getCode();
        } else {
            return sprintf( '%s : %d - %s (%s @ %s)',
                get_class($this),
                $this->getCode(),
                $this->getMessage(),
                $this->getFile(),
                $this->getLine()
            );
        }
    }

    /**
     * When using the Application::getEnvironment() function a Config/Exception could be thrown.
     * Throwing an Exception in an Exception is considered bad practice.
     *
     * @ignore (do not show up in generated documentation)
     * @return bool
     */
    protected function _inProduction(): bool
    {
        if (false === getenv(QUADRO_ENV_INDEX) ) {
            putenv(QUADRO_ENV_INDEX .'=' . QUADRO_ENV_PRODUCTION);
        }
        return getenv(QUADRO_ENV_INDEX) === QUADRO_ENV_PRODUCTION;
    }


} // class