<?php

namespace robot;


use robot\Tools\Debug;

class Variable {
    private static $variables;
    private static $system;
    public static function get($name): mixed
    {
        if  (isset(self::$variables[$name]))
        {
            Debug::notice("Get variable: {$name} value: ".self::$variables[$name]);
            return self::$variables[$name];
        }else{
            Debug::warning("Variable: {$name} not found!");
            return null;
        }
    }
 
    public static function set($name, $value, $level=1): void
    {
        Debug::notice("Set variable: {$name} value: {$value}");
        self::$variables[$name] = $value;
    }

    public static function dump(): bool|string
    {
        Debug::notice("Dump variables");
        return json_encode(self::$variables);
    }

    public static function systemSet($name, $value=null)   
    {
            self::$system[$name] = $value;
    }
    public static function systemGet($name)
    {
        if (isset(self::$system[$name]))
        {
            return self::$system[$name];
        }else{
            return null;
        }
    }
}
