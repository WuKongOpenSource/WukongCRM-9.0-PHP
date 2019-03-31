<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!ini_get('date.timezone')) {
    date_default_timezone_set('GMT');
}

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->add('Aliyun\\Test', __DIR__);
if (!defined("ALIYUN_CONFIG_PATH")) {
    define("ALIYUN_CONFIG_PATH", __DIR__);
}