#### 实现功能

1. 通过http接口添加设备
2. 通过http接口给设备发送tcp消息

#### 运行说明

1. 配置在confg目录
2. 运行server.php 启动tcp服务和http服务
3. 运行client.php 连接tcp服务
4. 给单个设备发送数据 http://127.0.0.1:1080/event/send_client?client_id=abc&data=hi
5. 给所有ios设备发送数据 http://127.0.0.1:1080/event/send_client_all?device=ios&data=hi
6. 给所有设备发送数据 http://127.0.0.1:1080/event/send_client_all?data=hi