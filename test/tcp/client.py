#! /usr/bin/env python
#coding=utf-8
import socket

# 创建socket对象
# 参数一 指定用ipv4版本，参数2 指定用udp协议
serverSocket = socket.socket(socket.AF_INET,socket.SOCK_STREAM)

HOST='127.0.0.1'
PORT=17000 #从指定的端口，从任何发送者，接收UDP数据
BUFSIZ=1024
ADDR=(HOST, PORT)

serverSocket.connect(ADDR)

while True:
    #提示用户输入数据
    send_data = input("请输入要发送的数据：")

    serverSocket.send(send_data.encode("utf-8"))

    # 接收对方发送过来的数据，最大接收1024个字节
    recvData = serverSocket.recv(BUFSIZ)
    print('接收到的数据为:', recvData.decode('utf-8'))