<?php
/**
 * Router Loader.
 *
 * @version 1.0.0
 */

namespace henlibs\router;

/**
 * Router Loader.
 */
class Loader
{
    /** Class namespace */
    const NS = 'henlibs\router';

    /**
     * Register class loader.
     */
    public static function register()
    {
        spl_autoload_register(self::NS.'\Loader::loadClass');
    }

    /**
     * Unregister class loader.
     */
    public static function unregister()
    {
        spl_autoload_unregister(self::NS.'\Loader::loadClass');
    }

    /**
     * Load class.
     *
     * @param strint $class Class name
     */
    public static function loadClass($class)
    {
        if (strpos($class, self::NS) === 0) {
            $dir = dirname(__FILE__);
            $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen(self::NS))).'.php';
            $path = $dir.'/src/'.$path;
            if (file_exists($path)) {
                require $path;
            }
        }
    }
}
