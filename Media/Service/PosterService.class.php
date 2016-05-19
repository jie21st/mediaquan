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
        if(false === $this->_getWechatRQCode()) return false;
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
        $url = $wechat->getQRUrl($this->uid, $type, C('POSTER_TIME'));
        if (false === $url) {
            return false;
        }

        $this->wechatRQCode = $url;
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
            'isPrint'   =>  true,          // 是否打印
            'isSave'    =>  true,           // 是否保存
            'savePath'  =>  $savePath,         // 保存路径
            'saveName'  =>  md5($this->uid . time()),                // 保存名字
            // 缩略图
            'src' => array(
                array(
                    'srcPath'   =>  $this->wechatRQCode,     // 图片路径
                    'srcX'      =>  '167',      // X轴位置
                    'srcY'      =>  '460',      // Y轴位置
                    'srcW'      =>  '416',      // 图片宽度
                    'srcH'      =>  '420',      // 图片高度
                ),
                array(
                    'srcPath'   =>  $this->userImageSrc,   // 图片路径
                    'srcX'      =>  '275',      // X轴位置
                    'srcY'      =>  '167',      // Y轴位置
                    'srcW'      =>  '200',      // 图片宽度
                    'srcH'      =>  '200',      // 图片高度
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
        return array(
            'pathInfo' => $images->pathInfo,
            'pathName' => $images->pathName,
            'time'     => time()
        );
    }
}