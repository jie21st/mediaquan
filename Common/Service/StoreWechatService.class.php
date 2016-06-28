<?php
namespace Common\Service;

/**
 * 店铺微信服务类
 */
class StoreWechatService
{
    protected $wechat;
    
    public $errMsg = '';

    private function checkStoreAuth($storeId)
    {
        if (!$storeId) {
            $this->errMsg = 'store_id is invalid';
            return false;
        }
        $storeModel = new \Common\Model\StoreModel();
        $storeInfo = $storeModel->getStoreInfo(['store_id' => $storeId]);
        if (empty($storeInfo) || $storeInfo['store_state'] == 0) {
            $this->errMsg = 'store is closed or not exists';
            return false;
        }
        if ($storeInfo['if_bind_wechat'] == 0) {
            $this->errMsg = 'store doesn\'t bind wechat';
            return false;
        }
        
        // 获取店铺公众号信息
        $appInfo = M('wechat')->where(['store_id' => $storeId])->find();
        // 获取公众号token
        $tokenInfo = M('wechatToken')->where(['app_id' => $appInfo['appid']])->find();
        $wechat = new \Org\Util\Wechat;
        $wechat->checkAuth($appInfo['appid'], '', $tokenInfo['access_token']);
        $this->wechat = $wechat;
        
        return true;
    }
    
    /**
     * 生成带参数的二维码
     * 
     * @param type $scene_id
     * @param type $type 0:临时二维码；1:永久二维码(此时expire参数无效)
     * @param type $expire
     * @return boolean
     */
    public function getQRUrl($storeId, $scene_id, $type = 0, $expire = 3600)
    {
        if (!$this->wechat && !$this->checkStoreAuth($storeId)) return false;
        if ($scene_id <= 0) {
            return false;
        }
        $result= $this->wechat->getQRCode($scene_id, $type, $expire);
        if ($result) {
            $ticket = $result['ticket'];
            return $this->wechat->getQRUrl($ticket);
        } else {
            return false;
        }
    }
    
    /**
     * 新增临时素材
     * @param $data
     * @param $type
     *
     * @return mixed
     */
    public function uploadMedia($data, $type)
    {
        return $this->wechat->uploadMedia($data, $type);
    }

    /**
     * 发送消息
     * @param $data
     *
     * @return mixed
     */
    public function sendCustomMessage($storeId, $data)
    {
        if (!$this->wechat && !$this->checkStoreAuth($storeId)) return false;
        $result = $this->wechat->sendCustomMessage($data);
        if (!$result) {
            $this->errMsg = $this->wechat->errMsg;
            return false;
        }
        return $result;
    }
}