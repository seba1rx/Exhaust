<?php

namespace Exhaust\Patterns;

/**
 * The singleton class to extend it as it is needed
 */
class Singleton
{
    private static $instances;

    /**
     * This is a singleton class, so constructor is protected
     */
    final protected function __construct()
    {}

    /**
     * cloning is not allowed for singletons, do not write logic code here
     */
    final protected function __clone()
    {}

    final public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    final public static function getInstance()
    {
        $subclass = static::class;
        if(!isset(self::$instances[$subclass])){
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }

}