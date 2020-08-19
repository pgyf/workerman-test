#### 实现功能

1. 通过http接口给设备发送tcp消息

#### 运行说明

1. 配置在confg目录
2. 运行server.php 启动tcp服务和http服务
3. 运行client.php 或者client.py 连接tcp服务
4. 给单个用户发送数据 http://127.0.0.1:1080/event/send_user?uid=abc&data=hione
5. 给所有ios用户发送数据 http://127.0.0.1:1080/event/send_user_all?device=ios&data=hiall
6. 给所有用户发送数据 http://127.0.0.1:1080/event/send_user_all?data=hi