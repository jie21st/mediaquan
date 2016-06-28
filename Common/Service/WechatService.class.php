<?php
namespace Common\Service;

/**
 * 平台微信服务类
 */
class WechatService
{
    protected $wechat;
    
    public function __construct()
    {
        $this->wechat = new \Org\Util\Wechat;
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
        $messageModel = D('wechatMessage');
        $messageId = $messageModel->add([
            'message_data' => json_encode($data),
            'message_time' => time(),
        ]);
        if (!$messageId) {
            \Think\Log::write('客服消息记录失败');
            return false;
        }
        $json = $this->wechat->sendCustomMessage($data);
        if ($json) {
            $messageModel->where(['message_id' => $messageId])->save([
                'message_state' => 1
            ]);
        } else {
            $messageModel->where(['message_id' => $messageId])->save([
                'message_state' => 2,
                'message_faildesc' => $this->wechat->errCode.': '.$this->wechat->errMsg
            ]);
        }
        return true;
    }

    /**
     * 创建菜单
     * @param $data
     *
     * @return mixed
     */
    public function createMenu($data)
    {
        return $this->wechat->createMenu($data);
    }

    /**
     * 删除菜单
     * @return mixed
     */
    public function deleteMenu()
    {
        return $this->wechat->deleteMenu();
    }

    /**
     * 失败信息
     * @return mixed
     */
    public function error()
    {
        $error['errCode'] = $this->wechat->errCode;
        $error['errMsg'] = $this->wechat->errMsg;
        return $error;
    }
}
