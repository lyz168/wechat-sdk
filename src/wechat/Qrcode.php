<?php

namespace lyz\wechat;

use lyz\wechat\Contracts\BasicWeChat;
use lyz\wechat\utils\Curl;

/**
 * 二维码管理
 * Class Qrcode
 * @package lyz\wechat
 */
class Qrcode extends BasicWeChat
{
    /**
     * 创建二维码 ticket
     * 
     * 获取带参数的二维码的过程包括两步，首先创建二维码ticket，然后凭借ticket到指定URL换取二维码。
     * 
     * 目前有2种类型的二维码，分别是临时二维码和永久二维码，
     *
     * @param string|int $scene             二维码场景类型
     * @param integer    $expire_seconds    二维码有效时间  默认 600秒 最大 2592000秒(30天)
     * 
     * @return array[
     *                  "ticket" => "gQE88DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyUDA2UHdpRWVlU0MxZktrR2h6MWwAAgSW0ipjAwRYAgAA",
     *                  "expire_seconds" => 600,
     *                  "url" => "http://weixin.qq.com/q/02P06PwiEeeSC1fKkGhz1l"
     *              ]
     */
    public function create($scene, $expire_seconds = 600)
    {
        /*
            POST https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=TOKEN
                    参数            	描述
            请求：
                expire_seconds	    该二维码有效时间，以秒为单位。 最大不超过2592000（即30天），此字段如果不填，则默认有效期为60秒。
                action_name	        二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
                action_info	        二维码详细信息
                scene_id	        场景值ID，临时二维码时为32位非0整型，永久二维码时最大值为100000（目前参数只支持1--100000）
                scene_str	        场景值ID（字符串形式的ID），字符串类型，长度限制为1到64
        
            返回:
                ticket  	        获取的二维码ticket，凭借此 ticket 可以在有效时间内换取二维码。
                expire_seconds	    该二维码有效时间，以秒为单位。 最大不超过2592000（即30天）。
                url                 二维码图片解析后的地址，开发者可根据该地址自行生成需要的二维码图片
                
                例：$ticketInfo = [
                    "ticket" => "gQE88DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyUDA2UHdpRWVlU0MxZktrR2h6MWwAAgSW0ipjAwRYAgAA",
                    "expire_seconds" => 600,
                    "url" => "http://weixin.qq.com/q/02P06PwiEeeSC1fKkGhz1l"
                ];

            错误   如 ticket 非法  返回 HTTP 错误码404。
        */
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $this->getAccessToken();

        // 二维码场景类型
        if (is_integer($scene)) {
            $data = ['action_info' => ['scene' => ['scene_id' => $scene]]];
        } else {
            $data = ['action_info' => ['scene' => ['scene_str' => $scene]]];
        }

        if ($expire_seconds > 0) {
            // 临时二维码
            $data['expire_seconds'] = $expire_seconds;
            $data['action_name'] = is_integer($scene) ? 'QR_SCENE' : 'QR_STR_SCENE';
        } else {
            // 永久二维码
            $data['action_name'] = is_integer($scene) ? 'QR_LIMIT_SCENE' : 'QR_LIMIT_STR_SCENE';
        }

        $curl = new Curl();
        return $curl->post($url, $data);
    }

    /**
     * 通过ticket换取二维码
     * @param string $ticket 获取的二维码ticket，凭借此ticket可以在有效时间内换取二维码。
     * @param string $filename String 文件路径，如果不为空，则会创建一个图片文件，二维码文件为jpg格式，保存到指定的路径
     * 
     * @return [直接echo本函数的返回值，并在调用页面添加header('Content-type: image/jpg');，将会展示出一个二维码的图片。]
     */
    public function getQrcode($ticket, $filename = '')
    {
        $queryUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);

        $curl = new Curl();
        $img = $curl->get($queryUrl);
        if (!empty($filename)) {
            file_put_contents($filename, $img);
        }

        return $img;
    }

    /**
     * 获取二维码 url
     *
     * @param string $ticket
     * 
     * @return string
     */
    public function getQrcodeUrl($ticket)
    {
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
    }

    /**
     * 将一条长链接转成短链接。
     * 主要使用场景：开发者用于生成二维码的原链接（商品、支付二维码等）太长导致扫码速度和成功率下降，将原长链接通过此接口转成短链接再生成二维码将大大提升扫码速度和成功率。
     * @param $longUrl String 需要转换的长链接，支持http://、https://、weixin://wxpay 格式的url
     * 
     * @return array ('errcode'=>0, 'errmsg'=>'错误信息', 'short_url'=>'http://t.cn/asdasd')错误码为0表示正常
     */
    public function shortUrl($longUrl)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token=' . $this->getAccessToken();

        $curl = new Curl();
        return $curl->post($url, ['action' => 'long2short', 'long_url' => $longUrl]);
    }
}
