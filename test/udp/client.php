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


$udpWorker = new Worker('udp://0.0.0.0:'. Config::get('client_port'));
$udpWorker->name = 'UdpClientWork';
$udpWorker->onWorkerStart = function($work){
    //echo "onWorkerStart\r\n"; 
};
$udpWorker->onMessage = function($connection, $message){
    //通知接受到了
    $connection->send('ok');
    //打印消息
    echo $message . ' 来自'. $connection->getRemoteIp() . "\n";
};
// 运行所有服务
Worker::runAll();
