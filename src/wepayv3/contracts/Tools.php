<?php

namespace lyz\wepayv3\contracts;

/**
 * Class Tools
 * @package lyz\wepayv3\contracts
 */
class Tools
{
    /**
     * 缓存路径
     * @var string
     */
    public static $cache_path = null;

    /**
     * 缓存写入操作
     * @var array
     */
    public static $cache_callable = [
        'set' => null, // 写入缓存
        'get' => null, // 获取缓存
        'del' => null, // 删除缓存
    ];

    public static function setCachePath($path)
    {
        self::$cache_path = $path;
    }

    /**
     * 缓存配置与存储
     * @param string $name    缓存名称
     * @param string $value   缓存内容
     * @param int    $expired 缓存时间(0表示永久缓存)
     * 
     * @return string 路径
     * @throws \lyz\wepayv3\exceptions\LocalCacheException
     */
    public static function setCache($name, $value = '', $expired = 3600)
    {
        if (self::checkCallback(self::$cache_callable['set'])) {
            return call_user_func_array(self::$cache_callable['set'], func_get_args());
        }
        $file = self::_getCacheName($name);
        $data = [
            'name' => $name,
            'value' => $value,
            'expired' => $expired === 0 ? 0 : time() + intval($expired)
        ];
        if (!file_put_contents($file, serialize($data))) {
            throw new \Exception('local cache error.', '0');
        }
        return $file;
    }

    /**
     * 获取缓存内容
     * @param string $name 缓存名称
     * @return null|mixed
     */
    public static function getCache($name)
    {
        if (self::checkCallback(self::$cache_callable['get'])) {
            return call_user_func_array(self::$cache_callable['get'], func_get_args());
        }
        $file = self::_getCacheName($name);
        if (file_exists($file) && is_file($file) && ($content = file_get_contents($file))) {
            $data = unserialize($content);
            if (isset($data['expired']) && (intval($data['expired']) === 0 || intval($data['expired']) >= time())) {
                return $data['value'];
            }
            self::delCache($name);
        }
        return null;
    }

    /**
     * 移除缓存文件
     * @param string $name 缓存名称
     * @return boolean
     */
    public static function delCache($name)
    {
        if (self::checkCallback(self::$cache_callable['del'])) {
            return call_user_func_array(self::$cache_callable['del'], func_get_args());
        }
        $file = self::_getCacheName($name);
        return !file_exists($file) || @unlink($file);
    }

    /**
     * 应用缓存目录
     * @param string $name
     * @return string
     */
    private static function _getCacheName($name)
    {
        if (empty(self::$cache_path)) {
            self::$cache_path = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        }
        self::$cache_path = rtrim(self::$cache_path, '/\\') . DIRECTORY_SEPARATOR;
        file_exists(self::$cache_path) || mkdir(self::$cache_path, 0755, true);
        return self::$cache_path . $name;
    }

    /**
     * 检查回调函数是否有用
     * @param string|array $callback
     * string: 函数名
     * array: [类, 函数名] 如类不是实例化，则函数需要是 静态函数
     * 
     * @return boolean
     */
    public static function checkCallback($callback)
    {
        if (
            (is_string($callback) && is_callable($callback, false, $callable_name)) ||
            (is_array($callback) && is_callable($callback, true, $callable_name))
        ) return true;

        return false;
    }
}
