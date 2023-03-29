<?php

include "../vendor/autoload.php";

use lyz\wepayv3\Transfers;

$config = include "./config.php";
$config = $config['wepayv3'];

$transfers = new Transfers($config);
$data = [
    'appid' => $config['mch_appid'], // 【商户appid】 申请商户号的appid或商户号绑定的appid（企业号corpid即为此appid）
    'out_batch_no' => 'plfk202004201', // 【商家批次单号】 商户系统内部的商家批次单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一
    'batch_name' => '提现', // 【批次名称】 该笔批量转账的名称
    'batch_remark' => '用户提现', // 【批次备注】 转账说明，UTF8编码，最多允许32个字符
    'total_amount' => 10, // 【转账总金额】 转账金额单位为“分”。转账总金额必须与批次内所有明细转账金额之和保持一致，否则无法发起转账操作
    'total_num' => 1, // 【转账总笔数】 一个转账批次单最多发起一千笔转账。转账总笔数必须与批次内所有明细之和保持一致，否则无法发起转账操作
    'transfer_detail_list' => [ // 【转账明细列表】 发起批量转账的明细列表，最多一千笔
        [
            'out_detail_no' => 'plfk2020042011', // 【商家明细单号】 商户系统内部区分转账批次单下不同转账明细单的唯一标识，要求此参数只能由数字、大小写字母组成
            'transfer_amount' => 10, // 【转账金额】 转账金额单位为“分”
            'transfer_remark' => '提现', // 【转账备注】 单条转账备注（微信用户会收到该备注），UTF8编码，最多允许32个字符
            'openid' => 'oZ6ed6a8uV-Ryi_SfRkBbsN6FDQ0', // 【收款用户openid】 商户appid下，某用户的openid
            /* 
                user_name【收款用户姓名】 收款方真实姓名。支持标准RSA算法和国密算法，公钥由微信侧提供
                明细转账金额 <0.3元 时，不允许填写收款用户姓名
                明细转账金额 >= 2,000元 时，该笔明细必须填写收款用户姓名
                同一批次转账明细中的姓名字段传入规则需保持一致，也即全部填写、或全部不填写
                若商户传入收款用户姓名，微信支付会校验用户openID与姓名是否一致，并提供电子回单
            */
            // 'user_name' => '',
        ]
    ],
    'transfer_scene_id' => '1000', // 【转账场景ID】 必填，指定该笔转账使用的转账场景ID, 需要微信支付后台申请
];
$transfers->batchs($data);
