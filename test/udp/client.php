<?php
/**
 * run with command 
 * php client.php start
 */
ini_set('display_errors', 'on');

use Workerman\Worker;
use core\common\Config;
use core\common\Util;
use core\work\UdpWorker;
use core\common\File;
use Workerman\Lib\Timer;

//定义常量
define('WORKERMAN_APP_DEBUG', true);
define('WORKERMAN_APP_ENV', 'dev');

//自动加载类文件
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../Autoloader.php';
spl_autoload_register(['Autoloader', 'autoload'], true, true);
//读取配置
Config::$config = require(__DIR__ . '/../../config/udp.php');


$udpWorker = new UdpWorker('udp://0.0.0.0:'. Config::get('client_port'));
$udpWorker->name = 'UdpClientWork';
$udpWorker->onWorkerStart = function($work){
    //echo "onWorkerStart\r\n"; 
    Timer::add(60, [$work, 'checkPackageDatas']);
};
$udpWorker->onMessage = function($connection, $message) use ($udpWorker){
    //通知接受到了
    $mergePackage = $udpWorker->mergePackage($message);
    $package_buffer = null;
    if($mergePackage['status'] === -1){
        $package_buffer = $mergePackage['package_buffer'];
        $connection->send('ok');
    }
    else if($mergePackage['status'] === -2){
        if($mergePackage['pack_order'] == 1){
            var_dump($mergePackage);
        }
        Util::echoText('接收进度：'. ($mergePackage['pack_current_count']) . '/' .$mergePackage['pack_count']);
        Util::echoText('丢包了');
    }
    else if($mergePackage['status'] === 0){
        Util::echoText('接收进度：'. ($mergePackage['pack_current_count']) . '/' .$mergePackage['pack_count']);
    }
    else if($mergePackage['status'] === 1){
        Util::echoText('接收进度：'. ($mergePackage['pack_current_count']) . '/' .$mergePackage['pack_count']);
        $package_buffer = $mergePackage['package_buffer'];
        $connection->send('ok');
    }
    //打印消息
    if(!empty($package_buffer)){
        if(!Util::isBase64($package_buffer)){
            Util::echoText($package_buffer . ' 来自'. $connection->getRemoteIp());
        }
        else{
            //如果是文件
            $fileContent = base64_decode($package_buffer);
            $fileName = Config::get('test_save_file');
            $handle = fopen($fileName, "w");
            fwrite($handle, $fileContent);
            fseek($handle, 0);
            fclose($handle);
            $ext = File::getFileExt(mime_content_type($fileName));
            //修改后缀
            rename($fileName, $fileName. $ext);
        }
    }
    
};
// 运行所有服务
Worker::runAll();
