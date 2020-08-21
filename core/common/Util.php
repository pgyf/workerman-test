<?php

namespace core\common;

/**
 * Description of Util
 *
 * @author phpyii
 */
class Util {

    /**
     * 生成一个数组
     * @param int $start 开始
     * @param int $stop 结束
     */
    function createRange($start, $stop) {
        if ($start < $stop) {
            for ($i = $start; $i <= $stop; $i++) {
                yield $i => $i * $i;
            }
        } else {
            for ($i = $start; $i >= $stop; $i--) {
                yield $i => $i * $i; //迭代生成数组： 键=》值
            }
        }
    }

    /**
     * 返回数据
     * @param type $data
     * @return type
     */
    private static function retFmt($data, $dataType = 'json') {
        $data = array_merge(['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => []], $data);
        if ($dataType == 'json') {
            return json_encode($data, true);
        }
        return $data;
    }

    public static function retJson($data) {
        return self::retFmt($data, 'json');
    }

    public static function retArray($data) {
        return self::retFmt($data, 'array');
    }

    /**
     * 格式化参数格式化成url参数
     */
    public static function toUrlParams($values) {
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 创建签名
     * @param array $data
     * @param string $key
     * @return string
     */
    public static function makeSign($data, $key) {
        //签名步骤一：按字典序排序参数
        $values = $data['values'];
        ksort($values);
        $string = self::toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 加
     * @param float $left_operand
     * @param float $right_operand
     * @param int $scale
     * @return float
     */
    public static function calcAdd($left_operand, $right_operand, $scale = 2) {
        return bcadd($left_operand, $right_operand, $scale);
    }

    /**
     * 减
     * @param float $left_operand
     * @param float $right_operand
     * @param int $scale
     * @return float
     */
    public static function calcSub($left_operand, $right_operand, $scale = 2) {
        return bcsub($left_operand, $right_operand, $scale);
    }

    /**
     * 乘
     * @param float $left_operand
     * @param float $right_operand
     * @param int $scale
     * @return float
     */
    public static function calcMul($left_operand, $right_operand, $scale = 2) {
        return bcmul($left_operand, $right_operand, $scale);
    }

    /**
     * 除
     * @param float $left_operand
     * @param float $right_operand
     * @param int $scale
     * @return float
     */
    public static function calcDiv($left_operand, $right_operand, $scale = 2) {
        return bcdiv($left_operand, $right_operand, $scale);
    }

    /**
     * 计算比例后的数字
     * @param float $leftOperand
     * @param float $divided
     * @param int $scale
     * @return float
     */
    public static function calcDivided($leftOperand, $divided, $scale = 2) {
        if ($divided >= 1) {
            return $leftOperand;
        }
        if ($divided < 0) {
            return 0;
        }
        return self::calcMul($leftOperand, $divided, $scale);
    }

    /**
     * 转json字符串为数组
     * @param string $json
     * @param mixed $default
     * @return array
     */
    public static function jsonDecode($json, $default = []) {
        if (!empty($json)) {
            try {
                return json_decode($json, true);
            } catch (\Exception $exc) {
                //errorLog($exc->getMessage());
            }
        }
        return $default;
    }

    /**
     * 打印并换行
     * @param string $txt
     * @param string $br
     */
    public static function echoText($txt, $br = PHP_EOL) {
        echo '[' . date('Y-m-d H:i:s') . ']' . '-' . $txt . $br;
    }

    /**
     * 是否base64
     * @param type $str
     * @return type
     */
    public static function isBase64($str) {
        return $str == base64_encode(base64_decode($str)) ? true : false;
    }

}
