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
        $model = M('wechat');
        $component = new \Org\Util\Component();
        
        $condition = array();
        $condition['auth_state'] = 1;
        $condition['token_expiretime'] = ['lt', time() + 300];
        $list = $model->field('appid,refresh_token')->where($condition)->select();
        
        foreach ($list as $item) {
            $result = $component->getAuthorizeRefreshToken($item['appid'], $item['refresh_token']);
            if ($result) {
                $data = array();
                $data['access_token'] = $result['authorizer_access_token'];
                $data['refresh_token'] = $result['authorizer_refresh_token'];
                $data['token_expiretime'] = time() + intval($result['expires_in']) - 100;
                
                $update = $model->where(['appid' => $item['appid']])->save($data);
                if (!$update) {
                    echo $item['appid'].'写入token失败: '.$component->errMsg;
                }
            } else {
                echo $item['appid'].'获取token失败: '.$component->errMsg;
            }
        }
    }

    public function shutdown()
    {
        exit("\n" . date('Y-m-d H:i:s') . "\tsuccess");
    }
}
