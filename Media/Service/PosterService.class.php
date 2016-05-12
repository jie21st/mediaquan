<?php
/**
 * 海报生产服务类
 */
namespace Media\Service;

use Common\Service\ImagesMergerService as Images;
use Common\Service\WechatService as Wechat;

class PosterService
{
    public function getPoster($userInfo)
    {
        // 用户头像地址
        $this->uid = $userInfo['user_id'];
        $this->_setUserImageSrc($userInfo['user_avatar']);
        $this->_getWechatRQCode();
        $this->_setConfig();
        return $this->_getImagePath();
    }

    /**
     * 获取用户信息
     * @param $uid　用户ID
     *
     * @return array 用户信息
     */
    private function _setUserImageSrc($avatar)
    {
        $this->userImageSrc = DIR_UPLOAD . DS . ATTACH_AVATAR . DS . $avatar;
    }


    /**
     * 获取微信二维码地址
     * @param string $type  类型 (临时 QR_SCENE  永久 QR_LIMIT_SCENE)
     * @param string $expire
     */
    private function _getWechatRQCode($type = 'QR_SCENE')
    {
        $wechat = new Wechat;
        $this->wechatRQCode = $wechat->getQRUrl($this->uid, $type, C('POSTER_TIME'));
    }

    /**
     * 设置海报配置
     * @return array
     */
    private function _setConfig()
    {
        $dst = DIR_RESOURCE . DS .ATTACH_POSTER . DS . 'poster.jpg';
        $savePath = DIR_UPLOAD . DS .ATTACH_POSTER . DS;

        $config = array(
            'dst'       =>  $dst,           // 模板地址(目标图)
            'isPrint'   =>  false,          // 是否打印
            'isSave'    =>  true,           // 是否保存
            'savePath'  =>  $savePath,         // 保存路径
            'saveName'  =>  md5($this->uid . time()),                // 保存名字
            // 缩略图
            'src' => array(
                array(
                    'srcPath'   =>  $this->wechatRQCode,     // 图片路径
                    'srcX'      =>  '147',      // X轴位置
                    'srcY'      =>  '407',      // Y轴位置
                    'srcW'      =>  '270',      // 图片宽度
                    'srcH'      =>  '270',      // 图片高度
                ),
                array(
                    'srcPath'   =>  $this->userImageSrc,   // 图片路径
                    'srcX'      =>  '230',      // X轴位置
                    'srcY'      =>  '167',      // Y轴位置
                    'srcW'      =>  '111',      // 图片宽度
                    'srcH'      =>  '111',      // 图片高度
                ),
            ),
        );

        $this->config = $config;
    }

    /**
     * 生产海报
     * @param $config
     *
     * @return string
     */
    private function _getImagePath()
    {
        $images = new Images($this->config);
        $images->start();
        return array('pathInfo' => $images->pathInfo, 'pathName' => $images->pathName);
    }
}