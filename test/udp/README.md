#### 实现功能

1. 客户端开启一个udp监听服务 服务端提供一个http接口给客户端发送udp消息的作用
2. 通过http接口添加设备
3. 通过http接口给设备发送udp消息

#### 运行说明

1. 配置在confg目录 包括测试文件路径配置
2. 运行server.php 启动任务和http服务
3. 运行client.php或者client.py 监听udp端口 查看日志输出
4. 添加一个ios设备 http://127.0.0.1:1080/event/add_client?client_id=abc&ip=127.0.0.1&device=ios
5. 给单个设备发送数据 http://127.0.0.1:1080/event/send_client?client_id=abc&data=hione
6. 给所有ios设备发送数据 http://127.0.0.1:1080/event/send_client_all?device=ios&data=hiall
7. 给所有设备发送数据 http://127.0.0.1:1080/event/send_client_all?data=hi
8. 给所有设备发送文件 http://127.0.0.1:1080/event/send_file?data=hi 测试了发送本地70MB的文件 分为77496个包 用时3分钟20秒