<?php
namespace Wechat\Action;

use \Org\Util\Wechat;
use \Org\Util\Component;

class IndexAction extends \Think\Action
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
    
    public function indexOp()
    {
        if (!empty(I('get.appid'))){
            $appid = ltrim(I('get.appid'), '/');
        } else {
            exit('invalid appid');
        }
        
        $model = M('store_wechat');
        $appInfo = $model->where(['appid' => $appid])->find();
        if (empty($appInfo)){
            exit('app not exists');
        }

        $wechat = new Component;
        $wechat->valid();
        $type = $wechat->getRev()->getRevType();
        switch($type) {
            case Component::MSGTYPE_EVENT:
                $event = $wechat->getRev()->getRevEvent();
                $openid = $wechat->getRevFrom();
                $fansModel = new \Common\Model\FansModel();
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
                            $insert['store_id'] = $appInfo['store_id'];
                            $insert['openid'] = $userInfo['openid'];
                            $insert['fans_nickname'] = $userInfo['nickname'];
                            $insert['fans_sex'] = $userInfo['sex'];
                            $insert['fans_avatar'] = $userInfo['headimgurl'];
                            $insert['fans_country'] = $userInfo['country'];
                            $insert['fans_province'] = $userInfo['province'];
                            $insert['fans_city'] = $userInfo['city'];
                            $insert['subscribe_state'] = 1;
                            $insert['subscribe_time'] = $userInfo['subscribe_time'];
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
    }
    
    /**
     * 接收微信推送消息
     */
    public function _receiveOp()
    {
        (new \Media\Service\WechatResponseService)->responseHandle();
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
