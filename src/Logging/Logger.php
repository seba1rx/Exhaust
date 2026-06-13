<?php

namespace Exhaust\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger
{

    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * @param LoggerInterface $logger
     */
    public static function set(LoggerInterface $logger)
    {
        static::$logger = $logger;
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @param $level
     * @return
     */
    public static function log($object, $message, array $context = [], $level = LogLevel::DEBUG)
    {
        if (is_object($object)) {
            $className  = get_class($object);
            $pid        = getmypid();
            $message    = "[{$className} PID {$pid}] {$message}";
        }

        // if (static::$logger === null) {
        //     static::$logger = new ExhaustLogger();
        // }

        $json_message = json_encode([
            "class" => $object::class,
            "message" => $message,
        ]);
        static::$logger->log($level, $json_message, $context);
    }

    /**
     * @param $object
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function alert($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function critical($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function debug($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function emergency($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function error($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function info($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $object
     * @param $message
     * @param array $context
     * @return
     */
    public static function notice($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * @param $message
     * @param array $context
     * @return
     */
    public static function warning($object, $message, $context = [])
    {
        static::log($object, $message, $context, LogLevel::ALERT);
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    public function __wakeup()
    {
    }
}