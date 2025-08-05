<?php
namespace robot\tools;
use raphaeu\Colorize;
use robot\Variable;

class Debug
{
    public static $phone;

    public static function init($phone)
    {
        self::$phone = $phone;
    }

    public static function title($component, $statusId)
    {
        self::print(
        Colorize::bold().Colorize::blue('white')
        .str_pad(strtoupper($component), 80, ' ',STR_PAD_BOTH )
        .Colorize::clear()
        .Colorize::ciano("black")
        .str_pad($statusId, 20, ' ',STR_PAD_BOTH )
        .Colorize::clear());
    }

    public static function subTitle($text)
    {
        self::print(Colorize::black('white').str_pad(strtoupper($text), 100, ' ',STR_PAD_BOTH ).Colorize::clear());
    }

    public static function success($text)
    {
        self::print($text);
    }

    public static function warning($text)
    {
        self::print($text);
    }

    public static function error($text)
    {
        self::print($text);
    }
    
    public static function notice($text)
    {
        self::print($text);
    }

    public static function info($text)
    {
        self::print($text);
    }

    public static function debug($debug)
    {
        AgiServices::getInstance()->verbose(PHP_EOL. print_r($debug, true) . PHP_EOL);
    }

    private static function print($str)
    {
        $trace = debug_backtrace();
        $type = $trace[1]['function'];
        if (in_array($type, ['info', 'success', 'warning', 'error', 'notice'])) {
            $log[] = ["type" => $type, "datetime" => date('Y/m/d h:i:s'), "message" => $str];
            $logHistory = Variable::systemGet("debug")?Variable::systemGet("debug"):[];
            $debug = array_merge($log , $logHistory);
            Variable::systemSet ("debug", $debug);
        }

        if (in_array($type, ['title', 'subTitle'])) {
            AgiServices::getInstance()->verbose($str );
        }
        else if (in_array($type, ['info', 'success', 'warning', 'error', 'notice']))
        {
            switch ($type) {
                case 'info':
                    $color = Colorize::white();
                    break;
                case 'success':
                    $color = Colorize::green();
                    break;
                case 'warning':
                    $color = Colorize::yellow();
                    break;
                case 'error':
                    $color = Colorize::red();
                    break;
                case 'notice':
                    $color = Colorize::magenta();
                    break;
            }
            AgiServices::getInstance()->verbose( $color."[".date('d/m/Y H:i:s')."][".strtoupper($type)."] ". $str . Colorize::clear()  );
        }

    }
}