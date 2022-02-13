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

use PDO;
use Quadro\Application\Component;
use Quadro\Config\OptionsTrait;

/**
 * Base PDO wrapper class
 * 
 * @see \PDO
 * @license <http://www.wtfpl.net/about/> WTFPL
 * @package libraries\Quadro
 * @author Rob <rob@jaribio.nl>
 */
class Db extends Component implements DbInterface
{
    
    use OptionsTrait;
    
    /**
     * @var PDO $pdo Reference to a PDO object
     */
    protected PDO $pdo;



    /**
     * Wrapper for the standard \PDO object
     *
     * @param array $options
     * @throws Config\Exception
     */
    protected function __construct(array $options = [])
    {     
        $this->requiredOptions = ['dsn'];                 
        $this->setOptions($options);

        $this->pdo = new PDO(
            $this->getOption('dsn'),
            $this->getOption('username', ''),
            $this->getOption('passw', ''),
            $this->getOption('options', [])
        );
        
    }

    public static function initialize(array $options = []): Db
    {

    }
    
    /**
     * Dispatch all undefined functions to the PDO object
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }


    
    /**
     * Dispatch all undefined static functions to the PDO object 
     * 
     * @param $name $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
       return call_user_func_array(['\PDO', $name], $arguments);
    }


    
    /**
     * Dispatch all undefined property getters to the PDO object
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) :mixed
    {
        return $this->pdo->$name;
    }


    
    /**
     * Dispatch all undefined property setters to the PDO object
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->pdo->$name = $value;
    }



    /**
     * This is a wrapper for the PDO object standard with PHP. When a sub class 
     * is defined as the dsn we create an instantce of this sub class. 
     * 
     * To find the subclass we add a dsn option with the following format:
     * 
     *    class:\Namespace\And\Name\Of\The\Class
     * 
     * @see https://www.php.net/manual/en/pdo.construct.php
     * @param string $dsn
     * @param array $options
     * @return static|null
     */
    public static function factory(string $dsn, array $options=[] ):?self
    {
        // return NULL if nothing is found
        $db = null;
        
        // check for a class as a dsn
        $pattern = '@^class:(.*)@';
        $match = [];
        if (preg_match($pattern, $dsn, $match)){
            $class = $match[1]; 
            $db = new $class($options);
            
        // otherwise,  add the dsn as an option to the option list
        // and pass this through to the constructor    
        } else {
            $options['dsn'] = $dsn;
            $db = new self($options);
        }
        
        return $db;
    }


} // class