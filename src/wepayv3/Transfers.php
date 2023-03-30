<?php

namespace lyz\wepayv3;

use lyz\wepayv3\contracts\BasicWePay;

/**
 * 普通商户商家转账到零钱
 * Class Transfers
 * doc: https://pay.weixin.qq.com/docs/merchant/apis/batch-transfer-to-balance/transfer-batch/initiate-batch-transfer.html
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
                "batch_id": "1310004020001013828056420230", // 【微信批次单号】 微信批次单号，微信商家转账系统返回的唯一标识
                "create_time": "2023-03-30T11:20:08+08:00", // 【批次创建时间】 批次受理成功时返回，按照使用rfc3339所定义的格式，格式为YYYY-MM-DDThh:mm:ss+TIMEZONE
                "out_batch_no": "lyz01"                     // 【微信批次单号】 微信批次单号，微信商家转账系统返回的唯一标识
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
        /*
            limit:             【最大资源条数】 该次请求可返回的最大明细条数，最小20条，最大100条，不传则默认20条。不足20条按实际条数返回
            offset:            【请求资源起始位置】 默认值为0，该次请求资源的起始位置。返回的明细是按照设置的明细条数进行分页展示的，一次查询可能无法返回所有明细，我们使用该参数标识查询开始位置
            need_query_detail: 【是否查询转账明细单】 true-是；false-否，默认否。商户可选择是否查询指定状态的转账明细单，当转账批次单状态为“FINISHED”（已完成）时，才会返回满足条件的转账明细单
            detail_status:     【明细状态】 
                                    WAIT_PAY: 待确认。待商户确认, 符合免密条件时, 系统会自动扭转为转账中
                                    ALL:全部。需要同时查询转账成功和转账失败的明细单
                                    FAIL:转账失败。需要确认失败原因后，再决定是否重新发起对该笔明细单的转账（并非整个转账批次单）
                                    SUCCESS:转账成功
        */
        $params = http_build_query([
            'limit'             => $limit,
            'offset'            => $offset,
            'detail_status'     => $detailStatus,
            'need_query_detail' => $needQueryDetail ? 'true' : 'false',
        ]);
        return $this->doRequest('GET', "{$pathinfo}?{$params}", '', true);
        /* {
            transfer_batch: {
                mchid:          【商户号】 微信支付分配的商户号
                out_batch_no:   【商家批次单号】 商户系统内部的商家批次单号，在商户系统内部唯一
                batch_id:       【微信批次单号】 微信批次单号，微信商家转账系统返回的唯一标识
                appid:          【商户appid】 申请商户号的appid或商户号绑定的appid（企业号corpid即为此appid）
                batch_status:   【批次状态】 
                                    WAIT_PAY: 待付款确认。需要付款出资商户在商家助手小程序或服务商助手小程序进行付款确认
                                    ACCEPTED:已受理。批次已受理成功，若发起批量转账的30分钟后，转账批次单仍处于该状态，可能原因是商户账户余额不足等。商户可查询账户资金流水，若该笔转账批次单的扣款已经发生，则表示批次已经进入转账中，请再次查单确认
                                    PROCESSING:转账中。已开始处理批次内的转账明细单
                                    FINISHED:已完成。批次内的所有转账明细单都已处理完成
                                    CLOSED:已关闭。可查询具体的批次关闭原因确认
                batch_type:     【批次类型】 
                                    API:API方式发起
                                    WEB:页面方式发起
                batch_name:     【批次名称】 该笔批量转账的名称
                batch_remark:   【批次备注】 转账说明，UTF8编码，最多允许32个字符
                close_reason:   【批次关闭原因】 如果批次单状态为“CLOSED”（已关闭），则有关闭原因
                                    可选取值：
                                    OVERDUE_CLOSE: 系统超时关闭，可能原因账户余额不足或其他错误
                                    TRANSFER_SCENE_INVALID: 付款确认时，转账场景已不可用，系统做关单处理
                total_amount:   【转账总金额】 转账金额单位为“分”
                total_num:      【转账总笔数】 一个转账批次单最多发起三千笔转账
                create_time:    【批次创建时间】 批次受理成功时返回，按照使用rfc3339所定义的格式，格式为YYYY-MM-DDThh:mm:ss+TIMEZONE
                update_time:    【批次更新时间】 批次最近一次状态变更的时间，按照使用rfc3339所定义的格式，格式为YYYY-MM-DDThh:mm:ss+TIMEZONE
                success_amount: 【转账成功金额】 转账成功的金额，单位为“分”。当批次状态为“PROCESSING”（转账中）时，转账成功金额随时可能变化
                success_num:    【转账成功笔数】 转账成功的笔数。当批次状态为“PROCESSING”（转账中）时，转账成功笔数随时可能变化
                fail_amount:    【转账失败金额】 转账失败的金额，单位为“分”
                fail_num:       【转账失败笔数】 转账失败的笔数
                transfer_scene_id: 【转账场景ID】 指定的转账场景ID
            },
            transfer_detail_list: [
                {
                    detail_id:     【微信明细单号】 微信支付系统内部区分转账批次单下不同转账明细单的唯一标识
                    out_detail_no: 【商家明细单号】 商户系统内部区分转账批次单下不同转账明细单的唯一标识
                    detail_status: 【明细状态】
                                        INIT: 初始态。 系统转账校验中
                                        WAIT_PAY: 待确认。待商户确认, 符合免密条件时, 系统会自动扭转为转账中
                                        PROCESSING:转账中。正在处理中，转账结果尚未明确
                                        SUCCESS:转账成功
                                        FAIL:转账失败。需要确认失败原因后，再决定是否重新发起对该笔明细单的转账（并非整个转账批次单）
                }
            ]
        } */
    }

    /**
     * 通过微信明细单号查询明细单
     * @param string $batchId  微信批次单号s
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
