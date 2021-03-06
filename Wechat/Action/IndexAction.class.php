<?php
namespace Wechat\Action;

use \Org\Util\Wechat;
use \Org\Util\WechatPlatform;

class IndexAction extends \Think\Controller
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
    
    public function index()
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
        if (empty($message)) {
            exit('request failed');
        }
        $this->message = $message;
        
        $this->booking($message);
        
        $pars = $this->analyze($message);
        
        \Think\Log::write('分析结果'.$pars);
        
        
//        foreach ($pars as $par) {
//            $par['message'] = $message;
//            
//            $this->process($par);
//        }

    }
    
    private function process($params) {
        $processor = \Think\Process::getInstance($params['module']);
        $response = $processor->response();
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
    
    private function analyze(&$message) {
        $params = array();
//        if ($message['MsgType'] == 'event') {
//            $params = $this->analyzeEvent($message);
//            if (!empty($params)){
//                return $params;
//            }
//        }
        
        if (method_exists($this, 'analyze' . $message['MsgType'])) {
            $temp = call_user_func_array(array($this, 'analyze' . $message['MsgType']), array(&$message));
            if(!empty($temp) && is_array($temp)){
                    $params += $temp;
            } else {
                $params += $this->handler($message['MsgType']);
            }
        }
        
        return $params;
    }
    
    private function handler($type) {
        
    }


    private function analyzeEvent(&$message) {
        if ($message['Event'] == 'subscribe') {
            return $this->analyzeSubscribe($message);
        }
    }
    
    private function analyzeSubscribe(&$message) {
        $params = array();
        $message['type'] = 'text'; 
        $message['redirection'] = true;
        $message['source'] = 'subscribe';
        $setting = M('store_settings')->where(['store_id' => $this->account['store_id']])->find();
        if (!empty($setting['welcome'])) {
            $message['content'] = $setting['welcome'];
            $params += $this->analyzeText($message);
        }
        
        return $params;
    }
    
    private function analyzeText(&$message) {
        $pars = array();
        if(!isset($message['content'])) {
            return $pars;
        }

        $condition = array();
        $condition['store_id'] = $this->account['store_id'];
        $condition['keyword'] = $message['content'];
        $keyword = M('rule_keyword')->where($condition)->find();
        return $keyword;
    }
}
