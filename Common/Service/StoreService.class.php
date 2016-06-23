<?php
namespace Common\Service;

/**
 * 店铺服务类
 * @author Wang Jie <im.wjie@gmail.com>
 */
class StoreService
{
    public function getStoreInfoByID($storeId) {
        
        $cachename = 'store:'.$storeId;
        $redis = \Think\Cache::getInstance('redis');
        $storeInfo = $redis->get($cachename);
        if(empty($storeInfo)) {
            $storeModel = new \Common\Model\StoreModel;
            $storeInfo = $storeModel->getStoreInfo(['store_id' => $storeId]);
            if ($storeInfo) {
                $redis->set($cachename, $storeInfo, 3600);
            }
        }
        return $storeInfo;
    }
    
    public function getStoreOnlineInfoByID($storeId)
    {
        $storeInfo = $this->getStoreInfoByID($storeId);
        if (empty($storeInfo) || $storeInfo['store_state'] == '0') {
            return null;
        }
        return $storeInfo;
    }
}