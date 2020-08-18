<?php

/**
 * run with command 
 * php start.php start
 */
ini_set('display_errors', 'on');

use Workerman\Worker;
use Workerman\Autoloader;
use core\common\Config;

//定义常量
define('WORKERMAN_APP_DEBUG', true);
define('WORKERMAN_APP_ENV', 'dev');

//自动加载类文件
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Autoloader.php';
spl_autoload_register(['Autoloader', 'autoload'], true, true);
//读取配置
Config::$config = require(__DIR__ . '/../../config/udp.php');

// worker 任务进程
$work = new core\work\UdpWorker(Config::get('udp'));
$work->apiConfig = Config::get('api');
$work->client_port = Config::get('client_port');
Worker::runAll();
