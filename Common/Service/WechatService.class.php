<?php
namespace Common\Service;

/**
 * 微信服务类
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
     */
    public function getQRUrl($scene_id, $type = 'QR_SCENE', $expire = 3600)
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
     * 公众平台权限
     * 获取公众平台TOKEN
     *
     * @access public
     * @return 
     */
    public function getAccessTokenOp()
    {
        $result = $this->wechat->checkAuth();
        if ($result) {
            return $result;
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
                'message_faildesc' => $json['errcode'].': '.$json['errmsg']
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