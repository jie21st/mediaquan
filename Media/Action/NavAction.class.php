<?php
/**
 *　公众号菜单
 */
namespace Media\Action;

use Common\Service\WechatService as Wechat;

class NavAction extends CommonAction
{

    /**
     * 菜单生成
     */
    public function CreateNavOp()
    {
        $result = $this->_NavJson();
        (new Wechat)->createMenu($result);
    }

    /**
     * 删除菜单
     */
    public function DelOp()
    {
        (new Wechat)->deleteMenu();
    }

    /**
     * 菜单列表
     * @return array
     */
    private function _NavJson()
    {
        $json = array(
            "button" => array(
                array(
                    "type" => "view",
                    "name" => "全部商品",
                    "url"  => C('MEDIA_SITE_URL') 
                ),
                array(
                    "type" => "view",
                    "name" => "会员中心",
                    "url"  => C('MEDIA_SITE_URL') . "/my"
                ),
                array(
                    "name" => "服务中心",
                    "sub_button" => array(
                        array(
                            "type" => "view",
                            "name" => "新手指南",
                            "url"  => C('MEDIA_SITE_URL') . '/manual.html'
                        ),
                        array(
                            "type" => "view",
                            "name" => "售后服务",
                            "url"  => C('MEDIA_SITE_URL') . "/contact"
                        ),
                        array(
                            "type" => "click",
                            "name" => "获取推广二维码",
                            "key"  => "WECHAT_QRCODE"
                        ),
                    )
                )
            )
        );
        return $json;
    }
}
