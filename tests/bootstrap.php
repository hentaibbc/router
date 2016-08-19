<?php

// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_NOTICE);

function make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return $sec + $usec * 1000000;
}
mt_srand(make_seed());

$root_path = dirname(dirname(__FILE__));
include $root_path . '/vendor/autoload.php';
include $root_path . '/Loader.php';
henlibs\router\Loader::register();
