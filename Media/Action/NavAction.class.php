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
        $result = (new Wechat)->createMenu($result);
        if ($result) {
            echo 'success';
        } else {
            echo 'error';
        }
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
                    "name" => "全部课程",
                    "url"  => C('MEDIA_SITE_URL') 
                ),
                array(
                    "type" => "click",
                    "name" => "我的二维码",
                    "key"  => "WECHAT_QRCODE"
                ),
                array(
                    "name" => "服务中心",
                    "sub_button" => array(
                        array(
                            "type" => "view",
                            "name" => "个人中心",
                            "url"  => C('MEDIA_SITE_URL') . "/my/"
                        ),
                        array(
                            "type" => "click",
                            "name" => "新手指南",
                            "key"  => "WECHAT_XSZN"
                        ),
                        array(
                            "type" => "click",
                            "name" => "在线客服",
                            "key"  => "WECHAT_ZXKF"
                        ),
                        array(
                            "type" => "view",
                            "name" => "合作交流",
                            "url"  => C('MEDIA_SITE_URL') . '/coopera/'
                        )
                    )
                )
            )
        );
        return $json;
    }
}
