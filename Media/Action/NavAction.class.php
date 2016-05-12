<?php
/**
 * 海报
 */
namespace Media\Action;

use Common\Service\WechatService as Wechat;

class NavAction extends CommonAction
{

    public function CreateNavOp()
    {
        $result = $this->_NavJson();
        print_r((new Wechat)->createMenu($result));
    }

    private function _NavJson()
    {
        $json = array(
            "button" => array(
                array(
                    "type" => "view",
                    "name" => "全部商品",
                    "url"  => "http://test.mediaquan.com/"
                ),
                array(
                    "type" => "view",
                    "name" => "会员中心",
                    "url"  => "http://test.mediaquan.com/"
                ),
                array(
                    "name" => "服务中心",
                    "sub_button" => array(
                        array(
                            "type" => "view",
                            "name" => "新手指南",
                            "url"  => "http://test.mediaquan.com/"
                        ),
                        array(
                            "type" => "click",
                            "name" => "获取推广二维码",
                            "url"  => "WECHAT_QRCODE"
                        ),
                        array(
                            "type" => "view",
                            "name" => "售后服务",
                            "url"  => "http://test.mediaquan.com/"
                        ),
                        array(
                            "type" => "click",
                            "name" => "test_获取推广二维码",
                            "url"  => "http://test.mediaquan.com/poster/getPoster"
                        ),
                    )
                )
            )
        );
        return $json;
    }
}