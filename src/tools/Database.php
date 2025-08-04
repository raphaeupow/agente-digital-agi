<?php
namespace robot\tools;
class Database
{    
    private static $connection;

    public static function init($host, $user, $password, $dbname)
    {
        try {
            self::$connection = new \PDO("mysql:host=$host;dbname=$dbname", $user, $password);
         } catch (\PDOException $e) {
            throw new \Exception("Erro ao se conectar: ".$e->getMessage());
         }        
    }

    public static function get(): \PDO
    {
        return self::$connection;
    }
}
