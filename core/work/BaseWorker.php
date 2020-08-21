<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace core\work;

use Workerman\Worker;
use Workerman\Protocols\Frame;
use core\common\Util;

/**
 * Description of BaseWorker
 *
 * @author phpyii
 */
abstract class BaseWorker extends Worker {

    /**
     * 分包大小 upd最大1400
     * @var int 
     */
    public $maxPackageLength = 1400;
    
    /**
     * 分包个数
     * @var int 
     */
    public $maxPackageCount = 99999;
    
    
    /**
     *丢包是否删除全部包
     * @var boolean 
     */
    public $losePackageClear = true;
    
    /**
     * 缓存包数据
     * @var type 
     */
    protected $packageDatas = [];

    /**
     * 拆包
     * 包组成 标识id 27位 - 包总数 5位 - 当前包顺序 5位 - 当前包大小 4位 - 数据
     * @param string $buffer  数据
     * @return array
     */
    public function splitPackage($buffer) {
        if (empty($buffer)) {
            return null;
        }
        if(is_array($buffer)){
            $buffer = json_encode($buffer);
        }
        if (strlen($buffer) <= $this->maxPackageLength) {
            return $this->messageEncode($buffer);
        }
        //包标识 长度37
        $pack_id = uniqid(rand(1000, 9999) . time());
        //顺序
        $wbool = true;
        $pack_next_start = 0;
        //包数据
        $pack_buffers = [];
        while ($wbool) {
            $pack_buffer = substr($buffer, $pack_next_start, $this->maxPackageLength);
            if (empty($pack_buffer)) {
                break;
            }
            $pack_buffers[] = str_pad(strlen($pack_buffer), 4, "0", STR_PAD_LEFT) . $pack_buffer;
            $pack_next_start = $pack_next_start + $this->maxPackageLength;
        }
        //分包个数大于99999
        $pack_count = count($pack_buffers);
        if ($pack_count > $this->maxPackageCount) {
            return 0;
        }
        //设置每个包的数据
        foreach ($pack_buffers as $k => &$pack_buffer) {
            $pack_buffer =  $this->messageEncode($pack_id . str_pad($pack_count, 5, "0", STR_PAD_LEFT) . str_pad(($k + 1), 5, "0", STR_PAD_LEFT) . $pack_buffer);
        }
        unset($buffer);
        return $pack_buffers;
    }

    /**
     * 组合包
     * @param type $buffer
     */
    public function mergePackage($buffer) {
        $buffer = $this->messageDecode($buffer);
        if (empty($buffer) || strlen($buffer) < 41) {
            return [
                'status' => -1,
                'msg' => '包过小',
                'package_buffer' => $buffer,
            ];
        }
        //包标识
        $pack_id = substr($buffer, 0, 27);
        if (!isset($this->packageDatas[$pack_id])) {
            $this->packageDatas[$pack_id] = [
                'time' => time(),
                'package' => [],
            ];
        }
        //包总数
        $pack_count = intval(substr($buffer, 27, 5));
        //包顺序
        $pack_order = intval(substr($buffer, 32, 5));
        //包大小
        $pack_length = intval(substr($buffer, 37, 4));
        $currentPackage = substr($buffer, 41);
        $this->packageDatas[$pack_id]['time'] = time();
        $this->packageDatas[$pack_id]['package'][$pack_order] = $currentPackage;
        $pack_current_count = count($this->packageDatas[$pack_id]['package']);
        if ($pack_length != strlen($currentPackage)) {
            //丢包了 报数据不对
            if($this->losePackageClear){
                unset($this->packageDatas[$pack_id]);
            }
            return [
                'status' => -2,
                'msg' => '丢包了',
                'pack_order' => $pack_order,
                'pack_current_count' => $pack_current_count,
                'pack_count' => $pack_count,
                'package_buffer' => $buffer,
            ];
        }
        if ($pack_current_count != $pack_count) {
            //等待包
            return [
                'status' => 0,
                'msg' => '接收中',
                'pack_current_count' => $pack_current_count,
                'pack_order' => $pack_order,
                'pack_count' => $pack_count,
            ];
        }
        //排序
        ksort($this->packageDatas[$pack_id]['package']);
        //组合
        $package_buffer = '';
        foreach ($this->packageDatas[$pack_id]['package'] as $package) {
            $package_buffer .= $package;
        }
        unset($this->packageDatas[$pack_id]);
        return [
            'status' => 1,
            'msg' => 'ok',
            'pack_order' => $pack_order,
            'pack_current_count' => $pack_current_count,
            'pack_count' => $pack_count,
            'package_buffer' => $package_buffer,
        ];
    }

    /**
     * 数据包协议
     * @param type $buffer
     * @return type
     */
    protected function messageEncode($buffer) {
        return Frame::encode($buffer);
    }
    
    /**
     * 数据包协议
     * @param type $buffer
     * @return type
     */
    protected function messageDecode($buffer) {
        return Frame::decode($buffer);
    }
    
    
    /**
     * 检测缓存数据
     * @return void
     */
    public function checkPackageDatas() {
        Util::echoText('心跳检测缓存');
        // 遍历当前进程所有的客户端连接
        $nowTime = time();
        foreach ($this->packageDatas as $k => $packageData) {
            if(($packageData['time'] + 30) < ($nowTime)){
                unset($this->packageDatas[$k]);
            }
        }
    }
    
}
