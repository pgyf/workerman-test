<?php

namespace core\work;

use Workerman\Worker;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Protocols\Http\Response;
use core\common\Util;
use core\common\ApiResponse;
use core\common\SocketResponse;

/**
 * Description of UdpWorker
 * 给udp客户端发送消息
 * @author phpyii
 */
class UdpWorker extends Worker {

    /**
     * Name of the worker processes.
     *
     * @var string
     */
    public $name = 'UdpWorker';

    /**
     * reloadable.
     *
     * @var bool
     */
    public $reloadable = false;

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
     * 所有的客户端
     *
     * @var array
     */
    protected $_clients = [];

    /**
     * 客户端端口
     *
     * @var array
     */
    public $client_port = 17000;

    /**
     * 所有的udp连接
     * @var array
     */
    protected $_udpConnections = [];

    /**
     * 构造函数
     *
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name, $context_option = []) {
        parent::__construct($socket_name, $context_option);
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
    }

    /**
     * 客户端发来消息时
     * @param $connection
     * @param string $message
     * @return void
     */
    public function onClientMessage($connection, $message) {
        // debug
        Util::echoText($message . ' 来自' . $connection->getRemoteIp());
        $data = Util::jsonDecode($data);
        if (!empty($data)) {
            return;
        }
        //验证数据的签名安全
        $event = $data['event'];
        switch ($event) {
            case ':ping':
                $connection->send(SocketResponse::resSuccess(['data' => ['event' => 'pong']]));
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
        } else {
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
                return $connection->send(ApiResponse::resError(['statusCode' => 400, 'errmsg' => 'bad request']));
            }
        }
        //验证签名安全处理等省略
        switch ($eventType) {
            case 'event':
                $event = $path_info['event'];
                Util::echoText('事件：' . $event);
                switch ($event) {
                    case 'add_client':
                        $this->_clients[$requestData['client_id']] = ['ip' => $requestData['ip'], 'device' => $requestData['device'], 'client_id' => $requestData['client_id']];
                        break;
                    case 'send_client':
                        $this->sendClient($requestData['client_id'], $requestData['data']);
                        break;
                    case 'send_client_all':
                        $this->sendClientAll($requestData['data'], $requestData['device'] ?? 'all');
                        break;
                }
                return $connection->send(ApiResponse::resSuccess());
            default :
                return $connection->send(ApiResponse::resError(['statusCode' => 400, 'errmsg' => 'bad request']));
        }
    }

    /**
     * 发送udp消息
     * @param string $addr
     * @param array $data
     */
    private function sendUdpData($addr, $data) {
        
        if(empty($addr)){
            return;
        }
        if(isset($this->_udpConnections[$addr])){
            $udpConnection = $this->_udpConnections[$addr];
        }
        else{
            $udpConnection = new AsyncUdpConnection('udp://' . $addr);
        }
        Util::echoText('发送消息：'.$addr);
//        $udpConnection->onConnect = function($udpConnection) use ($data) {
//            $udpConnection->send(SocketResponse::resSuccess(['data' => $data]));
//        };
        $udpConnection->onMessage = function($udpConnection, $message) {
            // 收到服务端返回的数据就关闭连接
            //echo "recv $message\r\n";
            Util::echoText('收到消息：' . $message);
            // 关闭连接
            //$udpConnection->close();
        };
        if(!isset($this->_udpConnections[$addr])){
            $this->_udpConnections[$addr] = $udpConnection;
        }
        $udpConnection->send(SocketResponse::resSuccess(['data' => $data]));
    }

    public function sendClient($client_id, $data) {
        if (isset($this->_clients[$client_id])) {
            $this->sendUdpData($this->_clients[$client_id]['ip'] . ':' . $this->client_port, $data);
//            $udp_connection = new AsyncUdpConnection('udp://' . $this->_clients[$client_id]['ip'] . ':' . $this->client_port);
//            $udp_connection->onConnect = function($udp_connection) use ($data) {
//                $udp_connection->send(SocketResponse::resSuccess(['data' => $data]));
//            };
//            $udp_connection->onMessage = function($udp_connection, $message) {
//                // 收到服务端返回的数据就关闭连接
//                //echo "recv $message\r\n";
//                Util::echoText('收到消息：' . $message . '响应后关闭udp连接');
//                // 关闭连接
//                $udp_connection->close();
//            };
//            $udp_connection->connect();
        }
    }

    /**
     * 发送给所有客户端
     * @param array $data
     * @param string $device
     */
    public function sendClientAll($data, $device = 'all') {
        foreach ($this->_clients as $client) {
            if ($device == 'all' || $client['device'] == $device) {
                $this->sendClient($client['client_id'], $data);
            }
        }
    }

}
