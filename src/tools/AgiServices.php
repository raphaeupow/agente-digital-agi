<?php
namespace robot\tools;

class AgiServices
{
    private static $instance = null;
    
    public static function getInstance():Agi 
    {
        if (self::$instance === null) {
            self::$instance = new Agi();
        }
        
        return self::$instance;
    }
}
