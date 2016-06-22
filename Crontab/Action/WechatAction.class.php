<?php
namespace Crontab\Action;

class WechatAction extends \Think\Action
{
    /**
     * 初始化对象
     */
    public function __construct()
    {
        parent::__construct();

        register_shutdown_function(array($this, "shutdown"));
    }
    
    /**
     * 刷新token
     */
    public function refreshTokenOp()
    {
        $tokenModel = D('wechatToken');
        
        $condition = array();
        $condition['expire_time'] = ['lt', time() + 300];  //$item['expire_time'] <= time()+300
        $list = $tokenModel->where($condition)->select();
        $component = new \Org\Util\Component();
        
        foreach ($list as $item) {
            $result = $component->getAuthorizeRefreshToken($item['app_id'], $item['refresh_token']);
            if ($result) {
                $data = [];
                $data['access_token'] = $result['authorizer_access_token'];
                $data['refresh_token'] = $result['authorizer_refresh_token'];
                $data['expire_time'] = time() + intval($result['expires_in']) - 100;
                
                $update = $tokenModel->where(['app_id' => $item['app_id']])->save($data);
                if (!$update) {
                    echo $item['app_id'].'更新token失败: '.$component->errMsg;
                }
            } else {
                echo $item['app_id'].'获取token失败: '.$component->errMsg;
            }
            $this->counter['total']++;
        }
    }

    public function shutdown()
    {
        exit("\n" . date('Y-m-d H:i:s') . "\tsuccess");
    }
}
