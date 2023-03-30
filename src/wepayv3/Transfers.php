<?php

namespace lyz\wepayv3;

use lyz\wepayv3\contracts\BasicWePay;

/**
 * 普通商户商家转账到零钱
 * Class Transfers
 * @package lyz\wepayv3
 */
class Transfers extends BasicWePay
{
    /**
     * 发起商家批量转账
     * @param array $body
     * @return array
     * @throws \lyz\wepayv3\exceptions\InvalidResponseException
     */
    public function batchs($body)
    {
        /*
            {
                'appid': '',              // 【商户appid】 申请商户号的appid或商户号绑定的appid（企业号corpid即为此appid）
                'out_batch_no': 'lyz01',  // 【商家批次单号】 商户系统内部的商家批次单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一
                'batch_name': '提现',     // 【批次名称】 该笔批量转账的名称
                'batch_remark': '提现',   // 【批次备注】 转账说明，UTF8编码，最多允许32个字符
                'total_amount': 10,       // 【转账总金额】 转账金额单位为“分”。转账总金额必须与批次内所有明细转账金额之和保持一致，否则无法发起转账操作
                'total_num': 1,           // 【转账总笔数】 一个转账批次单最多发起一千笔转账。转账总笔数必须与批次内所有明细之和保持一致，否则无法发起转账操作
                'transfer_detail_list': [ // 【转账明细列表】 发起批量转账的明细列表，最多一千笔
                    [
                        'out_detail_no': 'lyz011',  // 【商家明细单号】 商户系统内部区分转账批次单下不同转账明细单的唯一标识，要求此参数只能由数字、大小写字母组成
                        'transfer_amount': 10,      // 【转账金额】 转账金额单位为“分” 最少 0.1 元
                        'transfer_remark': '提现',  // 【转账备注】 单条转账备注（微信用户会收到该备注），UTF8编码，最多允许32个字符
                        'openid': '',               // 【收款用户openid】 商户appid下，某用户的openid
                        'user_name': '',            // 【收款用户姓名】 收款方真实姓名。支持标准RSA算法和国密算法，公钥由微信侧提供
                                                    // 明细转账金额 <0.3元 时，不允许填写收款用户姓名
                                                    // 明细转账金额 >= 2,000元 时，该笔明细必须填写收款用户姓名
                                                    // 同一批次转账明细中的姓名字段传入规则需保持一致，也即全部填写、或全部不填写
                                                    // 若商户传入收款用户姓名，微信支付会校验用户openID与姓名是否一致，并提供电子回单
                    ]
                ],
                'transfer_scene_id': '1000', // 【转账场景ID】 必填，指定该笔转账使用的转账场景ID, 需要微信支付后台申请
            }
        */
        return $this->doRequest('POST', '/v3/transfer/batches', json_encode($body, JSON_UNESCAPED_UNICODE), true);
        /*
            success
            {
                "batch_id": "131000402000101382805642023033026742536517",
                "create_time": "2023-03-30T11:20:08+08:00",
                "out_batch_no": "lyz01"
            }
            error
            {
                "code": "INVALID_REQUEST",
                "message": "此IP地址不允许调用该接口\t"
            }
         */
    }

    /**
     * 通过微信批次单号查询批次单
     * @param string  $batchId         微信批次单号(二选一)
     * @param string  $outBatchNo      商家批次单号(二选一)
     * @param boolean $needQueryDetail 查询指定状态
     * @param integer $offset          请求资源的起始位置
     * @param integer $limit           最大明细条数
     * @param string  $detailStatus    查询指定状态
     * @return array
     * @throws \lyz\wepayv3\exceptions\InvalidResponseException
     */
    public function query($batchId = '', $outBatchNo = '', $needQueryDetail = true, $offset = 0, $limit = 20, $detailStatus = 'ALL')
    {
        if (empty($batchId)) {
            $pathinfo = "/v3/transfer/batches/out-batch-no/{$outBatchNo}";
        } else {
            $pathinfo = "/v3/transfer/batches/batch-id/{$batchId}";
        }
        $params = http_build_query([
            'limit'             => $limit,
            'offset'            => $offset,
            'detail_status'     => $detailStatus,
            'need_query_detail' => $needQueryDetail ? 'true' : 'false',
        ]);
        return $this->doRequest('GET', "{$pathinfo}?{$params}", '', true);
    }

    /**
     * 通过微信明细单号查询明细单
     * @param string $batchId 微信批次单号
     * @param string $detailId 微信支付系统内部区分转账批次单下不同转账明细单的唯一标识
     * @return array
     * @throws \lyz\wepayv3\exceptions\InvalidResponseException
     */
    public function detailBatchId($batchId, $detailId)
    {
        $pathinfo = "/v3/transfer/batches/batch-id/{$batchId}/details/detail-id/{$detailId}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 通过商家明细单号查询明细单
     * @param string $outBatchNo 商户系统内部的商家批次单号，在商户系统内部唯一
     * @param string $outDetailNo 商户系统内部区分转账批次单下不同转账明细单的唯一标识
     * @return array
     * @throws \lyz\wepayv3\exceptions\InvalidResponseException
     */
    public function detailOutBatchNo($outBatchNo, $outDetailNo)
    {
        $pathinfo = "/v3/transfer/batches/out-batch-no/{$outBatchNo}/details/out-detail-no/{$outDetailNo}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }
}
