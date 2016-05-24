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
        $data = [
            'touser' => $userInfo['user_wechatopenid'],
            'msgtype' => 'news',
            'news'  => [
                'articles' => [
                    [
                        "title"=>"关注测试",
                        "description"=>"关注测试",
                        "url"=>"http://mp.weixin.qq.com/wiki/home/index.html",
                        "picurl"=>"http://mp.weixin.qq.com/wiki/static/assets/dc5de672083b2ec495408b00b96c9aab.png"
                    ],
                    [
                        "title"=>"关注测试",
                        "description"=>"关注测试",
                        "url"=>"http://mp.weixin.qq.com/wiki/home/index.html",
                        "picurl"=>"http://mp.weixin.qq.com/wiki/static/assets/dc5de672083b2ec495408b00b96c9aab.png"
                    ],
                    [
                        "title"=>"关注测试",
                        "description"=>"关注测试",
                        "url"=>"http://mp.weixin.qq.com/wiki/home/index.html",
                        "picurl"=>"http://mp.weixin.qq.com/wiki/static/assets/dc5de672083b2ec495408b00b96c9aab.png"
                    ],
                    [
                        "title"=>"关注测试",
                        "description"=>"关注测试",
                        "url"=>"http://mp.weixin.qq.com/wiki/home/index.html",
                        "picurl"=>"http://mp.weixin.qq.com/wiki/static/assets/dc5de672083b2ec495408b00b96c9aab.png"
                    ],
                ]
            ]

        ];

        $wechat = new Wechat;
        $wechat->sendCustomMessage($data);
    }
}