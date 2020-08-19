<?php
/**
 * run with command 
 * php client.php start
 */
ini_set('display_errors', 'on');

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use core\common\Config;


//定义常量
define('WORKERMAN_APP_DEBUG', true);
define('WORKERMAN_APP_ENV', 'dev');

//自动加载类文件
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Autoloader.php';
spl_autoload_register(['Autoloader', 'autoload'], true, true);
//读取配置
Config::$config = require(__DIR__ . '/../../config/tcp.php');


$worker = new Worker();
$worker->name = 'TcpClientWork';
$worker->onWorkerStart = function($work){
    //echo "onWorkerStart\r\n"; 
    //异步发送socket消息
    $task_connection = new AsyncTcpConnection('tcp://'. Config::get('tcp_ip') .':' . Config::get('tcp_port'));
    // 发送数据
    $task_data = ['event' => 'handshake' ,'uid' => 'abc', 'device' => 'ios'];
    $task_connection->send(json_encode($task_data));
    // 发送数据
//    $task_data = ['event' => 'ping' ,'client_id' => 'abc'];
//    $task_connection->send(json_encode($task_data));
//    // 异步获得结果
    $task_connection->onMessage = function($task_connection, $task_result)
    {
         // 结果
         var_dump($task_result);
         // 获得结果后记得关闭异步连接
         //$task_connection->close();
//        $task_data = ['event' => 'ping' ,'client_id' => 'abc'];
//        $task_connection->send(json_encode($task_data));         
    };
    // 执行异步连接
    $task_connection->connect();
    
};
$worker->onMessage = function($connection, $message){
    //通知接受到了
    $connection->send('ok');
    //打印消息
    echo $message . ' 来自'. $connection->getRemoteIp() . "\n";
};
// 运行所有服务
Worker::runAll();
