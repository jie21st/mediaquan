<?php
namespace Wechat\Action;

use \Org\Util\Wechat;
use \Org\Util\WechatPlatform;

class IndexAction extends \Think\Action
{
    /**
     * wechat 
     * 
     * @var mixed
     * @access private
     */
    private $wechat;
    
    private $message;
    
    private $account;

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
    
    public function indexOp()
    {
        if (!empty(I('get.appid'))){
            $appid = ltrim(I('get.appid'), '/');
        } else {
            exit('invalid appid');
        }
        
        $account = M('store_wechat')->where(['appid' => $appid])->find();
        if (empty($account)){
            exit('account not exists');
        }
        $this->account = $account;

        $wechat = new WechatPlatform($account);
        $wechat->valid();
        
        $message = $wechat->getRev()->getRevData();
        $this->message = $message;
        
        $this->booking($message);
        
        
        /*
        $type = $wechat->getRev()->getRevType();
        switch($type) {
            case Component::MSGTYPE_EVENT:
                $event = $wechat->getRev()->getRevEvent();
                $openid = $wechat->getRevFrom();
                $fansModel = new \Common\Model\FansModel();
                $fans = $fansModel->getFansInfo(['openid' => $openid]);
                switch ($event['event']) {
                    case 'subscribe':
                        if ($appInfo['mp_verify_type'] != 0) {
                            \Think\Log::write('该公众号未认证，不支持用户管理');
                            break;
                        }
                        \Think\Log::write('关注人openid: '.$openid);
                        $userInfo = $wechat->getUserInfo($appInfo['access_token'], $openid);
                        \Think\Log::write('关注人信息: '.json_encode($userInfo) . $wechat->errMsg);
                        $fansInfo = $fansModel->where(['openid' => $openid])->find();
                        if (empty($fansInfo)) {
                            $insert = array();
                            $insert['store_id'] = $account['store_id'];
                            $insert['user_id'] = 0;
                            $insert['openid'] = $userInfo['openid'];
                            $insert['fans_nickname'] = $userInfo['nickname'];
                            $insert['fans_sex'] = $userInfo['sex'];
                            $insert['fans_avatar'] = $userInfo['headimgurl'];
                            $insert['fans_country'] = $userInfo['country'];
                            $insert['fans_province'] = $userInfo['province'];
                            $insert['fans_city'] = $userInfo['city'];
                            $insert['subscribe_state'] = 1;
                            $insert['subscribe_time'] = $userInfo['subscribe_time'];
                            $insert['unsubscribe_time'] = 0;
                            $insert['fans_remark'] = $userInfo['remark'];

                            $fansModel->add($insert);
                        } else {
                            $update = array();
                            $update['fans_nickname'] = $userInfo['nickname'];
                            $update['fans_sex'] = $userInfo['sex'];
                            $update['fans_avatar'] = $userInfo['headimgurl'];
                            $update['fans_country'] = $userInfo['country'];
                            $update['fans_province'] = $userInfo['province'];
                            $update['fans_city'] = $userInfo['city'];
                            $update['subscribe_state'] = 1;
                            $update['subscribe_time'] = $userInfo['subscribe_time'];
                            $update['fans_remark'] = $userInfo['remark'];
                            $fansModel->where(['openid' => $openid])->save($update);
                        }
                        break;
                    case 'unsubscribe':
                        \Think\Log::write('取消关注openid: '.$openid);
                        if ($appInfo['mp_verify_type'] != 0) {
                            \Think\Log::write('该公众号未认证，不支持用户管理');
                            break;
                        }
                        $fansModel->where(['openid' => $openid])->setField('subscribe_state', 0);
                        break;
                }
                
                break;
            case Wechat::MSGTYPE_TEXT:
            case Wechat::MSGTYPE_IMAGE:
            case Wechat::MSGTYPE_VOICE:
            case Wechat::MSGTYPE_SHORTVIDEO:
                $wechat->transfer_customer_service()->reply();
                break;
            default:
                $data = $wechat->getRevData();
                \Think\Log::write('其他接收'.print_r($data, true));
        }
        */
    }
    
    private function booking($message) {
        if ($message['Event'] == 'subscribe' || $message['Event'] == 'unsubscribe') {
            $condition = array();
            $condition['store_id'] = $this->account['store_id'];
            $condition['date'] = date('Ymd');
            $todaystat = M('stat_fans')->where($condition)->find();
            if ($message['Event'] == 'subscribe') {
                if (empty($todaystat)) {
                    $data = array(
                        'store_id' => $this->account['store_id'],
                        'new' => 1,
                        'cancel' => 0,
                        'cumulate' => 0,
                        'date' => date('Ymd'),
                    );
                    M('stat_fans')->add($data);
                } else {
                    $data = array();
                    $data['new'] = ['exp', 'new+1'];
                    $data['cumulate'] = 0;
                    M('stat_fans')->where(['id' => $todaystat['id']])->setField($data);
                }
            } elseif ($message['Event'] == 'unsubscribe') {
                if (empty($todaystat)) {
                    $data = array(
                        'store_id' => $this->account['store_id'],
                        'new' => 0,
                        'cancel' => 1,
                        'cumulate' => 0,
                        'date' => date('Ymd'),
                    );
                    M('stat_fans')->add($data);
                } else {
                    $data = array();
                    $data['cancel'] = ['exp', 'cancel+1'];
                    $data['cumulate'] = 0;
                    M('stat_fans')->where(['id' => $todaystat['id']])->setField($data);
                }
            }
        }
        $fansModel = new \Common\Model\FansModel();
        $fans = $fansModel->getFansInfo(['openid' => $message['FromUserName']]);
        if (!empty($fans)) {
            if ($message['Event'] == 'unsubscribe') {
                $data = array();
                $data['subscribe_state'] = 0;
                $data['unsubscribe_time'] = time();
                $fansModel->where(['fans_id' => $fans['fans_id']])->save($data);
            }
        } else {
            if ($message['Event'] == 'subscribe' || $message['MsgType'] == 'text') {
                $data = array();
                $data['store_id'] = $this->account['store_id'];
                $data['user_id'] = 0;
                $data['openid'] = $message['FromUserName'];
                $data['subscribe_state'] = 1;
                $data['subscribe_time'] = $message['CreateTime'];
                $data['unsubscribe_time'] = 0;
                $fansModel->add($data);
            }
        }
    }
}
