<?php
namespace Falgun\DInjector;

class Singleton
{

    protected static $instances;

    public static function getInstance($class)
    {
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        return self::$instances[$class] = new $class();
    }

    public static function setInstance($obj)
    {
        return self::$instances[get_class($obj)] = $obj;
    }

    public static function get($class)
    {
        return self::getInstance($class);
    }

    public static function set($object)
    {
        return self::setInstance($object);
    }
}
