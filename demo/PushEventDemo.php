<?php

include "../vendor/autoload.php";

use lyz\wechat\Receive;

class PushEventDemo
{
    /**
     * 微信服务器配置 地址入口
     */
    public function event()
    {
        // 获取公众号信息
        $config = include "./config.php";
        $config = $config['wechat'];
        $Receive = new Receive($config);

        switch ($Receive->getMsgType()) {
            case 'event':
                // 事件
                switch (strtolower($Receive->getReceive('Event'))) {
                    case 'subscribe':
                        // 关注
                        if (!empty($Receive->getReceive('EventKey')) && !empty($Receive->getReceive('Ticket'))) {
                            // 二维码关注
                        } else {
                            // 普通关注
                        }
                        break;
                    case 'scan':
                        // 扫描二维码
                        break;
                    case 'location':
                        //地理位置
                        break;
                    case 'click':
                        //自定义菜单 - 点击菜单拉取消息时的事件推送
                        break;
                    case 'view':
                        //自定义菜单 - 点击菜单跳转链接时的事件推送
                        break;
                    case 'scancode_push':
                        //自定义菜单 - 扫码推事件的事件推送
                        break;
                    case 'scancode_waitmsg':
                        //自定义菜单 - 扫码推事件且弹出“消息接收中”提示框的事件推送
                        break;
                    case 'pic_sysphoto':
                        //自定义菜单 - 弹出系统拍照发图的事件推送
                        break;
                    case 'pic_photo_or_album':
                        //自定义菜单 - 弹出拍照或者相册发图的事件推送
                        break;
                    case 'pic_weixin':
                        //自定义菜单 - 弹出微信相册发图器的事件推送
                        break;
                    case 'location_select':
                        //自定义菜单 - 弹出地理位置选择器的事件推送
                        break;
                    case 'unsubscribe':
                        //取消关注
                        break;
                    case 'masssendjobfinish':
                        //群发接口完成后推送的结果
                        break;
                    case 'templatesendjobfinish':
                        //模板消息完成后推送的结果
                        break;
                    default:
                        // 收到了未知类型的消息
                        break;
                }
                break;

            case 'text':
                //文本
                // $Receive->text('发送的文本')->reply();
                return $Receive->text('发送的文本')->reply([], true);
                break;
            case 'image':
                //图像
                break;
            case 'voice':
                //语音
                break;
            case 'video':
                //视频
                break;
            case 'shortvideo':
                //小视频
                break;
            case 'location':
                //位置
                break;
            case 'link':
                //链接
                break;
            default:
                return $Receive->text('未知的消息')->reply([], true);
                break;
        }

        exit;
    }
}
