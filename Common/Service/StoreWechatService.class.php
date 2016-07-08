<?php
namespace Common\Service;

/**
 * 店铺微信服务类
 */
class StoreWechatService
{
    protected $storeId;
    protected $wechat;
    
    public $errMsg = '';
    
    public function __construct($storeId = '')
    {
        if(empty($storeId)) {
            trigger_error('error store id, can not construct ' . __CLASS__, E_USER_WARNING);
        }
        $this->storeId = $storeId;
    }

    private function checkStoreAuth()
    {
//        $storeModel = new \Common\Model\StoreModel();
//        $storeInfo = $storeModel->getStoreInfo(['store_id' => $this->storeId]);
//        if (empty($storeInfo) || $storeInfo['store_state'] == 0) {
//            $this->errMsg = '店铺不存在或已关闭';
//            return false;
//        }
//        if ($storeInfo['if_bind_wechat'] == 0) {
//            $this->errMsg = '该店铺未绑定公众号';
//            return false;
//        }
//        
        // 获取店铺公众号信息
        $account = M('store_wechat')->where(['store_id' => $this->storeId])->find();
        if (empty($account) || $account['auth_state'] == '0') {
            $this->errMsg = '店铺未绑定公众号或已取消授权';
            return false;
        }
        $this->wechat = new \Org\Util\WechatPlatform($account);
        
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
    public function getQRUrl($scene_id, $type = 0, $expire = 3600)
    {
        if (!$this->wechat && !$this->checkStoreAuth()) return false;
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
    public function sendCustomMessage($data)
    {
        if (!$this->wechat && !$this->checkStoreAuth()) {
            return false;
        }
        $result = $this->wechat->sendCustomMessage($data);
        if (!$result) {
            $this->errMsg = $this->wechat->errMsg;
            return false;
        }
        return $result;
    }
}