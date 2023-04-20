<?php

namespace lyz\applet;

use lyz\wechat\exceptions\InvalidResponseException;
use lyz\wechat\exceptions\InvalidDecryptException;
use lyz\wechat\contracts\BasicWeChat;
use lyz\applet\crypt\WxBizDataCrypt;
use lyz\wechat\utils\Curl;

/**
 * 用户信息 数据加密处理
 * Class Crypt
 * @package lyz\applet
 */
class InfoCrypt extends BasicWeChat
{
    /**
     * 通过授权码换取手机号
     * @param string $code
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPhoneNumber($code)
    {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . $access_token;
        $curl = new Curl();
        return $curl->post($url, ['code' => $code]);
        /*
            {
                "errcode": 0,                    // number 错误码 [-1, 40029]
                "errmsg": "ok",                  // string 错误信息 [system error(系统繁忙，此时请开发者稍候再试), code 无效(js_code无效)]
                "phone_info": {                  // object 用户手机号信息
                    "phoneNumber": "xxxxxx",     // string 用户绑定的手机号（国外手机号会有区号）
                    "purePhoneNumber": "xxxxxx", // string 没有区号的手机号
                    "countryCode": 86,           // string 区号
                    "watermark": {               // object 数据水印
                        "timestamp": 1637744274, // number 用户获取手机号操作的时间戳
                        "appid": "xxxx"          // string 小程序 appid
                    }
                }
            } 
         */
    }

    /**
     * 数据签名校验
     * @param string $iv
     * @param string $sessionKey
     * @param string $encryptedData
     * @return bool|array
     */
    public function decode($iv, $sessionKey, $encryptedData)
    {
        $pc = new WXBizDataCrypt($this->config->get('appid'), $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return json_decode($data, true);
        }
        return false;
    }

    /**
     * 登录凭证校验
     * @param string $code 登录时获取的 code
     * @return array
     */
    public function code2Session($code)
    {
        $appid = $this->config->get('appid');
        $secret = $this->config->get('appsecret');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        $curl = new Curl();
        return $curl->get($url);
    }

    /**
     * 换取用户信息
     * @param string $code 用户登录凭证（有效期五分钟）
     * @param string $iv 加密算法的初始向量
     * @param string $encryptedData 加密数据( encryptedData )
     * @return array
     * @throws \lyz\wechat\exceptions\InvalidDecryptException
     * @throws \lyz\wechat\exceptions\InvalidResponseException
     */
    public function userInfo($code, $iv, $encryptedData)
    {
        $result = $this->code2Session($code);
        if (empty($result['session_key'])) {
            throw new InvalidResponseException('Code 换取 SessionKey 失败', 403);
        }
        $userinfo = $this->decode($iv, $result['session_key'], $encryptedData);
        if (empty($userinfo)) {
            throw new InvalidDecryptException('用户信息解析失败', 403);
        }
        return array_merge($result, $userinfo);
    }
}
