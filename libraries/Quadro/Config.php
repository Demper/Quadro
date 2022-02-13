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

use Quadro\Config\OptionsTrait;
use Quadro\Config\Exception as Exception;

/**
 * Class Config
 * Class for storing name value pairs
 * @package Quadro
 */
class Config
{

    /**
     * Setting, Getting option values
     * @see OptionsTrait
     */
    use OptionsTrait;

    /**
     * Config constructor.
     *
     * Passed options can be a PHP file returning an array or an array itself
     *
     * Config constructor.
     * @param array|string|null $options
     * @param callable|null $onOptionChangeCallback
     * @throws Exception
     */
    public function __construct(array|string $options=null, callable $onOptionChangeCallback = null)
    {
        if(null !== $options ) {
            if (is_string($options)) {
                if (!file_exists($options) || !is_readable($options) || is_dir($options)) {
                    throw new Exception(sprintf('Could not open file "%s"', $options));
                }
                $options = (array)include $options;
            }
            $this->setOptions($options);
        }
        $this->onOptionChange = $onOptionChangeCallback;
    }



    public function getRequiredOptions(): array
    {
        return $this->requiredOptions;
    }
    public function setRequiredOptions(array $requiredOptions): self
    {
        $this->requiredOptions = $requiredOptions;
        return $this;
    }



    public function getAllowedOptions(): array
    {
        return $this->allowedOptions;
    }
    public function setAllowedOptions(array $allowedOptions): self
    {
        $this->allowedOptions = $allowedOptions;
        return $this;
    }


}