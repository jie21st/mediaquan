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
            case Wechat::MSGTYPE_TEXT:
                //$this->wechat->text("hello")->reply();
                exit;
                break;
            case Wechat::MSGTYPE_EVENT:
                $this->event();
                break;
            case Wechat::MSGTYPE_IMAGE:
                break;
            default:
                $this->wechat->text("help info")->reply();
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
            $this->userspread($parentId, 'scan');
        } else {
            $defaultParents = C('DEFAULT_USER_PARENT');
            if (is_array($defaultParents) && !empty($defaultParents)) {
                shuffle($defaultParents);
                $parentId = end($defaultParents);
                $this->userspread($parentId, 'assign');
            }
        }

        // 修改为已关注状态
        $result = $this->userModel->editUser([
            'subscribe_state' => 1,
                ], [
            'user_id' => $this->userInfo['user_id'],
        ]);

        // 关注推送消息
        $this->sendNews();
        
        $this->wechat->text("服务号建设中，请不要购买支付任何商品")->reply();
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
        $this->sendNews();
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
                    if (C('SPERAD_POSTER_GENERATE_NEEDBUY')) {
                        if ($this->userInfo['buy_num'] == 0) {
                            $url = C('MEDIA_SITE_URL');
                            $this->wechat->text('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="' . $url . '">立即点击“成为东家”</a>')->reply();
                        } else {
                            echo '';
                            $posterService = new \Media\Service\CreatePosterService();
                            $posterService->getPoster($this->userInfo['user_id']);
                        }
                    } else {
                        echo '';
                        $posterService = new \Media\Service\CreatePosterService();
                        $posterService->getPoster($this->userInfo['user_id']);
                    }
                } else {
                    $this->wechat->text('暂时无法获取海报')->reply();
                }
                break;
            case 'WECHAT_XSZN':
                $str = $this->sendXszn();
                $this->wechat->text($str)->reply();
                break;
            case 'WECHAT_ZXKF':
                $url = C('MEDIA_SITE_URL') .'/article/';
                if ((date('w') == 0 || date('w') == 6)) {
                    $str = "在线客服时间为：\r\n";
                    $str.= "周一至周五9:30至17:30，有什么问题可以<a href=\"{$url}\">查阅新手指南</a>";
                } elseif (date('Gi') >= 930 && date('Gi') <= 1730) {
                    $str = 'hi，我是今日值班编辑小秋，有什么可以帮您的么？';
                }else {
                    $str = "在线客服时间为：\r\n";
                    $str.= "周一至周五9:30至17:30，有什么问题可以<a href=\"{$url}\">查阅新手指南</a>";
                }
                $this->wechat->text($str)->reply();
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
    private function userspread($parentId = 0, $fromType = 'scan'){
        if (intval($this->userInfo['parent_id'])) {
            \Think\Log::write('该用户已存在推荐人');
            return false;
        }
            
        if ($parentId == 0) {
            \Think\Log::write('parent_id为0');
            return false;
        }
        
        if ($this->userInfo['user_id'] == $parentId) {
            \Think\Log::write('parent_id为当前用户自己');
            return false;
        }
        
        if (in_array($this->userInfo['user_id'], C('DEFAULT_USER_PARENT'))) {
            \Think\Log::write('parent_id存在于用户默认parent中');
            return false;
        }
            
        try {
            $userModel = new \Common\Model\UserModel;

            $recomUserInfo = $userModel->getUserInfo(['user_id' => $parentId]);
            if (empty($recomUserInfo)) {
                throw new \Exception('推荐人用户信息不存在');
            }

            if ($this->userInfo['user_id'] == $recomUserInfo['parent_id']) {
                throw new \Exception('该用户是当前推荐人的推荐人');
            }

            // 绑定关系
            $update = array();
            $update['parent_id'] = $recomUserInfo['user_id'];
            $result = $userModel->editUser($update, ['user_id' => $this->userInfo['user_id']]);
            if (! $result) {
                throw new \Exception('绑定写入失败');
            }
            $this->userInfo['parent_id'] = $recomUserInfo['user_id']; // 后续用到
            
            // 二维码加粉次数
            if ($fromType == 'scan') {
                $posterModel = new \Common\Model\PosterModel;
                $data = array();
                $data['poster_from_num'] = ['exp', 'poster_from_num+1'];
                $data['poster_scan_num'] = ['exp', 'poster_scan_num+1'];
                $posterModel->posterUpdate(['user_id' => $recomUserInfo['user_id']], $data);
            }
            // 通知
            $spreadUserAmount = C('SPERAD_SELLER_GAINS_AMOUNT');
            if (is_numeric($spreadUserAmount) && $spreadUserAmount > 0) {
                $pdService = new \Common\Service\PredepositService;
                $pd_data = array();
                $pd_data['user_id'] = $recomUserInfo['user_id'];
                $pd_data['amount'] = $spreadUserAmount;
                $pd_data['name'] = '推荐用户 '.$this->userInfo['user_nickname'];
                $pdService->changePd('sale_income', $pd_data);

                // 收益通知
                $wechatService = new \Common\Service\WechatService;
                $wechatService->sendCustomMessage([
                    'touser' => $recomUserInfo['user_wechatopenid'],
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
                $msg['touser'] = $recomUserInfo['user_wechatopenid'];
                $msg['msgtype'] = 'text';
                $msg['text'] = ['content' => $this->userInfo['user_nickname'].'成为了您的粉丝'];
                $wechatService = new \Common\Service\WechatService;
                $wechatService->sendCustomMessage($msg);
            }
        } catch (\Exception $e) {
            \Think\Log::write('推广用户失败: '.$e->getMessage());
        }
    }
    
    /**
     * 关注图文消息推送
     */
    private function sendNews()
    {
        $url = C('RESOURCE_SITE_URL');
        $name = ($this->userInfo['user_truename']) ? $this->userInfo['user_truename'] : $this->userInfo['user_nickname'];
        
        $parentInfo = D('User', 'Service')->getUserBaseInfo($this->userInfo['parent_id']);
        if(! empty($parentInfo)) {
            $parentName = ($parentInfo['user_truename']) ? $parentInfo['user_truename'] : $parentInfo['user_nickname'];
            $userImg = getMemberAvatar($parentInfo['user_avatar']);
        } else {
            $parentName = $name;
            $userImg = getMemberAvatar($this->userInfo['user_avatar']);
        }

        $data = [
            'touser' => $this->userInfo['user_wechatopenid'],
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
                        "url"=> C('MEDIA_SITE_URL') . "/article/",
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

        $wechatService = new \Common\Service\WechatService();
        $wechatService->sendCustomMessage($data);
    }
    
    /**
     * 帮助消息回复
     * 
     * @return string
     */
    private function sendXszn()
    {
        $arcModel = new \Common\Model\ArticleModel;
        $articleList = $arcModel->getArticleList(['article_show' => 1], 'article_id, article_title');
        $domain = C('MEDIA_SITE_URL');
        
        $str = "拇指微课测试期加大奖励力度，邀请粉丝、粉丝购买课程或者直接把课程推荐给朋友，均会获得奖励。\r\n";
        $str.= "戳链接了解详情。\r\n\r\n";
        $str.= "1、<a href=\"{$domain}/sales_model.html\">模式说明</a>\r\n\r\n";
        
        $i = 2;
        foreach ($articleList as $article) {
            $str.= "{$i}、<a href=\"{$domain}/article/{$article['article_id']}.html\">{$article['article_title']}</a>\r\n\r\n";
            $i++;
        }
        
        $str.= "<a href=\"{$domain}/article/\">了解更多</a>";
        
        return $str;
    }
}
