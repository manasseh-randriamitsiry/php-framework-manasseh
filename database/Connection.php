<?php
namespace Database;
use PDO;

Class Connection{
    public static function makeConnection($config)
    {
        try {
           return new PDO(
               'mysql:host='.$config['host'].';
               dbname='.$config['dbname'],
               $config['user'],
               $config['password']
           );
        } catch (\Throwable $throwable){
            die($throwable->getMessage());
        }
    }
}
