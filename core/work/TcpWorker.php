<?php

namespace core\work;

use Workerman\Worker;
use Workerman\Protocols\Http\Response;
use core\common\Util;
use Workerman\Lib\Timer;

/**
 * Description of TcpWorker
 * 给udp客户端发送消息
 * @author phpyii
 */
class TcpWorker extends Worker {

    /**
     * Name of the worker processes.
     *
     * @var string
     */
    public $name = 'TcpWorker';

    /**
     * reloadable.
     *
     * @var bool
     */
    public $reloadable = false;

    /**
     * 心跳时间
     *
     * @var int
     */
    public $keepAliveTimeout = 60;
    
    
    /**
     * api配置
     *
     * @var string
     */
    public $apiConfig = [
        'socket_name' => 'http://0.0.0.0:1080',
        'context_option' => [],
        'ssl' => false,
    ];

    /**
     * 所有的客户端链接
     * @var array
     */
    protected $_clients = [];
    
    /**
     * 所有的客户端信息
     * @var array
     */
    protected $_clientData = [];
    
    /**
     * 构造函数
     *
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name, $context_option = []) {
        parent::__construct($socket_name, $context_option);
        $this->onConnect = [$this, 'onClientConnect'];
        $this->onClose   = [$this, 'onClientClose'];
        $this->onMessage = [$this, 'onClientMessage'];
        $this->onWorkerStart = [$this, 'onStart'];
    }

    /**
     * 进程启动后初始化事件分发器客户端
     * @return void
     */
    public function onStart($worker) {
        $apiWorker = new Worker($this->apiConfig['socket_name'], $this->apiConfig['context_option']);
        if ($this->apiConfig['ssl']) {
            $apiWorker->transport = 'ssl';
        }
        $apiWorker->onMessage = [$this, 'onApiClientMessage'];
        $apiWorker->listen();
        Timer::add($this->keepAliveTimeout/2, [$this, 'checkHeartbeat']);
    }

    /**
     * 客户端连接后
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onClientConnect($connection) {
        $connection->clientNotSendPingCount = 0;
        Util::echoText('onClientConnect 来自'. $connection->getRemoteIp());
        //var_dump($connection);
    }
    
    /**
     * 客户端关闭链接时
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onClientClose($connection)
    {
        Util::echoText('onClientClose 来自'. $connection->getRemoteIp());
        if (!isset($connection->client_id)) {
            return;
        }
        Util::echoText('关闭客户端 '. $connection->client_id);
    }
    
    
    /**
     * 检查心跳，将心跳超时的客户端关闭
     *
     * @return void
     */
    public function checkHeartbeat()
    {
        Util::echoText('心跳检测');
        $closeClients = [];
        foreach ($this->_clients as $k => $connection) {
            if ($connection->clientNotSendPingCount > 1) {
                $connection->destroy();
                $closeClients[] = $k;
                Util::echoText('关闭客户端 ip:'. $connection->getRemoteIp());
            }
            $connection->clientNotSendPingCount ++;
        }
        foreach ($closeClients as $k) {
            unset($this->_clients[$k]);
        }
    }

    

    /**
     * 客户端发来消息时
     * @param $connection
     * @param string $message
     * @return void
     */
    public function onClientMessage($connection, $message) {
        // debug
        Util::echoText($message . ' 来自'. $connection->getRemoteIp());
        $data = Util::jsonDecode($message);
        if (empty($data)) {
            return;
        }
        $connection->clientNotSendPingCount = 0;
        //验证数据的签名安全
        $event = $data['event'];
        switch ($event) {
            case 'handshake':
                $connection->client_id = $data['client_id'];
                $this->_clients[$data['client_id']] = $connection;
                $this->_clientData[$data['client_id']] = ['client_id' => $data['client_id'], 'device' => $data['device']];
                $connection->send('{"event":"handshake", "timeout": ' .$this->keepAliveTimeout. ',"data":"{}"}');
                return;
            case 'ping':
                $connection->send('{"event":"pong","data":"{}"}');
                return;
        }
    }

    /**
     * 来自http消息
     * http://127.0.0.1:1080/event/add_client
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $request
     * @return type
     */
    public function onApiClientMessage($connection, $request) {
        //$connection->getRemoteIp()
        $requestData = [];
        $requestType = $request->method(); //请求类型
        if (strtoupper($requestType) == 'POST') {
            $requestData = $request->post();
            if (empty($requestData)) {
                $requestData = $request->rawBody();
            }
        }
        else{
           $requestData = $request->get(); 
        }
        $path = $request->path();
        $explode = explode('/', $path);
        $path_info = [];
        $i = 0;
        $key = '';
        foreach ($explode as $value) {
            if ($i === 0) {
                $i++;
                continue;
            }
            if ($i % 2 === 1) {
                $key = $value;
                $path_info[$key] = '';
            } else {
                $path_info[$key] = $value;
            }
            $i++;
        }
        $eventType = '';
        if (isset($path_info['event'])) {
            $eventType = 'event';
            if (empty($requestData)) {
                $response = new Response(400, [] , 'Bad Request');
                return $connection->send($response);
            }
        }
        //验证签名安全处理等省略
        switch ($eventType) {
            case 'event':
                $event = $path_info['event'];
                Util::echoText('事件：' . $event);
                switch ($event) {
                    case 'send_client':
                        $this->sendClient($requestData['client_id'], $requestData['data']);
                        break;
                    case 'send_client_all':
                        $this->sendClientAll($requestData['data'], $requestData['device'] ?? 'all');
                        break;
                }
                return $connection->send('{"status": 200,"errcode": 0,"errmsg":"", "data": "{}"}');
            default :
                $response = new Response(400, [] , 'Bad Request');
                return $connection->send($response);
        }
    }

    public function sendClient($client_id, $data) {
        if(isset($this->_clients[$client_id])){
            $tcp_connection = $this->_clients[$client_id];
            $tcp_connection->clientNotSendPingCount = 0;
            $tcp_connection->send(json_encode($data));
        }
    }

    /**
     * 发送给所有客户端
     * @param array $data
     * @param string $device
     */
    public function sendClientAll($data, $device = 'all') {
        foreach ($this->_clients as $client_id => $client) {
            if ($device == 'all' || $this->_clientData[$client_id]['device'] == $device) {
                $this->sendClient($client_id, $data);
            }
        }
    }

}
