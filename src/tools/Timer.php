<?php
namespace robot\tools;

class Timer
{
    private static float $startTime;
    private static float $endTime;

    public static function start(): void
    {
        self::$startTime = microtime(true);
    }

    public static function stop(): void
    {
        self::$endTime = microtime(true);
    }

    public static function getElapsedTime(int $precision = 4): float
    {
        return round(self::$endTime - self::$startTime, $precision);
    }

    public static function getElapsedTimeMs(int $precision = 2): float
    {
        return round((self::$endTime - self::$startTime) * 1000, $precision);
    }

    public static function getReadable(): string
    {
        $elapsed = self::$endTime - self::$startTime;
        return gmdate("H:i:s", (int)$elapsed) . sprintf(".%03d", ($elapsed - floor($elapsed)) * 1000);
    }
}
