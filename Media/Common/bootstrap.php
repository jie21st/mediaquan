<?php
/**
 * 初始化
 */
$GLOBALS['store_id'] = I('get.i', 0, 'intval');
$storeService = new \Common\Service\StoreService();
$GLOBALS['store_info'] = $storeService->getStoreOnlineInfoByID($GLOBALS['store_id']);
if (empty($GLOBALS['store_info'])) {
    showMessage('该店铺不存在或已关闭');
}