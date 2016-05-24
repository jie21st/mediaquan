<?php
namespace Media\Action;

use \Think\Log;
use \Org\Util\Wechat;
use \Common\Model\UserModel;
use \Common\Service\UserService;

class WechatAction extends CommonAction
{
    /**
     * wechat 
     * 
     * @var mixed
     * @access private
     */
    private $wechat;

    /**
     * __construct 
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->wechat = new Wechat;
    }
    
    public function receiveOp()
    {
        $this->wechat->valid();
        $type = $this->wechat->getRev()->getRevType();
        \Think\Log::write('事件名称：'.$type);
        switch($type) {
            case Wechat::MSGTYPE_TEXT:
                $this->wechat->text("hello")->reply();
                exit;
                break;
            case Wechat::MSGTYPE_EVENT:
                $event = $this->wechat->getRev()->getRevEvent();
                $openId = $this->wechat->getRev()->getRevFrom();
                $userModel = new \Common\Model\UserModel;
                $userInfo = $userModel->getUserInfo(['user_wechatopenid' => $openId]);
                    
                \Think\Log::write('事件类型和key：'.json_encode($event));
                if ($event['event'] == 'subscribe') {
                    if (empty($userInfo)) {
                        $userInfo = $this->registerOp($openId);
                    }
                    // 修改为已关注状态
                    $userModel->editUser([
                        'subscribe_state' => 1,
                    ],[
                        'user_id' => $userInfo['user_id'],
                    ]);

                    // 用户未关注时，进行关注后的事件推送
                    if (! empty($event['key']) && preg_match('/^qrscene_\d/', $event['key'])) {
                        $parentId = substr($event['key'], 8);
                        \Think\Log::write('关注事件自带parent_id='.$parentId);
                    } else {
                        $defaultParents = C('USER_DEFAULT_PARENT');
                        if (is_array($defaultParents) && !empty($defaultParents)) {
                            shuffle($defaultParents);
                            $parentId = end($defaultParents);
                            \Think\Log::write('关注事件默认parent_id='.$parentId);
                        } else {
                            $parentId = 0;
                            \Think\Log::write('关注事件无parent_id');
                        }
                    }
                    $this->userspread($userInfo, $parentId);
                    $this->wechat->text("服务号建设中，请不要购买支付任何商品")->reply();
                } elseif ($event['event'] == 'unsubscribe') {
                    if (! empty($userInfo)) {
                        $userModel->editUser([
                            'subscribe_state' => 0,
                        ],[
                            'user_id' => $userInfo['user_id'],
                        ]);
                    }
                } elseif ($event['event'] == 'SCAN') { // 用户已关注时的事件推送
                    // 给用户提示一下
                    $recomUserInfo = $userModel->getUserInfo(['user_id' => $event['key']]);
                    if (!empty($recomUserInfo)) {
                        $msg = array();
                        $msg['touser'] = $recomUserInfo['user_wechatopenid'];
                        $msg['msgtype'] = 'text';
                        $msg['text'] = ['content' => $userInfo['user_nickname'].'扫描了您分享的二维码'];
                        $wechatService = new \Common\Service\WechatService;
                        $wechatService->sendCustomMessage($msg);
                    }
                } elseif ($event['event'] == 'CLICK') {
                    if ($event['key'] == 'WECHAT_QRCODE') {
                        if ($userInfo['buy_num'] == 0) {
                            $url = C('APP_SITE_URL');
                            $this->wechat->text('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="'.$url.'">立即点击“成为东家”</a>')->reply();
                        } else {
                            echo '';
                            $posterService = new \Media\Service\CreatePosterService();
                            $posterService->getPoster($userInfo['user_id']);
//                            $this->wechat->text('请点击链接获取二维码海报！<a href="'.$url.'">获取海报</a>')->reply();
//                            $url = C('APP_SITE_URL').'/poster/getPoster';
                        }
                    }
                }
                break;
            case Wechat::MSGTYPE_IMAGE:
                break;
            default:
                $this->wechat->text("help info")->reply();
        }
    }
    
    /**
     * 注册
     * 
     * @param type $openId
     */
    private function registerOp($openId)
    {
        $userModel = new \Common\Model\UserModel;
        $wxUserInfo = $this->wechat->getUserInfo($openId);
        $wxUserInfo['nickname'] = remove_emoji($wxUserInfo['nickname']);
        $insertInfo = [
            'user_nickname' => $wxUserInfo['nickname'],
            'user_sex' => $wxUserInfo['sex'],
            'user_wechatinfo' => serialize($wxUserInfo),
            'user_wechatopenid' => $openId,
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
        
        return $insertInfo;
    }
    
    /**
     * 用户推广
     * 
     * @param type $userInfo
     * @param type $parentId
     * @return type
     */
    private function userspread($userInfo, $parentId){
        if ($parentId == 0) {
            return;
        }
        try {
            $userModel = new \Common\Model\UserModel;

            if ($userInfo['user_id'] == $parentId) {
                throw new \Exception('推荐人扫描自己的二维码');
            }

            $recomUserInfo = $userModel->getUserInfo(['user_id' => $parentId]);
            if (empty($recomUserInfo)) {
                throw new \Exception('推荐人用户信息不存在');
            }

            if (intval($userInfo['parent_id'])) {
                throw new \Exception('该用户已存在推荐人');
            }

            if ($userInfo['user_id'] == $recomUserInfo['parent_id']) {
                throw new \Exception('该用户是当前推荐人的推荐人');
            }

            // 绑定关系
            $update = array();
            $update['parent_id'] = $recomUserInfo['user_id'];
            $userModel->editUser([
                'parent_id' => $recomUserInfo['user_id']
            ], [
                'user_id' => $userInfo['user_id']
            ]);

            // 奖励推荐人
            if (C('PREDEPOSIT_SPREAD_USER')) {
                $pdService = new \Common\Service\PredepositService;
                $pd_data = array();
                $pd_data['user_id'] = $recomUserInfo['user_id'];
                $pd_data['amount'] = C('PREDEPOSIT_SPREAD_USER');
                $pd_data['name'] = '发展用户'.$userInfo['user_nickname'];
                $pdService->changePd('sale_income', $pd_data);
            }
        
            $msgcontent = $userInfo['user_nickname'].'成为了您的粉丝';
        } catch (\Exception $e) {
            $msgcontent = $userInfo['user_nickname'].'扫描了您分享的二维码';
            \Think\Log::write('推广用户失败: '.$e->getMessage());
        }
        // 通知推荐人
        $msg = array();
        $msg['touser'] = $recomUserInfo['user_wechatopenid'];
        $msg['msgtype'] = 'text';
        $msg['text'] = ['content' => $msgcontent];
        $wechatService = new \Common\Service\WechatService;
        $wechatService->sendCustomMessage($msg);
    }

    /**
     * 获取用户信息,生成毕业证时需要实时用户信息
     *
     */
    public function getUserInfoOp()
    {
        if (! isset($_GET['code'])) {
            $returnUrl = $_GET['returnUrl'];
            if (empty($returnUrl)) {
                exit('empty returnUrl'); 
            }
            session('returnUrl', $returnUrl);
            // 生成唯一随机串防CSRF攻击
            $state = md5(uniqid(rand(), TRUE));
            session('state', $state);
            // 构造请求url
            $baseUrl = C('APP_SITE_URL') . $_SERVER['REQUEST_URI'];
            $url = $this->wechat->getOauthRedirect($baseUrl, $state); 
            redirect($url);
        } else {
            // 验证state防止CSRF攻击
            if(session('state') != I('get.state', '')) {
                exit('The state does not match. You may be a victim of CSRF.');
            }
            $result = $this->wechat->getOauthAccessToken();
            if (false === $result) {
                exit('AuthToken error');
            }
            $authUserInfo = $this->wechat->getOauthUserinfo($result['access_token'], $result['openid']);
            if (false === $authUserInfo) {
                exit('获取用户信息失败');
            }
            $headimgurl = urlencode($authUserInfo['headimgurl']);
            $returnUrl = session('returnUrl');
            if (false !== strpos($returnUrl, '?')) {
                $url = $returnUrl . '&headimgurl=' . $headimgurl;
            } else {
                $url = $returnUrl . '?headimgurl=' . $headimgurl;
            }
            redirect($url);
        }
    }

    /**
     * 公众平台权限
     * 获取公众平台TOKEN
     *
     * @access public
     * @return json
     */
    public function getAccessTokenOp()
    {
        $result = $this->wechat->checkAuth();
        if ($result) {
            $result['expire'] *= 1000;
            $this->returnJson(1, 'SUCCESS', $result);
        } else {
            $this->returnJson(0, '获取失败');
        }
    }

    /**
     * 生成带参数的二维码
     *
     */
    public function getQRUrlOp()
    {
        $scene_id = I('post.scene_id', 0, 'intval');
        $type = I('post.type', 0, 'intval');
        $expire = I('post.expire', 3600, 'intval');

        if ($scene_id <= 0) {
            exit('scene_id invalid'); 
        }
        $result= $this->wechat->getQRCode($scene_id, $type, $expire);
        if ($result) {
            $ticket = $result['ticket']; 
            $url = $this->wechat->getQRUrl($ticket);
            $this->returnJson(1, 'SUCCESS', [
                'url'   => $url
            ]);
        } else {
            $this->returnJson(0, '获取失败'); 
        }
    }

    public function sendTemplateMessageOp()
    {
        
        $data = [
            'touser'    => 'oeE-Tt07niiF4LgVpyHkLwLyT8lg',
            'template_id'   => '-xHiYGseJaw8ZcwuLf-qrtCjUVsMGWn8-Yqql-tTHr8',    
            'url'       => 'http://m.guanlizhihui.com:86/i/feed',
            'topcolor'  => '#FF0000',
            'data'  => [
                'first' => [
                    'value' => '我们已收到您的货款，开始为您打包商品，请耐心等待',
                    'color' => '#173177', 
                ],
                'orderMoneySum' => [
                    'value' => '39.10元',
                    'color' => '#173177'
                ],
                'orderProductName' => [
                    'value' => '微信公开课',
                    'color' => '#173177'
                ],
                'Remark'    => [
                    'value' => '如有问题欢迎致电13521923981',
                    'color' => '#173177'
                ]
            ],
        ];
        $result = $this->wechat->sendTemplateMessage($data);
    }
     
    /**
     * 公众平台JSAPI签名
     * 
     * @access public
     * @return void
     */
    public function getJsSignOp()
    {
        $url = I('request.url', '', 'urldecode');
        if (! $url) {
            $this->ajaxReturn(['code' => 10010, 'msg' => '参数错误'], 'jsonp');
        }
        $result = $this->wechat->getJsSign($url);
        if ($result) {
            $this->ajaxReturn(['code' => 1, 'data' => $result, 'msg' => 'SUCCESS'], 'jsonp');
        } else {
            $this->ajaxReturn(['code' => 10010, 'errMsg' => '参数错误'], 'jsonp');
        }
    }
    
    
}
