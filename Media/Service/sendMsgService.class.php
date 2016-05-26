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
        $name = ($userInfo['user_truename']) ? $userInfo['user_truename'] : $userInfo['user_nickname'];
        $parentInfo = D('User', 'Service')->getUserBaseInfo($userInfo['parent_id']);

        if(! empty($parentInfo)) {
            $parentName = ($parentInfo['user_truename']) ? $parentInfo['user_truename'] : $parentInfo['user_nickname'];
            $userImg = C('UPLOADS_SITE_URL') . "/avatar/" . $parentInfo['user_avatar'];
        } else {
            $parentName = $name;
            $userImg = C('UPLOADS_SITE_URL') . "/avatar/" . $userInfo['user_avatar'];
        }

        $data = [
            'touser' => $userInfo['user_wechatopenid'],
            'msgtype' => 'news',
            'news'  => [
                'articles' => [
                    [
                        "title"=>"欢迎".$name."光临拇指微课",
                        "description"=>"欢迎".$name."光临拇指微课",
                        "url"=> C('MEDIA_SITE_URL') . '/class/1.html',
                        "picurl"=> $url . "/image/k2.jpg"
                    ],
                    [
                        "title"=>"新手指南",
                        "description"=>"新手指南",
                        "url"=> C('MEDIA_SITE_URL') . "/manual.html",
                        "picurl"=> $url . "/image/xs.jpg"
                    ],
                    [
                        "title"=>"微信运营理论与实操课程",
                        "description"=>"微信运营与实操课程",
                        "url"=> C('MEDIA_SITE_URL') . "/class/5.html",
                        "picurl"=> $url . "/image/k5.jpg"
                    ],
                    [
                        "title"=>"去逛逛\"".$parentName."\"家的微店",
                        "description"=>"去逛逛\"".$parentName."\"家的微店",
                        "url"=> C('MEDIA_SITE_URL'),
                        "picurl"=>$userImg 
                    ],
                ]
            ]

        ];

        $wechat = new Wechat;
        $wechat->sendCustomMessage($data);
    }
}
