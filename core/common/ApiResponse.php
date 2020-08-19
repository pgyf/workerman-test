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
 * @author lyf
 */
class ApiResponse {

    private static function resResponse($params = []) {
        $data = $params['data'] ?? [];
        $msg = $params['errmsg'] ?? '';
        $code = $params['errcode'] ?? 0;
        $statusCode = $params['statusCode'] ?? 200;
        if($statusCode != 200){
            $code = $statusCode;
        }
        $headers = $params['headers'] ?? [];
        $res = ['status' => $statusCode, 'errcode' => $code, 'errmsg' => $msg, 'data' => $data];
        $body = json_encode($res);
        return new Response($statusCode, $headers, $body);
    }

    public static function resError($params = []) {
        $data = $params['data'] ?? [];
        $msg = $params['errmsg'] ?? 'error';
        $code = $params['errcode'] ?? -1;
        $statusCode = $params['statusCode'] ?? 200;
        $headers = $params['headers'] ?? [];
        return self::resResponse($data, $msg, $code, $statusCode, $headers);
    }

    public static function resSuccess($params = []) {
        $data = $params['data'] ?? [];
        $msg = $params['errmsg'] ?? 'ok';
        $code = $params['errcode'] ?? 0;
        $statusCode = $params['statusCode'] ?? 200;
        $headers = $params['headers'] ?? [];
        return self::resResponse($data, $msg, $code, $statusCode, $headers);
    }

}
