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

class MyDb extends \PDO
{

    protected function __construct(array $options)
    {

        parent::__construct($dsn, $username, $password, $settings);
    }

    public static array $instancesCache = [];

    public static function factory(array $options): MyDb
    {
        $dsn      = $options['dsn'] ?? '';
        $username = $options['username'] ?? null;
        $password = $options['password'] ?? null;
        $settings = $options['options'] ?? null;

        $dsn      = $options['dsn'] ?? '';
        $instanceKey = md5(  $dsn);


        if(!isset(self::$instancesCache[$instanceKey])) {

            // check for a class as a dsn
            $pattern = '@^class:(.*)@';
            $match = [];
            if (preg_match($pattern, $dsn, $match)){
                $class = trim($match[1]);
                self::$instancesCache[$instanceKey] = new $class($options);
            } else {
                self::$instancesCache[$instanceKey] = new self($options);
            }
        }

        return self::$instancesCache[$instanceKey];
    }

}

try {

    $db = MyDb::factory([
        'dsn' => 'class: \Quadro\Db\SQLite',
        'options' => [
            'name' => 'db',
            'location' => QUADRO_DIR_APPLICATION. '../__data/'
        ]
    ]);
//    $db = MyDb::factory([
//        'dsn' => 'sqlite:' . Q_APPLICATION_DIR . '../__data/db.sqlite',
//
//    ]);
    var_dump($db);
    var_dump(MyDb::$instancesCache);



} catch(Throwable $e){
    echo "\n"; print_r($e->getMessage());
}
exit();