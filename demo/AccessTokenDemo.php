<?php

include "../vendor/autoload.php";

use lyz\wechat\Contracts\BasicWeChat;

// 测试
// $demo = new AccessTokenDemo();
// $demo->index();

class AccessTokenDemo
{
    public $config;

    public function __construct()
    {
        echo '===== set config =====' . PHP_EOL;
        // 配置参数
        $config = include "./config.php";
        $this->config = $config['wechat'];
    }

    /**
     * 测试token
     *
     * @return void
     */
    function index()
    {
        echo '===== start =====' . PHP_EOL;
        try {
            // 通过注释下一行代码，控制是否有代替函数
            // $this->setAccessTokenCallback();

            $token = $this->getAccessToken();
            echo $token . PHP_EOL;
        } catch (Exception $e) {
            // 出错啦，处理下吧
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 公共获取 AccessToken
     * @return string
     */
    function getAccessToken()
    {
        // 获取存储的 token
        // 验证是否过期
        if (false) {
            // 没过期返回token
            return 'AccessToken';
        } else {
            // 过期重新获取token  \lyz\wechat\Contracts\BasicWeChat->getAccessToken(false);
            // 更新本地存储的 token 
            return (new BasicWeChat($this->config))->getAccessToken(false);
        }
    }

    /**
     * AccessToken 代替函数
     *
     * @return string
     */
    public function getAccessTokenCallback(BasicWeChat $wechat)
    {
        // 获取存储的 token
        // 验证是否过期
        if (false) {
            // 没过期返回token
            return 'AccessToken';
        } else {
            // 过期重新获取token  \lyz\wechat\Contracts\BasicWeChat->getAccessToken(false);
            // 更新本地存储的 token 
            return $wechat->getAccessToken(false);
        }
    }

    public function setAccessTokenCallback()
    {
        // 注册代替函数
        $this->config['GetAccessTokenCallback'] = [new AccessTokenDemo, 'getAccessTokenCallback'];
    }
}
