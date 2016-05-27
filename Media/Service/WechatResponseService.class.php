<?php
namespace Media\Service;

use Org\Util\Wechat;

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
     * 构造方法
     */
    public function __construct()
    {
        $this->wechat = new Wechat;
    }
    
    /**
     * 微信推送事件处理
     */
    public function responseHandle()
    {
        $this->wechat->valid();
        $type = $this->wechat->getRev()->getRevType();
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
                        // 注册用户
                        $userInfo = $this->registerOp($openId);
                    }
                    
                    // 用户未关注时，进行关注后的事件推送
                    if (! empty($event['key']) && preg_match('/^qrscene_\d/', $event['key'])) {
                        $parentId = substr($event['key'], 8);
                        \Think\Log::write('关注事件自带parent_id='.$parentId);
                    } else {
                        $defaultParents = C('DEFAULT_USER_PARENT');
                        if (is_array($defaultParents) && !empty($defaultParents)) {
                            shuffle($defaultParents);
                            $parentId = end($defaultParents);
                            \Think\Log::write('关注事件默认parent_id='.$parentId);
                        } else {
                            $parentId = 0;
                            \Think\Log::write('关注事件无parent_id');
                        }
                    }
                    // 推广用户处理
                    $this->userspread($userInfo, $parentId);
                    
                    // 修改为已关注状态
                    $userModel->editUser([
                        'subscribe_state' => 1,
                    ],[
                        'user_id' => $userInfo['user_id'],
                    ]);
                    
                    // 关注推送消息
                    (new \Media\Service\SendMsgService)->sendNews($userInfo);
                    $this->wechat->text("服务号建设中，请不要购买支付任何商品")->reply();
                } elseif ($event['event'] == 'unsubscribe') {
                    // 如果存在用户设置为未订阅
                    if (! empty($userInfo)) {
                        $userModel->editUser([
                            'subscribe_state' => 0,
                        ],[
                            'user_id' => $userInfo['user_id'],
                        ]);
                    }
                } elseif ($event['event'] == 'SCAN') {
                    // 用户已关注时的事件推送
                    // 给用户提示一下
                    $recomUserInfo = $userModel->getUserInfo(['user_id' => $event['key']]);
                    if (! empty($recomUserInfo)) {
                        $msg = array();
                        $msg['touser'] = $recomUserInfo['user_wechatopenid'];
                        $msg['msgtype'] = 'text';
                        $msg['text'] = ['content' => $userInfo['user_nickname'].'扫描了您分享的二维码'];
                        $wechatService = new \Common\Service\WechatService;
                        $wechatService->sendCustomMessage($msg);
                    }
                    (new \Media\Service\SendMsgService)->sendNews($userInfo);
                } elseif ($event['event'] == 'CLICK') {
                    if ($event['key'] == 'WECHAT_QRCODE') {
                        if (C('SPREAD_POSTER_USE')) {
                            if (C('SPERAD_POSTER_GENERATE_NEEDBUY')) {
                                if ($userInfo['buy_num'] == 0) {
                                    $url = C('MEDIA_SITE_URL');
                                    $this->wechat->text('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="'.$url.'">立即点击“成为东家”</a>')->reply();
                                } else {
                                    echo '';
                                    $posterService = new \Media\Service\CreatePosterService();
                                    $posterService->getPoster($userInfo['user_id']);
                                }
                            } else {
                                echo '';
                                $posterService = new \Media\Service\CreatePosterService();
                                $posterService->getPoster($userInfo['user_id']);
                            }
                        } else {
                            $this->wechat->text('暂时无法获取海报')->reply();
                        }
                    } elseif ($event['key'] == 'WECHAT_XSZN') {
                        $str = (new \Media\Service\SendMsgService)->sendXszn();
                        $this->wechat->text($str)->reply();
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
     * @param type $userInfo
     * @param type $parentId
     * @return type
     */
    private function userspread($userInfo, $parentId){
        if (intval($userInfo['parent_id'])) {
            \Think\Log::write('该用户已存在推荐人');
            return false;
        }
            
        if ($parentId == 0) {
            \Think\Log::write('parent_id为0');
            return false;
        }
        
        if ($userInfo['user_id'] == $parentId) {
            \Think\Log::write('parent_id为当前用户自己');
            return false;
        }
            
        try {
            $userModel = new \Common\Model\UserModel;

            $recomUserInfo = $userModel->getUserInfo(['user_id' => $parentId]);
            if (empty($recomUserInfo)) {
                throw new \Exception('推荐人用户信息不存在');
            }

            if ($userInfo['user_id'] == $recomUserInfo['parent_id']) {
                throw new \Exception('该用户是当前推荐人的推荐人');
            }

            // 绑定关系
            $update = array();
            $update['parent_id'] = $recomUserInfo['user_id'];
            $result = $userModel->editUser($update, ['user_id' => $userInfo['user_id']]);
            if (! $result) {
                throw new \Exception('绑定写入失败');
            }

            // 通知
            $spreadUserAmount = C('SPERAD_SELLER_GAINS_AMOUNT');
            if (is_numeric($spreadUserAmount) && $spreadUserAmount > 0) {
                $pdService = new \Common\Service\PredepositService;
                $pd_data = array();
                $pd_data['user_id'] = $recomUserInfo['user_id'];
                $pd_data['amount'] = $spreadUserAmount;
                $pd_data['name'] = '推荐用户 '.$userInfo['user_nickname'];
                $pdService->changePd('sale_income', $pd_data);

                // 收益通知
                $wechatService = new \Common\Service\WechatService;
                $wechatService->sendCustomMessage([
                    'touser' => $recomUserInfo['user_wechatopenid'],
                    'msgtype' => 'text',
                    'text' => [
                        'content' => sprintf(
                                        '%s成为了您的粉丝，您获得收益%s元；推荐好友购买课程还可获得1-99元的收益，<a href="%s">点击查看</a>',
                                        $userInfo['user_nickname'],
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
                $msg['text'] = ['content' => $userInfo['user_nickname'].'成为了您的粉丝'];
                $wechatService = new \Common\Service\WechatService;
                $wechatService->sendCustomMessage($msg);
            }
        } catch (\Exception $e) {
            \Think\Log::write('推广用户失败: '.$e->getMessage());
        }
    }
}
