<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace core\common;

use Workerman\Protocols\Http\Response;

/**
 * Description of Response
 *
 * @author phpyii
 */
class SocketResponse {

    public static function resResponse($params = []) {
        $data = $params['data'] ?? [];
        $msg = $params['errmsg'] ?? '';
        $code = $params['errcode'] ?? 0;
        $res = ['errcode' => $code, 'errmsg' => $msg, 'data' => $data];
        $body = json_encode($res);
        return $body;
    }

    public static function resError($params = []) {
        if(!isset($params['errmsg'])){
            $params['errmsg'] = 'fail';
        }
        if(!isset($params['errcode'])){
            $params['errcode'] = -1;
        }
        return self::resResponse($params);
    }

    public static function resSuccess($params = []) {
        if(!isset($params['errmsg'])){
            $params['errmsg'] = 'ok';
        }
        if(!isset($params['errcode'])){
            $params['errcode'] = 0;
        }
        return self::resResponse($params);
    }

}
