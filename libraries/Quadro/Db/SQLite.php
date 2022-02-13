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

namespace Quadro\Db;

use \Quadro\Db as BaseDb;

/**
* PDO wrapper for SQLite
*  
*/
class SQLite extends BaseDb
{      
    
    /**
     * The default SQLite file extension
     */
    const FILE_DEFAULT_EXTENSION = 'sqlite3';

    /**
     * Connects to a SQLite database and creates one if not exists.
     *
     * Based on the SQLite driver of PDO. There will be no neeed to check for
     * installed PDO and/or for the sqlite driver. PDO and the PDO_SQLITE driver is enabled
     * by default as of PHP 5.1.0.
     *
     * @see:   https://www.php.net/manual/en/pdo.installation.php
     *
     * @option name      The name of the database
     * @option location  The path of the directory where to create the file
     * @option extension The extension of the file, defaults to sqlite3
     *
     * @throws \Quadro\Config\Exception
     * @throws SQLiteException
     */
    public function __construct( array $options = []) //:\Quadro\Db\SQLite
    {
         // we do not want a unending list of options, 
        $this->allowedOptions = [        
            'name',     
            'location',    
            'extension',               
        ];                 
        $this->options = array_merge($this->options, $options); 
        $name      = $this->getOption('name');
        $location  = $this->getOption('location');
        $extension = $this->getOption('extension',self::FILE_DEFAULT_EXTENSION);
                
        // check name, only alhanumeric, hyphens and uderscores
        $pattern = '/[^a-zA-Z0-9_-]/';
        $match = [];        
        if(preg_match($pattern, $name, $match)) {
           $this->throwException(sprintf('Cannot access or create database: Invalid characters in name "%s", allowed are [a-zA-Z0-9_-]', $name));
        }
                
        // check whether the file exist, if not check if the location is writable        
        $file = rtrim($location, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.'.'.$extension;
        if(!file_exists($file)) {        
            if(!is_writable($location)) {
                $this->throwException(sprintf('Cannot create database: location "%s" is not writable.', $location));
            }
        } else { 
            $this->isNew = false;
        }
        $this->file =  $file; 
        
        $this->pdo = new \PDO('sqlite:'. $this->file);  
        
    } // __construct(...)
    
    
    // ------------------------------------------------------------------------------------------------- 
    
    private bool $isNew = true;
    public function isNew(): bool
    {
        return $this->isNew;
    }
    
    // ------------------------------------------------------------------------------------------------- 
    
    /**
    * @var string $file Path and name of the database file
    */
    protected string $file;
    
    /**
     * @return string The name and path of the database file;
     */
    public function getFile():string
    {
        return $this->file;        
        
    }  // getFile()  
   
    // -------------------------------------------------------------------------------------------------
                    
    protected array $transactionStack = [];
    
    /**
     * 
     * @return array
     */
    public function getTransactionStack(): array
    {
        return $this->transactionStack;
        
    } // getTransactionStack()

    /**
     * @see https://sqlite.org/lang_savepoint.html
     */
    public function begin(string $savepoint = null): bool
    {
        array_push($this->transactionStack,$savepoint );
        if (null !== $savepoint && strlen($savepoint)) {
            return $this->exec('SAVEPOINT "'.$savepoint.'"');
        }  else { 
            return $this->exec('BEGIN');
        }
        
    } // begin(...)   
        
    /**
     * @see https://sqlite.org/lang_savepoint.html
     */
    public function commit(string $savepoint = null): bool
    {
        array_pop($this->transactionStack );
        if (null !== $savepoint && strlen($savepoint)) {
            return $this->exec('RELEASE "'.$savepoint.'"');
        }  else {            
            return $this->exec('COMMIT');
        } 
        
    } // commit()

    /**
     * @see https://sqlite.org/lang_savepoint.html
     */
    public function rollBack(string $savepoint = null): bool
    {
        array_pop($this->transactionStack);
        if (null !== $savepoint && strlen($savepoint)) {
            return $this->exec('ROLLBACK TO "'.$savepoint.'"');
        }  else { 
            return $this->exec('ROLLBACK');
        }  
        
    } // rollBack()
        
    // -------------------------------------------------------------------------------------------------
    
    /**
     * Executes a query which makes changes into the database.
     * Default with a transaction.
     * 
     * @param string $query The query
     * @param array $params The params used in the query
     * @param bool $verbose Prints out extra infomation
     * @return int          The number of affected rows, zero if none where affected
     */
    public function execute( string $query, array $params = null, bool $verbose = false): int
    {   
        $this->begin(__METHOD__);
        
        $error = null;
        if ($verbose) {echo "\n{$query}\n";}        
        if (isset($params) && count($params)) {
            
            $pdoStatement = $this->getPrepareStatement($query);   
            foreach($this->getBinds($params) as $bind){             
                if ($verbose) { echo "Bind: ", print_r( $bind), "\n"; }
                $pdoStatement->bindValue( $bind['name'], $bind['value'], $bind['type'] ); 
            }     
            $result = $pdoStatement->execute();
            $error =  $pdoStatement->errorInfo();
                    
        } else { 
            $result = $this->exec( $query );
            $error = $this->errorInfo();
        }
        
        if( false === $result ) {  
            $this->rollBack(__METHOD__); 
            $this->throwException( $error );
        } 
                    
        $this->commit(__METHOD__);
        
        if ($verbose) {
            echo "\nAffected rows {$result}\n";
        }
        
        return $result; 
        
    } // execute(...)
        
    /**
     * 
     * @param string $scriptFile
     * @param bool $verbose
     * @return int
     * @throws SQLiteException
     */
    public function executeScript(string $scriptFile, bool $verbose = false): int
    {
        if ( !file_exists( $scriptFile ) ) {
            throw new SQLiteException( sprintf( 'File not found: "%s"', $scriptFile) );
        }    
        
        $sql = file_get_contents( $scriptFile );
        
        if ($verbose) {
            echo "\nExecuting script: {$scriptFile}\n"; 
        }
        
        return $this->execute( $sql, null, $verbose );
        
    } // executeScript(...)   
   
    // -------------------------------------------------------------------------------------------------
   
    protected array $prepareCache = [];
    
    protected function getPrepareStatement($query) :\PDOStatement
    {
        $hash = md5($query);        
        if ( !isset( $this->prepareCache[$hash]) ) {
            $stmnt = $this->prepare($query);
            if ( false === $stmnt){
                 $this->throwException( $this->errorInfo() );
            }
            $this->prepareCache[$hash] = $stmnt ;
        }
        return $this->prepareCache[$hash];
        
    } // getPrepareStatement(...)
    
    // -------------------------------------------------------------------------------------------------
   
    const PARAM_NULL = 0; //  PDO::PARAM_NULL
    const PARAM_INT  = 1; //  PDO::PARAM_INT
    const PARAM_STR  = 2; //  PDO::PARAM_STR 
    const PARAM_LOB  = 3; //  PDO::PARAM_LOB
    
    
    /** 
     *
     * @param string $query  SQL Statement which should returns a list of rows
     * @param array $params   
     * @return bool          TRUE on succes throws an Exception other wise;
     */
    public function select(string $query, array $params = [], bool $verbose = false)
    {   
        $pdoStatement = $this->getPrepareStatement($query);   
        $binds = $this->getBinds($params);
        foreach($binds as $bind){             
            if ($verbose) { echo "Bind: ", print_r( $bind), "\n"; }
            $pdoStatement->bindValue( $bind['name'], $bind['value'], $bind['type'] ); 
        }
        
        // throw exception on error
        if ( false === $pdoStatement->execute() )  {
            $this->throwException( $this->errorInfo() );
        } 
        
        
        // make this a generator function 
        while($row = $pdoStatement->fetch( \PDO::FETCH_ASSOC ) ) {            
            yield $row;
        }  
        
        return null;
                
    } // select(...)
                    
    
    // -------------------------------------------------------------------------------------------------

    /**
     * @throws SQLiteException
     */
    protected function throwException(mixed $errorInfo ) //:void
    { 
        $code = $message = '';
        if ( is_array($errorInfo ) ) {
            foreach( $errorInfo as $errorIndex => $errorValue )  {
                if(is_numeric($errorValue)) {
                    if ( $code != '' ) { $code .= '::'; }
                    $code .= $errorValue;
                } else {
                    if ( $message != '' ) { $message .= "\n"; }
                    $message .= $errorValue;
                }
            }
        } else {
            $message = $errorInfo;
        }
        if ( $code == '' ) { $code = '000'; }  
        if ( $message == '' ) { $message = 'Unknown error '; }   
        throw new SQLiteException( sprintf( "%s - %s", $code, $message ) );
        
    } // throwException(...)
       
       
    protected function getBinds( array $params ) 
    {       
        $binds = []; 
        foreach( $params as $paramName => $paramValue) {  
            $bind = []; 
            
            if( !is_array( $paramValue) ) {
                $bind['name'] = ':' . trim($paramName, ':'); // make sure there is a colon in front
                $bind['value'] = $paramValue;     
                if ( is_int( $paramValue) ) {
                    $bind['type'] =  \PDO::PARAM_INT;  
                } else if ( is_null( $paramValue) ) {
                    $bind['type'] = \PDO::PARAM_NULL;  
                } else if ( is_string( $paramValue) ) {                
                    $bind['type'] = \PDO::PARAM_STR;  
                } else {
                    $bind['value'] = (string) $paramValue;
                    $bind['type'] = \PDO::PARAM_STR;                 
                }       
                
            } else { 
            
                // check all the required keys
                if ( empty($paramValue['name']) || !isset( $paramValue['value'] ) || !isset( $paramValue['type'] ) )  {
                    $this->throwException( 
                        'Make sure when passing a parameter value as an array ' .
                            'it contains a "name", "value" and "type" index with a value' 
                    );
                }  
                                
                // check whether the type is an integer
                if ( !is_int( $paramValue['type'] ) ) {
                    $this->throwException( 
                        sprintf(
                            'The "type" index when passing the value as an ' .
                                'arrray expects to be int, %s given', 
                            gettype($paramValue['type']) )
                    );
                } 
                
                // check the correct binding type
                switch ( $paramValue['type'] ) {
                    case \PDO::PARAM_INT: // no break;
                    case \PDO::PARAM_LOB: // no break;
                    case \PDO::PARAM_NULL: // no break;
                    case \PDO::PARAM_STR: break;
                    default: {
                        $this->throwException( 
                            sprintf( 
                                'Parameter types can only be of type PDO::PARAM_NULL(=%d), ' .
                                    'PDO::PARAM_INT(=%d), PDO::PARAM_STR(=%d), PDO::PARAM_LOB(=%d)',
                                \PDO::PARAM_NULL, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_LOB
                            )
                        );
                    }    
                }  
                $bind  = $paramValue;
                $bind['name'] = ':' . trim($bind['name'], ':'); // make sure there is a colon in front               
            }
            $binds[$paramName] = $bind; 
        } 
        return $binds;
    } //  protected function getBinds( array $params ) 
    
                   
    public function insertRow(string $table, array $params): ?int 
    {
        $binds = $this->getBinds($params);
        $fields = $values = '';        
        foreach($binds as $bindIndex => $bind) {
            if( '' != $fields ) $fields.= ', ';
            $fields .= $bindIndex;
            if( '' != $values ) $values.= ', ';
            $values .= $bind['name'];
        }                    
        $query = "INSERT INTO {$table} ({$fields}) VALUES({$values})";        
        if ($this->execute($query,  $binds ) ){
          return  (int) $this->lastInsertId();         
        } else {
           return  null;
        }
        
    } // insert(...)
    
    
    public function insertRows(string $table, array $paramsPerRow): ?bool
    {
        $this->begin(__METHOD__);        
        try{
            foreach( $paramsPerRow as $paramIndex => $params ) {
                $this->insertRow($table, $params);
            }
        } catch (SQLiteException $e){ 
            $this->rollBack(__METHOD__);
            throw $e;
            // return false;
        }
        $this->commit(__METHOD__);
        return true;
        
    } // insertRows
            
        
    public function deleteRow(): bool
    {
        // TODO 
        $this->throwException( __METHOD__ . " not implemented (yet)!");
        return false;
        
    } // deleteRow()
    
    
    public function deleteRows(): bool
    {
        // TODO 
        $this->throwException( __METHOD__ . " not implemented (yet)!");
        return false;
        
    } // deleteRows()
    
    
    
} // class
