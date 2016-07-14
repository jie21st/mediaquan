<?php
namespace Media\Action;

class BaseStoreAction extends CommonAction
{
    protected $storeInfo;
    
    protected $needAuth = true;
    
    public function __construct()
    {
        parent::__construct();
        
        $storeId = I('get.i', 0, 'intval');
        if($storeId <= 0) {
            $storeId = session('current_store_id');
            if (empty($storeId)) {
                send_http_status('404');
            }
        }
        
        $storeService = new \Common\Service\StoreService();
        $storeInfo = $storeService->getStoreOnlineInfoByID($storeId);
        if (empty($storeInfo)) {
            showMessage('该店铺不存在或已关闭');
        }
        
        session('current_store_id', $storeId);
        $this->storeInfo = $storeInfo;

        $this->fansLogin();
    }
    
    protected function fansLogin()
    {
        $storeId = $this->storeInfo['store_id'];
        if (session('?store_fans_'.$storeId)) {
            return;
        }
        $wechatModel = M('store_wechat');
        $appInfo = $wechatModel->where(['store_id'=> $storeId])->find();
        if (empty($appInfo) || $appInfo['auth_state'] == 0 || $appInfo['mp_type'] != 4) {
            session('store_fans_'.$storeId, -1); // -1表示无法绑定
            return;
        }
        $model = new \Common\Model\FansModel();
        $condition = array();
        $condition['store_id'] = $storeId;
        $condition['user_id'] = session('user_id');
        $fansInfo = $model->where($condition)->find();
        if (is_array($fansInfo) && !empty($fansInfo)) {
            session('store_fans_'.$storeId, $fansInfo['fans_id']);
        } else {
            $accountPlatform = new \Org\Util\WechatPlatform();
            $state = session('state');
            if (empty($state)) {
                $state = md5(uniqid(rand(), true));
                session('state', $state);
            }
            if (!session('?_dest_url')) {
                session('_dest_url', getCurrentURL());
            }
            $callback = C('MEDIA_SITE_URL').'/login/bindStoreUser?store_id='.$storeId.'&scope=snsapi_base';
            $loginUrl = $accountPlatform->getOauthRedirect($appInfo['appid'], $callback, $state, 'snsapi_base');
            redirect($loginUrl);
        }
    }
}
