<?php
namespace robot\tools;
use PDO;

class Database
{    
    private static $connection;
    private static $host;
    private static $user;
    private static $password;
    private static $dbname;

    public static function init()
    {
        try {
            self::$host = getenv('DB_HOST');
            self::$user = getenv('DB_USER');
            self::$password = getenv('DB_PASS');
            self::$dbname = getenv('DB_NAME');

            self::$connection = new PDO("mysql:host=".self::$host.";dbname=".self::$dbname, self::$user, self::$password);
         } catch (\PDOException $e) {
            throw new \Exception("Erro ao se conectar: ".$e->getMessage());
         }        
    }

    public static function get(): PDO
    {
        if (self::$connection === null) {
            self::init();
        }
        return self::$connection;
    }
}
