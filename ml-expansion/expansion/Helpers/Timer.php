<?php

namespace ManiaLivePlugins\eXpansion\Helpers;

use ManiaLive\Utilities\Console as Cnsole;
use ManiaLive\Utilities\Logger;

/**
 * Description of Timer
 *
 * @author Petri
 */
class Timer
{

    public static $nbTimers = 0;
    public static $times = array();

    public static $time;

    public static function set()
    {
        self::$time = -microtime(true);
        Cnsole::println("Profiler timer started.");
    }

    public static function get()
    {
        if (empty(self::$time)) {
            self::set();
        } else {
            Cnsole::println("Profiler ended: " . (self::$time + microtime(true)) . "ms");

            return (self::$time + microtime(true));
        }
    }

    public static function startNewTimer($message, $print = true, $log = true)
    {
        $id = self::$nbTimers++;
        self::$times[$id] = microtime(true);
        if ($print) {
            Cnsole::println($message . ' Started ...');
        }
        if ($log) {
            Logger::info($message . ' Started ...');
        }

        return $id;
    }

    public static function endTimer($id, $message, $print = true, $log = true)
    {
        if (isset(self::$times[$id])) {
            $time = microtime(true) - self::$times[$id];
            if ($print) {
                Cnsole::println($message . ' Ended in : ' . $time . 'ms');
            }
            if ($log) {
                Logger::info($message . ' Ended in : ' . $time . 'ms');
            }

            return $time;
        }

        return 0;
    }
}
