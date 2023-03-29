<?php

include "../vendor/autoload.php";
include "./AccessTokenDemo.php";

use lyz\wechat\Qrcode;

try {

    // 加载 config 和 AccessToken代替函数
    $token = new AccessTokenDemo();
    $token->setAccessTokenCallback();

    $qrcode = new Qrcode($token->config);
    // 获取 ticket
    $ticket_info = $qrcode->create('test');
    var_dump($ticket_info);

    // 创建二维码链接
    $url = $qrcode->getQrcodeUrl($ticket_info['ticket']);
    var_dump($url);
} catch (Exception $e) {
    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;
}
