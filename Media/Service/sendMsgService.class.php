<?php
/**
{
    "touser":"OPENID",
    "msgtype":"news",
    "news":{
        "articles": [
            {
                "title":"Happy Day",
                "description":"Is Really A Happy Day",
                "url":"URL",
                "picurl":"PIC_URL"
            },
            {
                "title":"Happy Day",
                "description":"Is Really A Happy Day",
                "url":"URL",
                "picurl":"PIC_URL"
            }
        ]
    }
}
 */

namespace Media\Service;

use Common\Service\WechatService as Wechat;

class sendMsgService
{
    public function sendNews($userInfo)
    {
        $url = C('RESOURCE_SITE_URL');
        //$userInfo = D('User', 'Service')->getUserBaseInfo($uid);
        $data = [
            'touser' => $userInfo['user_wechatopenid'],
            'msgtype' => 'news',
            'news'  => [
                'articles' => [
                    [
                        "title"=>"测试",
                        "description"=>"建设中, 请勿购买",
                        "url"=>"http://wangjie.guanlizhihui.com",
                        "picurl"=> $url . "/image/k1.jpg"
                    ],
                    [
                        "title"=>"测试",
                        "description"=>"建设中, 请勿购买",
                        "url"=>"http://wangjie.guanlizhihui.com",
                        "picurl"=> $url . "/image/k1.jpg"
                    ],
                    [
                        "title"=>"测试",
                        "description"=>"建设中, 请勿购买",
                        "url"=>"http://wangjie.guanlizhihui.com",
                        "picurl"=> $url . "/image/k1.jpg"
                    ],
                    [
                        "title"=>"测试",
                        "description"=>"建设中, 请勿购买",
                        "url"=>"http://wangjie.guanlizhihui.com",
                        "picurl"=> $url . "/image/k1.jpg"
                    ],
                ]
            ]

        ];

        $wechat = new Wechat;
        $wechat->sendCustomMessage($data);
    }
}
