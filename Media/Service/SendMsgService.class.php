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

class SendMsgService
{

    /**

      关注图文消息推送
    **/
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


/**{
    "touser":"OPENID",
    "msgtype":"text",
    "text":
    {
         "content":"Hello World"
    }
} **/

    public function sendXszn()
    {
        $domain = C('MEDIA_SITE_URL');
        return <<<EOF
请点击以下链接，了解详情

1、<a href="$domain/help/1.html">模式说明</a>
        
2、<a href="$domain/help/2.html">如何获取推广二维码海报？</a>
        
3、<a href="$domain/help/3.html">如何将海报分享给朋友？</a>
        
4、<a href="$domain/help/4.html">如何购买课程和听课？</a>
        
5、<a href="$domain/help/5.html">如何直接推广课程？</a>
        
6、<a href="$domain/help/6.html">如何查询账单明细？如何提现？如何查询零钱明细？</a>
        
7、<a href="$domain/help/7.html">如何开通微信支付？</a>
EOF;
    }
}
