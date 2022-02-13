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

namespace Quadro\Config;

use Quadro\Config\Exception as Exception;

/** 
 * Set of methods to manage options in a class/object
 *
 * @package libraries\Quadro
 * @author Rob <rob@jaribio.com>
 */
trait OptionsTrait
{
     
    // The first placeholder(%s) is the name of the option key,
    // the second the name of the class the Trait is being used in.
    protected string $requiredMissingMessage = 'Required option "%s" is missing in "%s::options"!';
    protected string $notAllowedMessage      = 'Option "%s" not allowed in "%s::options"!';
    protected string $optionNotFoundMessage  = 'Option "%s" not found in "%s::options" and no default value provided.';
    protected string $alreadySetMessage      = 'Option "%s" already set in "%s::options"!';

    /**
     * @ignore (do not show up in generated documentation)
     * @var array|string[] If not empty only these options keys are required
     */
    protected array $requiredOptions = [];
    
    /**
     * @ignore (do not show up in generated documentation)
     * @var array|string[] White list options. If not empty only these options are allowed
     */
    protected array $allowedOptions = [];

    /**
     * @ignore (do not show up in generated documentation)
     * @var array Internal list with options;
     */
    protected array $options = [];

    /**
     * @ignore (do not show up in generated documentation)
     * @var mixed|null Called when an option is changed
     */
    protected mixed /*callable*/ $onOptionChange = null;

    /**
     * Sets or overwrites the entire options list. 
     * 
     * The __Options::$requiredOptions__ and __Options::$allowedOptions__
     * will be taken into account.
     * 
     * @see Config::$allowedOptions
     * @see Config::$requiredOptions
     * 
     * @param array $options
     * @return object The current instance of the class implementing this trait
     * @throws Exception
     */
    public function setOptions(array $options): object
    {
        foreach($this->requiredOptions as $key) {
            if(!array_key_exists($key, $options)){
                 throw new Exception(sprintf($this->requiredMissingMessage, $key, $this->_getCallee()));
            }
        }       
        if (count($this->allowedOptions)) {
            foreach(array_keys($options) as $key) {
                if(!in_array($key, $this->allowedOptions)){
                     throw new Exception(sprintf($this->notAllowedMessage, $key, $this->_getCallee()));
                }
            }
        }
        $this->options = $options;
        return $this;
    }



    /**
     * Returns all the options 
     */
    public function getOptions(): array
    {
        return $this->options;
    }


    
    /**
     * Gets the value for an option.
     * 
     * If a no option is found with the key __$key__ and a default is provided
     * the default value is returned. An exception is thrown otherwise.
     * 
     * @param  string $key     The index of the option
     * @param  mixed  $default Defaults to NULL, default value if the option is not found
     * @return mixed
     * @throws Exception
     */
    public function getOption(string $key, mixed $default=null): mixed
    {
        if (count($this->allowedOptions)) {
            if (!in_array($key, $this->allowedOptions)){
                throw new Exception(sprintf($this->notAllowedMessage, $key, $this->_getCallee()));
            }
        }
        $keys = explode('.', $key);
        $current = &$this->options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                if (null===$default) {
                    throw new Exception(sprintf($this->optionNotFoundMessage, $key, $this->_getCallee()));
                }
                return $default;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return $current;
    }

    protected function _getCallee(): string
    {
        $callee  = '' ; //static::class;
        $backtrace = debug_backtrace();
        if (isset($backtrace[2])) {
            $callee  = $backtrace[2]['class']??'';
            $callee .= $callee==''?'':'::';

            $callee .= $backtrace[2]['function']??'';
            $callee .= $callee==''?'':'()';
        }
        if ('' == $callee) $callee = static::class;
        return $callee;
    }



    /**
     * Sets one option
     *
     * Throws an exception when the option already is set and __$overWrite__
     * is set to false
     *
     * The __Options::$requiredOptions__ and __Options::$allowedOptions__
     * will be taken into account.
     *
     * @see Config::$allowedOptions
     * @see Config::$requiredOptions
     *
     * @param  string $key       The index of the option
     * @param  mixed  $value     The value for the option
     * @param  bool   $overWrite Whether to overwrite if the option exists
     * @return static            The current instance of the class implementing this trait
     * @throws Exception         When $overWrite is false and the option already exists
     */
    public function setOption(string $key, mixed $value, bool $overWrite=false ): Static
    {
        //if($key == 'response.class' )
        //    exit(__METHOD__ . ' :: ' . __LINE__ . ' @ ' . __FILE__ );

        if ($this->hasOption($key) && !$overWrite) {
            throw new Exception(sprintf($this->alreadySetMessage, $key, $this->_getCallee()));
        }
        if (count($this->allowedOptions)) {
            if (!in_array($key, $this->allowedOptions)){
                throw new Exception(sprintf($this->notAllowedMessage, $key, $this->_getCallee()));
            }
        }
        $keys = explode('.', $key);
        $current = &$this->options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!isset($current[$keys[$keyIndex]])) {
                if (!is_array($current)) $current = [];
                $current[$keys[$keyIndex]] = [];
            }
            $current = &$current[$keys[$keyIndex]];
        }
        $old = $current;
        $current = $value;
        if(is_callable($this->onOptionChange)){
            call_user_func($this->onOptionChange,$key, $old, $value);
        }
        return $this;
    }



    /**     
     * Checks if an option exists.
     * 
     * @param  string $key The index of the option
     * @return bool        TRUE when there is an option with index __$key__, FALSE otherwise
     */
    public function hasOption(string $key): bool
    {

        $keys = explode('.', $key);
        $current = &$this->options;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                return false;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return true;
    }


    
    /**
     * Checks whether an options equal the given value
     * 
     * Returns _TRUE_ when option with index __$key__ is set and is of the same value
     * as __$value__, _FALSE_ otherwise. 
     * 
     * @param  string $key   The index of the option
     * @param  mixed  $value The value for the option
     * @return bool          TRUE when equal, FALSE otherwise
     * @throws Exception
     */
    public function optionEquals(string $key, mixed $value): bool
    {
        return ($this->hasOption($key) && $this->getOption($key) == $value);
        
    } // optionEquals(...)
   
    
} // trait
