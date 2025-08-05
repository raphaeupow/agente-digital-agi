<?php
namespace robot\tools;

use robot\tools\provider\ProviderInterface;

class Provider 
{
    private static $instance = null;
    public static function init($name): ProviderInterface
    {
        if (self::$instance === null) {
            try {
                $class = "robot\\tools\\provider\\" . $name;
                self::$instance = new $class();
            } catch (\Exception $e) {
                throw new \Exception("Provider not found: " . $e->getMessage());
            }
        }
        return self::$instance; 
    }
    public static function get() : ProviderInterface
    {
        if (self::$instance === null) {
            throw new \Exception("Provider not initialized, call init method first");
        }
        return self::$instance;
    }

}

