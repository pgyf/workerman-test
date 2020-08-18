<?php

namespace core\common;

/**
 * Description of Config
 *
 * @author Administrator
 */
class Config {
    
    public static $config;

    /**
     * 获取配置参数 为空则获取所有配置
     * @param string    $name 配置参数名（支持二级配置 .号分割）
     * @return mixed
     */
    public static function get($name)
    {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            return isset(self::$config[$name]) ? self::$config[$name] : null;
        } else {
            // 二维数组设置和获取支持
            $name    = explode('.', $name);
            $name[0] = strtolower($name[0]);
            return isset(self::$config[$name[0]][$name[1]]) ? self::$config[$name[0]][$name[1]] : null;
        }
    }
    
}