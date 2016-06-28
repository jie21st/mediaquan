<?php
namespace Common\Service;

/**
 * 店铺服务类
 * @author Wang Jie <im.wjie@gmail.com>
 */
class StoreService
{
    /**
     * 通过id获取店铺信息
     * @param type $storeId
     * @return type
     */
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
    
    /**
     * 发送消息
     * 
     * @param type $storeId 店铺id
     * @param type $userId 平台用户id
     * @param type $tplCode 模板调用代码
     * @param type $params 模板参数
     * @return boolean
     */
    public function sendMessage($storeId, $userId, $tplCode, $params)
    {
        $tplModel = new \Common\Model\MessageTemplatesModel();
        $tplInfo = $tplModel->getOneTemplates($tplCode);
        if (empty($tplInfo) || $tplInfo['tpl_state'] == 0) {
            return false;
        }
        
        $fansModel = new \Common\Model\FansModel();
        $receiver = $fansModel->getFansInfo(['store_id' => $storeId, 'user_id' => $userId]);
	if (empty($receiver)) {
            return false;
        }
        $message = notifyReplaceText($tplInfo['tpl_content'], $params);
        
        $storeWechatService = new \Common\Service\StoreWechatService();
        // 发送
//        $messageModel = M('message');
//        $data = array(
//            'to_fans_id'        => $receiver['fans_id'],
//            'msg_type'          => 'text',
//            'msg_body'          => $message,
//            'msg_state'         => 0,
//            'msg_create_time'   => time(),
//        );
//        $msgId = $messageModel->add($data);
//        if (!$msgId) {
//            return false;
//        }
        $result = $storeWechatService->sendCustomMessage($storeId, [
            'touser' => $receiver['openid'],
            'msgtype' => 'text',
            'text' => ['content' => $message]
        ]);
        if (!$result) {
            echo $storeWechatService->errMsg;
        }
        
        return true;
    }
}
