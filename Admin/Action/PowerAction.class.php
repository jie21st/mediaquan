<?php
//=======================================
// 权限管理
//=======================================
namespace Admin\Action;

class PowerAction extends CommonAction 
{
    public function getUserPowerOp()
    {
        $userId = $this->uid['id'];
        dump($userId);die;
        $powerId = I('post.power_id');

        $powerUrl = C('BASE_CAT_URL') . C('ADMIN_POWER_URL');
        $data = [
            'userId' => $userId,
            'funcId' => $powerId,
        ];

        // 调用接口
        $url = new \Org\Util\URL;
        $result = json_decode($url->get($powerUrl, $data), true);
        $this->ajaxReturn($result);
    }
}
