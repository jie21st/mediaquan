<?php
namespace Media\Action;

class BaseStoreAction extends CommonAction
{
    protected $storeInfo;
    
    protected $needAuth = true;
    
    public function __construct()
    {
        parent::__construct();
        
        $storeId = I('store_id', 0, 'intval');
        if($storeId <= 0) {
            showMessage('参数错误');
        }
        
        $storeService = new \Common\Service\StoreService();
        $storeInfo = $storeService->getStoreOnlineInfoByID($storeId);
        if (empty($storeInfo)) {
            showMessage('该店铺不存在或已关闭');
        } else {
            session('current_store_id', $storeId);
            $this->storeInfo = $storeInfo;
        }
    }
}
