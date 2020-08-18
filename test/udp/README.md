#### 实现功能

1. 通过http接口添加设备
2. 通过http接口给设备发送udp消息

#### 运行说明

1. 配置在confg目录
2. 运行server.php
3. 运行client.php 监听udp端口 查看日志输出
4. 添加一个ios设备 http://127.0.0.1:1080/event/add_client?client_id=abc&ip=127.0.0.1&device=ios
5. 给单个设备发送数据 http://127.0.0.1:1080/event/send_client?client_id=abc&data=hi
6. 给所有ios设备发送数据 http://127.0.0.1:1080/event/send_client_all?device=ios&data=hi
7. 给所有设备发送数据 http://127.0.0.1:1080/event/send_client_all?data=hi