<?php
/**
 * 店铺app服务类
 */
namespace Media\Service;

class StoreService extends \Common\Service\StoreService
{
    public function getStoreNav($storeId)
    {
        $nav = M('store_page')->where(['store_id' => $storeId, 'type' => 2, 'state' => 1])->find();
        if (empty($nav)) {
            return null;
        }
        return $nav['content'];
    }
}


