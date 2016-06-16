<?php
namespace Media\Service;

use Org\Util\Wechat;
use \Common\Model\UserModel;

/**
 * 微信推送消息处理/响应
 * 
 * @author Wang Jie <im.wjie@gmail.com>
 */
class WechatResponseService
{
    /**
     * 微信接口
     * @var type 
     */
    protected $wechat;
    
    /**
     * 用户openid
     * @var type 
     */
    protected $openid;
    
    /**
     * 用户模型类
     * @var type 
     */
    protected $userModel;

    private $userInfo = array();


    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->wechat = new Wechat();
        $this->userModel = new UserModel();
    }

    /**
     * 微信推送事件处理
     */
    public function responseHandle()
    {
        $this->wechat->valid();
        
        $openid = $this->wechat->getRev()->getRevFrom();
        $userInfo = $this->userModel->getUserInfo(['user_wechatopenid' => $openid]);
        $this->openid = $openid;
        $this->userInfo = $userInfo;
        
        $type = $this->wechat->getRev()->getRevType();
        switch($type) {
            case Wechat::MSGTYPE_EVENT:
                $this->event();
                break;
            //case Wechat::MSGTYPE_TEXT:
                //$this->wechat->text("hello")->reply();
            //    break;
            //case Wechat::MSGTYPE_IMAGE:
            //    break;
            default:
                //$this->wechat->text("help info")->reply();
                $this->wechat->transfer_customer_service()->reply();
        }
    }
    
    /**
     * 接收事件推送
     */
    private function event()
    {
        $event = $this->wechat->getRev()->getRevEvent();
        \Think\Log::write('事件推送：' . json_encode($event));
        
        if ($event['event'] == 'subscribe') {
            $this->_subscribeEvent($event['key']);
        } elseif ($event['event'] == 'unsubscribe') {
            $this->_unsubscribeEvent();
        } elseif ($event['event'] == 'SCAN') {
            $this->_scanEvent($event['key']);
        } elseif ($event['event'] == 'CLICK') {
            $this->_clickEvent($event['key']);
        }
    }
    
    /**
     * 关注事件
     */
    private function _subscribeEvent($key = '')
    {
        if (empty($this->userInfo)) {
            // 注册用户
            $userInfo = $this->registerOp();
            if (empty($userInfo)) {
                \Think\Log::write('关注注册用户失败');
                exit();
            }
            $this->userInfo = $userInfo;
        }
        
        // 推广用户处理
        if (!empty($key) && preg_match('/^qrscene_\d/', $key)) {
            $parentId = substr($key, 8);
            $this->userspread($parentId);
        } else {
            $defaultParents = C('DEFAULT_USER_PARENT');
            if (is_array($defaultParents) && !empty($defaultParents)) {
                shuffle($defaultParents);
                $parentId = end($defaultParents);
                $this->userspread($parentId);
            }
        }

        // 修改为已关注状态
        $result = $this->userModel->editUser([
            'subscribe_state' => 1,
                ], [
            'user_id' => $this->userInfo['user_id'],
        ]);

        // 回复
        $this->subscribeReply();
    }
    
    /**
     * 取消关注事件
     */
    private function _unsubscribeEvent()
    {
        if (! empty($this->userInfo)) {
            $result = $this->userModel->editUser([
                'subscribe_state' => 0,
            ], [
                'user_id' => $this->userInfo['user_id'],
            ]);
        }
    }

    /**
     * 用户已关注时的事件推送
     */
    private function _scanEvent($key)
    {
        // 通知推荐人有粉丝扫码
        $recomUserInfo = $this->userModel->getUserInfo(['user_id' => $key]);
        if (! empty($recomUserInfo)) {
            $msg = array();
            $msg['touser'] = $recomUserInfo['user_wechatopenid'];
            $msg['msgtype'] = 'text';
            $msg['text'] = ['content' => $this->userInfo['user_nickname'] . '扫描了您分享的二维码'];
            $wechatService = new \Common\Service\WechatService;
            $wechatService->sendCustomMessage($msg);

            $posterModel = new \Common\Model\PosterModel();
            $posterModel->posterUpdate(['user_id' => $recomUserInfo['user_id']], ['poster_scan_num' => ['exp', 'poster_scan_num+1']]);
        }
        // 推送图文消息
        $this->subscribeReply();
    }

    /**
     * 点击菜单拉取消息时的事件推送
     * 
     * @param type $key
     */
    private function _clickEvent($key)
    {
        switch ($key) {
            case 'WECHAT_QRCODE':
                if (C('SPREAD_POSTER_USE')) {
//                    if (C('SPERAD_POSTER_GENERATE_NEEDBUY')) {
//                        if ($this->userInfo['buy_num'] == 0) {
//                            $url = C('MEDIA_SITE_URL');
//                            $this->wechat->text('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="' . $url . '">立即点击“成为东家”</a>')->reply();
//                        } else {
//                            echo '';
//                            $posterService = new \Media\Service\CreatePosterService();
//                            $posterService->getPoster($this->userInfo['user_id']);
//                        }
//                    } else {
//                        echo '';
//                        $posterService = new \Media\Service\CreatePosterService();
//                        $posterService->getPoster($this->userInfo['user_id']);
//                    }
                    $replyContent = "拇指微课测试期加大奖励力度，通过听课证邀请粉丝、粉丝购买课程或者直接把课程推荐给朋友，均会获得奖励。\r\n\r\n"
                            . "<a href=\"".C('MEDIA_SITE_URL')."/my/poster\">点击领取专属听课证</a>";
                    $this->wechat->text($replyContent)->reply();
                } else {
                    $this->wechat->text('暂时无法获取海报')->reply();
                }
                break;
            case 'WECHAT_XSZN':
                $this->clickHelpReply();
                break;
            case 'WECHAT_ZXKF':
                $this->clickKfReply();
                break;
        }
    }

    /**
     * 注册
     * 
     */
    private function registerOp()
    {
        $userModel = new \Common\Model\UserModel;
        $wxUserInfo = $this->wechat->getUserInfo($this->openid);
        $wxUserInfo['nickname'] = remove_emoji($wxUserInfo['nickname']);
        $insertInfo = [
            'user_nickname' => $wxUserInfo['nickname'],
            'user_sex' => $wxUserInfo['sex'],
            'user_wechatinfo' => serialize($wxUserInfo),
            'user_wechatopenid' => $this->openid,
            'subscribe_state' => 1,
            'parent_id' => 0,
        ];
        $avatarName = uniqid();
        $avatarSavePath = DIR_UPLOAD . DS . ATTACH_AVATAR;
        $avatarPath = downloadFiles($wxUserInfo['headimgurl'], $avatarName, $avatarSavePath, 'jpg');
        if ($avatarPath) {
            $insertInfo['user_avatar'] = $avatarName . '.jpg';
        }
        
        $userId = $userModel->addUser($insertInfo);
        if (!$userId){
            \Think\Log::write('微信关注用户注册失败');
            exit();
        }
        $insertInfo['user_id'] = $userId;
        
        // 用户加入关注关怀提醒队列
        if (C('USER_SUBSCRIBE_CASE_TIME') > 0) {
            $cronModel = new \Common\Model\CronModel;
            $exectime = time() + C('USER_SUBSCRIBE_CASE_TIME');
            $cronModel->addCron(['exec_time' => $exectime, 'exec_id' => $userId, 'type' => 1]);
        }
        return $insertInfo;
    }
    
    /**
     * 用户推广
     * 
     * @param type $parentId
     * @return type
     */
    private function userspread($parentId = 0, $fromType = 'scan')
    {
        if (intval($this->userInfo['parent_id'])) {
            return false;
        }
        
        $userService = new \Common\Service\UserService;
        // 绑定关系
        $result = $userService->bindParent($this->userInfo['user_id'], $parentId);
        if ($result !== true) {
            \Think\Log::write('推广用户失败: '.$result['error']);
            return false;
        }
        $this->userInfo['parent_id'] = $parentId; // 后续用到

        // 二维码加粉次数
        $posterModel = new \Common\Model\PosterModel;
        $data = array();
        $data['poster_from_num'] = ['exp', 'poster_from_num+1'];
        $data['poster_scan_num'] = ['exp', 'poster_scan_num+1'];
        $posterModel->posterUpdate(['user_id' => $parentId], $data);

        // 通知
        $spreadUserAmount = C('SPERAD_SELLER_GAINS_AMOUNT');
        if (is_numeric($spreadUserAmount) && $spreadUserAmount > 0) {
            $pdService = new \Common\Service\PredepositService;
            $pd_data = array();
            $pd_data['user_id'] = $parentId;
            $pd_data['amount'] = $spreadUserAmount;
            $pd_data['name'] = '推荐用户 '.$this->userInfo['user_nickname'];
            $pdService->changePd('sale_income', $pd_data);
            
            $parentInfo = $userService->getUserInfo($parentId);
            // 收益通知
            $wechatService = new \Common\Service\WechatService;
            $wechatService->sendCustomMessage([
                'touser' => $parentInfo['user_wechatopenid'],
                'msgtype' => 'text',
                'text' => [
                    'content' => sprintf(
                                    '%s成为了您的粉丝，您获得收益%s元；推荐好友购买课程还可获得1-99元的收益，<a href="%s">点击查看</a>',
                                    $this->userInfo['user_nickname'],
                                    glzh_price_format($spreadUserAmount),
                                    C('MEDIA_SITE_URL').'/predeposit/'
                                )
                ]
            ]);
        } else {
            // 通知推荐人
            $msg = array();
            $msg['touser'] = $parentInfo['user_wechatopenid'];
            $msg['msgtype'] = 'text';
            $msg['text'] = ['content' => $this->userInfo['user_nickname'].'成为了您的粉丝'];
            $wechatService = new \Common\Service\WechatService;
            $wechatService->sendCustomMessage($msg);
        }
    }
    
    /**
     * 关注回复
     */
    private function subscribeReply()
    {
        $url = C('RESOURCE_SITE_URL');
        $name = ($this->userInfo['user_truename']) ? $this->userInfo['user_truename'] : $this->userInfo['user_nickname'];
        
        $parentInfo = D('User', 'Service')->getUserBaseInfo($this->userInfo['parent_id']);

        if(! empty($parentInfo)) {
            
            $parentName = ($parentInfo['user_truename']) ? $parentInfo['user_truename'] : $parentInfo['user_nickname'];
            
            $title = "Hi，".$parentName."向你推荐了实用的课程哦";
            //$userImg = getMemberAvatar($parentInfo['user_avatar']);
        } else {
            $parentName = $name;
            $title = "Hi，这里有很多实用的课程哦";
            //$userImg = getMemberAvatar($this->userInfo['user_avatar']);
        }

        $data = [
            [
                "Title"=>$title,
                "Description"=>$title,
                "Url"=> C('MEDIA_SITE_URL'),
                "PicUrl"=> $url . "/image/k2.jpg"
            ]
            ,
            /**
            [
                "Title"=>"欢迎".$name."光临拇指微课",
                "Description"=>"欢迎".$name."光临拇指微课",
                "Url"=> C('MEDIA_SITE_URL') . '/class/1.html',
                "PicUrl"=> $url . "/image/k2.jpg"
            ],
            **/
            [
                "Title"=>"实用好玩又赚钱来试试吧",
                "Description"=>"实用好玩又赚钱来试试吧",
                "Url"=> C('MEDIA_SITE_URL') . "/article",
                "PicUrl"=> $url . "/image/xs.jpg"
            ],
            [
                "Title"=>"微信营销有何难，30天让你成为微信运营高手",
                "Description"=>"微信营销有何难，30天让你成为微信运营高手",
                "Url"=> C('MEDIA_SITE_URL') . "/class/7.html",
                "PicUrl"=> $url . "/image/k5.jpg"
            ],
            [
                "Title"=>"微信朋友圈第一课:19节课, 108个案例, 150个技巧",
                "Description"=>"微信朋友圈第一课:19节课, 108个案例, 150个技巧",
                "Url"=> C('MEDIA_SITE_URL') . "/class/9.html",
                "PicUrl"=> $url . "/image/k7.jpg"
            ],
            /**
            [
                "Title"=>"去逛逛\"".$parentName."\"家的微店",
                "Description"=>"去逛逛\"".$parentName."\"家的微店",
                "Url"=> C('MEDIA_SITE_URL'),
                "PicUrl"=>$userImg 
            ]
            **/
        ];
        
        $replyContent = "Hi，欢迎来拇指微课学习！\r\n领取专属的二维码海报后，您将成为拇指微课学习代言人，可以与同学分享知识财富。\r\n\r\n"
            ."更多详情戳链接\r\n\r\n"
            ."<a href=\"".C('MEDIA_SITE_URL')."/article/1.html\">了解拇指微课模式点这里</a>\r\n\r\n"
            ."<a href=\"".C('MEDIA_SITE_URL')."/article/2.html\">如何获取专属二维码海报</a>\r\n\r\n"
            ."<a href=\"".C('MEDIA_SITE_URL')."/article/4.html\">如何推广二维码海报</a>\r\n\r\n"
            ."<a href=\"".C('MEDIA_SITE_URL')."/article/7.html\">如何直接推广课程？</a>";
        $this->wechat->text($replyContent)->reply();
    }
    
    /**
     * 点击帮助事件回复
     * 
     * @return string
     */
    private function clickHelpReply()
    {
        $arcModel = new \Common\Model\ArticleModel;
        $articleList = $arcModel->getArticleList(['article_show' => 1], 'article_id, article_title');
        $domain = C('MEDIA_SITE_URL');
        
        $str = "拇指微课测试期加大奖励力度，邀请粉丝、粉丝购买课程或者直接把课程推荐给朋友，均会获得奖励。戳链接了解详情。\r\n\r\n";
        $i = 1;
        foreach ($articleList as $article) {
            $str.= "{$i}、<a href=\"{$domain}/article/{$article['article_id']}.html\">{$article['article_title']}</a>\r\n\r\n";
            $i++;
        }
        
        $str.= "<a href=\"{$domain}/article/\">了解更多</a>";
        
        $this->wechat->text($str)->reply();
    }
    
    /**
     * 点击在线客服回复
     */
    private function clickKfReply()
    {
        $url = C('MEDIA_SITE_URL') .'/article/';
        if ((date('w') == 0 || date('w') == 6)) {
            $str = "在线客服时间为：\r\n";
            $str.= "周一至周五9:30至23:00，有什么问题可以<a href=\"{$url}\">查阅新手指南</a>";
        } elseif (date('Gi') >= 930 && date('Gi') <= 2300) {
            $str = 'hi，我是今日值班编辑小秋，有什么可以帮您的么？';
        }else {
            $str = "在线客服时间为：\r\n";
            $str.= "周一至周五9:30至23:00，有什么问题可以<a href=\"{$url}\">查阅新手指南</a>";
        }
        $this->wechat->text($str)->reply();
    }
}
