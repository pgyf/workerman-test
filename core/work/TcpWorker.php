<?php

namespace core\work;

use Workerman\Worker;
use core\common\Util;
use Workerman\Lib\Timer;
use Workerman\Connection\TcpConnection;
use core\common\ApiResponse;

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
     * 验证的客户端链接  客户端id和连接id的映射关系
     * uid => [ connection_id1=> [], connection_id2 => []]
     * @var array
     */
    protected $_uids = [];

    /**
     * 客户端信息
     * @var array
     */
    protected $_uidData = [];

    /**
     * 所有连接id和客户端id的映射关系
     * connection_id => ['uid' => '', 'not_ping_count' => 0]
     * @var array
     */
    protected $_connIdToUids = [];

    /**
     * 构造函数
     *
     * @param string $socket_name
     * @param array $context_option
     */
    public function __construct($socket_name, $context_option = []) {
        parent::__construct($socket_name, $context_option);
        $this->onConnect = [$this, 'onClientConnect'];
        $this->onClose = [$this, 'onClientClose'];
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
        Timer::add($this->keepAliveTimeout / 2, [$this, 'checkHeartbeat']);
    }

    /**
     * 客户端连接后
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onClientConnect($connection) {
        Util::echoText('onClientConnect' . ' id:' . $connection->id . ' ip:' . $connection->getRemoteIp());
        $this->_connIdToClientIds[$connection->id] = ['uid' => '', 'not_ping_count' => 0];
    }

    /**
     * 客户端关闭链接时
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function onClientClose($connection) {
        Util::echoText('onClientClose' . ' id:' . $connection->id . ' ip:' . $connection->getRemoteIp());
        $uid = '';
        if(isset($connection->uid)){
            $uid = $connection->uid;
        }
        $this->clearConnection($connection->id, $uid);
    }

    /**
     * 客户端发来消息时
     * @param $connection
     * @param string $message
     * @return void
     */
    public function onClientMessage($connection, $message) {
        // debug
        Util::echoText($message . ' id:' . $connection->id . ' ip:' . $connection->getRemoteIp());
        $data = Util::jsonDecode($message);
        if (empty($data) || empty($data['event'])) {
            $connection->send(ApiResponse::resError(['errmsg' => 'forbidden', 'statusCode' => 403]));
            return;
        }
        //验证数据的签名安全（自己实现）
        
        if(isset($this->_connIdToClientIds[$connection->id])){
            //将次数设置为0
            $this->_connIdToClientIds[$connection->id]['not_ping_count'] = 0;
        }
        $event = $data['event'];
        switch ($event) {
            case 'handshake':
                $connection->uid = $data['uid'];
                $this->_uids[$data['uid']][$connection->id] = ['device' => $data['device']];
                $this->_connIdToClientIds[$connection->id] = ['uid' => $data['uid'], 'not_ping_count' => 0];
                //保存一些用户信息
                //$this->_clientData[$data['uid']] = ['uid' => $data['uid'], 'device' => $data['device']];
                $connection->send(ApiResponse::resSuccess(['data' => ['event' => 'handshake', 'timeout' => $this->keepAliveTimeout]]));
                return;
            case 'ping':
                if(!isset($this->_connIdToClientIds[$connection->id])){
                    $connection->send(ApiResponse::resError(['errmsg' => 'unauthorized', 'statusCode' => 401]));
                }
                $connection->send(ApiResponse::resSuccess(['data' => ['event' => 'pong', 'timeout' => $this->keepAliveTimeout]]));
                return;
        }
    }

    /**
     * 来自http消息
     * http://127.0.0.1:1080/event/send_user
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
                    case 'send_user':
                        $this->sendUser($requestData['uid'], $requestData['data']);
                        break;
                    case 'send_user_all':
                        $this->sendUserAll($requestData['data'], $requestData['device'] ?? 'all');
                        break;
                }
                return $connection->send(ApiResponse::resSuccess());
            default :
                return $connection->send(ApiResponse::resError(['statusCode' => 400, 'errmsg' => 'bad request']));
        }
    }

    /**
     * 给客户端发送消息
     * @param string $uid  用户id
     * @param array $data     消息
     * @param string $device 设备
     */
    public function sendUser($uid, $data, $device = 'all') {
        if (isset($this->_uids[$uid])) {
            foreach ($this->_uids[$uid] as $connection_id => $item) {
                if($device == 'all' || $item['device'] == $device){
                    if (isset($this->connections[$connection_id])) {
                        //if(TcpConnection::STATUS_CLOSED != $this->connections[$connection_id]->getStatus()){
                            $this->connections[$connection_id]->send(json_encode($data));
                        //}
                    }
                    if(isset($this->_connIdToClientIds[$connection_id])){
                        //发送消息将次数设置为0
                        $this->_connIdToClientIds[$connection_id]['not_ping_count'] = 0;
                    }
                }
            }
        }
    }

    /**
     * 发送给所有用户
     * @param array $data 消息
     * @param string $device 设备
     */
    public function sendUserAll($data, $device = 'all') {
        foreach ($this->_uids as $uid => $connections) {
            $this->sendUser($uid, $data, $device);
        }
    }


    /**
     * 心跳，将心跳超时的客户端关闭
     * @return void
     */
    public function checkHeartbeat() {
        Util::echoText('心跳检测');
        // 遍历当前进程所有的客户端连接
        foreach ($this->connections as $connection) {
            Util::echoText('心跳检测 ' . ' id:' . $connection->id . ' ip:' . $connection->getRemoteIp());
            $this->checkConnection($connection);
        }
    }

    /**
     * 检测连接
     * @param \Workerman\Connection\TcpConnection $connection
     */
    public function checkConnection($connection) {
        if (isset($this->_connIdToClientIds[$connection->id])) {
            if ($this->_connIdToClientIds[$connection->id]['not_ping_count'] > 1) {
                $uid = $this->_connIdToClientIds[$connection->id]['uid'];
                $connection->destroy();
                $this->clearConnection($connection->id, $uid);
                Util::echoText('心跳检测 关闭客户端' . ' id:' . $connection->id . ' ip:' . $connection->getRemoteIp());
            }
            else{
                $this->_connIdToClientIds[$connection->id]['not_ping_count'] ++ ;
            }
        }
    }

    /**
     * 清除连接
     * @param int $connectionId
     * @param string $uid
     */
    public function clearConnection($connectionId = 0, $uid = '') {
        //清除连接和uid关系
        if ($connectionId > 0 && isset($this->_connIdToClientIds[$connectionId])) {
            if(empty($uid)){
                $uid = $this->_connIdToClientIds[$connectionId]['uid'];
            }
            unset($this->_connIdToClientIds[$connectionId]);
        }
        //清除uid和连接关系
        if (!empty($uid)) {
            if (!empty($uid) && isset($this->_uids[$uid])) {
                if(isset($this->_uids[$uid][$connectionId])){
                    unset($this->_uids[$uid][$connectionId]);
                }
            }
//            if(isset($this->_clientData[$uid])){
//                unset($this->_clientData[$uid]);
//            }
        }
    }
    
}
