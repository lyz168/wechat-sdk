<?php

return [
    'wechat' => [
        'appid'          => '',
        'appsecret'      => '',
        'token'          => '',
        'encodingaeskey' => '',
    ],
    'wepay' => [
        'mch_id'         => '',
        'mch_key'        => '',
        'cert_public'    => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'pay' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
        'cert_private'   => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'pay' . DIRECTORY_SEPARATOR . 'apiclient_key.pem',

        'mch_appid'      => '',

    ],
    'wepayv3' => [
        'mch_id'         => '',
        'mch_v3_key'     => '',
        'cert_public'    => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'payv3' . DIRECTORY_SEPARATOR . 'apiclient_cert.pem',
        'cert_private'   => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'payv3' . DIRECTORY_SEPARATOR . 'apiclient_key.pem',

        'mch_appid'      => '',

    ],

    // 配置商户支付双向证书目录 (p12 | key,cert 二选一，两者都配置时p12优先)
    'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
    // 'ssl_key'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_key.pem',
    // 'ssl_cer'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.pem',
];
