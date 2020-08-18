<?php

return [
    'tcp' => 'tcp://0.0.0.0:17000', //udp://0.0.0.0:17000
    'tcp_ip' => '127.0.0.1',
    'tcp_port' => '17000',
    'api' => [
        'socket_name' => 'http://0.0.0.0:1080', //http端口
        'context_option' => [],
        'ssl' => false,
    ],
    'keep_alive_timeout' => 60, //心跳时间
];