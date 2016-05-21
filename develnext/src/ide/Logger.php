<?php
namespace ide;

use ide\systems\IdeSystem;
use php\io\Stream;
use php\lang\Environment;
use php\lang\IllegalArgumentException;
use php\lib\char;
use php\lib\Str;
use php\time\Time;

/**
 * Class Logger
 * @package ide
 */
class Logger
{
    protected static $ANSI_CODES = array(
        "off"        => 0,
        "bold"       => 1,
        "italic"     => 3,
        "underline"  => 4,
        "blink"      => 5,
        "inverse"    => 7,
        "hidden"     => 8,
        "gray"       => 30,
        "red"        => 31,
        "green"      => 32,
        "yellow"     => 33,
        "blue"       => 34,
        "magenta"    => 35,
        "cyan"       => 36,
        "silver"     => "0;37",
        "white"      => 37,
        "black_bg"   => 40,
        "red_bg"     => 41,
        "green_bg"   => 42,
        "yellow_bg"  => 43,
        "blue_bg"    => 44,
        "magenta_bg" => 45,
        "cyan_bg"    => 46,
        "white_bg"   => 47,
    );

    const LEVEL_ERROR = 1;
    const LEVEL_WARN = 2;

    const LEVEL_INFO = 100;
    const LEVEL_DEBUG = 200;

    protected static $level = self::LEVEL_DEBUG;

    protected static function withColor($str, $color)
    {
        $color_attrs = explode("+", $color);
        $ansi_str = "";

        foreach ($color_attrs as $attr) {
            $ansi_str .= char::of(27) . "[" . self::$ANSI_CODES[$attr] . "m";
        }

        $ansi_str .= $str . char::of(27) . "[" . self::$ANSI_CODES["off"] . "m";
        return $ansi_str;
    }

    static protected function getLogName($level)
    {
        switch ($level) {
            case self::LEVEL_DEBUG: return "DEBUG";
            case self::LEVEL_INFO: return "INFO";
            case self::LEVEL_ERROR: return "ERROR";
            case self::LEVEL_WARN: return "WARN";
            default:
                return "UNKNOWN";
        }
    }

    static protected function getLogColor($level) {
        switch ($level) {
            case self::LEVEL_DEBUG: return "silver";
            case self::LEVEL_WARN: return "yellow";
            case self::LEVEL_ERROR: return "red";
            default:
                return null;
        }
    }

    static protected function log($level, $message, ...$args)
    {
        if ($level <= static::$level) {
            if (Ide::isCreated()) {
                $file = Ide::get()->getFile('ide.log');
            }

            $time = Time::now()->toString('YYYY-MM-dd HH:mm:ss');

            $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

            $class = 'Unknown';

            foreach ($stackTrace as $trace) {
                if ($trace['class'] !== __CLASS__) {
                    $class = $trace['class'];
                    break;
                }
            }

            $class = Str::replace($class, '\\', '.');

            $line = static::getLogName($level) . " [" . $class . "] ($time) " . $message . "\r\n";

            if (IdeSystem::isDevelopment()) {
                $out = Stream::of('php://stdout');

                if ($color = static::getLogColor($level)) {
                    $line = static::withColor($line, $color);
                }

                $out->write($line);
            }

            if (Ide::isCreated()) {
                Stream::putContents($file, $line, 'a+');
            }
        }
    }

    static function info($message)
    {
        static::log(self::LEVEL_INFO, $message);
    }

    static function debug($message)
    {
        static::log(self::LEVEL_DEBUG, $message);
    }

    static function warn($message)
    {
        static::log(self::LEVEL_WARN, $message);
    }

    static function error($message)
    {
        static::log(self::LEVEL_ERROR, $message);
    }

    public static function trace($message = null)
    {
        static $time;

        if ($message != null) {
            $diff = Time::millis() - $time;

            if ($time) {
                static::log(self::LEVEL_DEBUG, "[$diff ms] $message");
            }
        }

        $time = Time::millis();
    }

    static function exception($message, \BaseException $e)
    {
        $message .= "\r\n" . $e->getMessage() . " on line {$e->getLine()} at file {$e->getFile()}\r\nStack Trace:\r\n";
        $message .= $e->getTraceAsString();

        static::log(self::LEVEL_ERROR, $message);
    }

    public static function reset()
    {
        $file = Ide::get()->getFile('ide.log');
        $file->delete();

        ob_implicit_flush(true);

        Environment::current()->onOutput(function ($output) {
            $out = Stream::of('php://stdout');
            $out->write($output);

            if (Ide::isCreated()) {
                $file = Ide::get()->getFile('ide.log');

                Stream::putContents($file, $output, 'a+');
            }
        });
    }
}