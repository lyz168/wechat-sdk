<?php

namespace lyz\wechat\Contracts;

use lyz\wechat\exceptions\InvalidArgumentException;
use lyz\wechat\exceptions\InvalidResponseException;
use lyz\wechat\utils\Tools;
use lyz\wechat\utils\Curl;

/**
 * Class BasicWeChat
 * @package lyz\wechat\Contracts
 */
class BasicWeChat
{

    /**
     * 当前微信配置
     * @var DataArray
     */
    public $config;

    /**
     * 静态缓存
     * @var static
     */
    protected static $instances;

    /**
     * 注册代替函数
     * @var array [类, 函数名]如类不是实例化，则函数需要是 静态函数
     */
    protected $GetAccessTokenCallback;

    /**
     * BasicWeChat constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (empty($options['appid'])) {
            throw new InvalidArgumentException("Missing Config -- [appid]");
        }
        if (empty($options['appsecret'])) {
            throw new InvalidArgumentException("Missing Config -- [appsecret]");
        }
        if (isset($options['GetAccessTokenCallback']) && Tools::checkCallback($options['GetAccessTokenCallback'])) {
            $this->GetAccessTokenCallback = $options['GetAccessTokenCallback'];
        }

        $this->config = new DataArray($options);
    }

    /**
     * 获取 AccessToken 回调方式
     *
     * @param boolean $is_callback 是否使用代替函数
     * @return string access_token
     */
    public function getAccessToken($is_callback = true)
    {
        if ($is_callback && !empty($this->GetAccessTokenCallback) && Tools::checkCallback($this->GetAccessTokenCallback)) {
            $access_token = call_user_func_array($this->GetAccessTokenCallback, [$this]);
            if (empty($access_token)) {
                throw new InvalidResponseException("获取 ACCESS_TOKEN 失败");
            }
            return $access_token;
        }

        $accessToken = $this->_getAccessToken();
        return $accessToken['access_token'];
    }

    /**
     * 获取 AccessToken
     *
     * @return array [
     *                  'access_token' => 'ACCESS_TOKEN',
     *                  'expires_in' => '时间戳'
     *               ]
     */
    private function _getAccessToken()
    {
        /*
            GET https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
                参数	        类型	是否必须	描述
            请求：
                grant_type	                是	    获取 access_token 填写 client_credential
                appid	                    是	    第三方用户唯一凭证
                secret	                    是	    第三方用户唯一凭证密钥，即appsecret
        
            返回:
                access_token	String		获取到的凭证
                expires_in	    Int	        凭证有效时间。7200秒

            错误     {"errcode":40013,"errmsg":"invalid appid"}


            注：access_token 需要统一存储，避免冲突
            使用的时候，判断是否过期，如果过期就重新调用此方法获取，存取操作请自行完成
           

            存入本地文件
            $accessTokenJson = json_encode($accessToken);
            $f = fopen('access_token', 'w+');
            fwrite($f, $accessTokenJson);
            fclose($f);
        */

        list($appid, $secret) = [$this->config->get('appid'), $this->config->get('appsecret')];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

        $curl = new Curl();
        $accessToken = $curl->get($url);
        if (!isset($accessToken['access_token'])) {
            throw new InvalidResponseException("获取 ACCESS_TOKEN 失败");
        }

        return $accessToken;
    }


    /**
     * 静态创建对象
     * @param array $config
     * @return static
     */
    public static function instance(array $config)
    {
        $key = md5(get_called_class() . serialize($config));
        if (isset(self::$instances[$key])) return self::$instances[$key];
        return self::$instances[$key] = new static($config);
    }
}
