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

use JsonSerializable;
use Quadro\Application\RegistryException as Exception;

/**
 * Class Registry
 *
 * Central storage for component objects
 *
 * @package Quadro\Application
 */
class Registry implements JsonSerializable
{

    /**
     * The registered component objects
     * @var array
     */
    protected array $_components = [];



    /**
     * Add a component object to the registry.
     *
     * @param callable|ComponentInterface $component The component to be registered
     * @param string|null $index The (unique)index for this object. It defaults to componentInterface::getComponentName()
     *                            which defaults to the class name of the Component
     * @return ComponentInterface Newly added component
     * @throws RegistryException
     */
    final public function add(ComponentInterface|callable $component, string $index = null): ComponentInterface
    {
        if (is_callable($component)) {
            $component = (object) call_user_func($component);
            if(!$component instanceof ComponentInterface) {
                throw new Exception('Components in the registry must implement the ComponentInterface');
            }
        }

        if (null === $index) $index = $component::getComponentName();

        if (!$this->has($index)) {
            $this->_components[$index] = $component;
        } else {
            throw new Exception('Component %s already exists!', $index);
        }

        return $this->_components[$index];
    }



    /**
     * Returns the Component stored with index __$index__
     *
     * @param string $index
     * @return mixed
     * @throws RegistryException
     */
    public function get(string $index): mixed
    {
        if (!$this->has($index)) {
            throw new Exception(sprintf('Component %s not found', $index));
        }



        return $this->_components[$index];
    }



    /**
     * Removes the Component stored with index __$index__
     *
     * @param string $index
     * @return bool
     * @throws RegistryException
     */
    public function remove(string $index): bool
    {
        if (!$this->has($index)) {
            throw new Exception('Component %s not found', $index);
        }
        unset($this->_components[$index]);
        return $this->has($index);
    }


    /**
     * Whether there is a Component stored with index __$index__
     *
     * @param string $index
     * @return bool
     */
    public function has(string $index): bool
    {
        return isset($this->_components[$index]);
    }
    public static function isComponent(object|string $component): bool
    {
        return ($component instanceof ComponentInterface);
    }

    /**
     * @see \JsonSerializable PHP std Interface
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            array_keys($this->_components)
        ];
    }


}