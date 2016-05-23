<?php
/**
 * 海报生产服务类
 */
namespace Media\Service;

use Common\Service\ImagesMergerService as Images;
use Common\Service\WechatService as Wechat;

class PosterService
{
    public function getPoster($userInfo, $wechatTime, $times)
    {
        // 用户头像地址
        $this->uid = $userInfo['user_id'];
        $this->wechatTime = $wechatTime;
        $this->times = $times;
        $this->userName = ($userInfo['user_truename'])? $userInfo['user_truename'] : $userInfo['user_nickname'];
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
        $url = $wechat->getQRUrl($this->uid, $type, $this->wechatTime);
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
        $fontPath = DIR_RESOURCE . DS . ATTACH_POSTER .'/Font/SourceHanSansK-Medium.ttf';
        $dst = DIR_RESOURCE . DS .ATTACH_POSTER . DS . 'poster.jpg';
        $savePath = DIR_UPLOAD . DS .ATTACH_POSTER . DS;
        $today = date('m月d日', $this->times + $this->wechatTime);

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
                    'srcX'      =>  '168',      // X轴位置
                    'srcY'      =>  '570',      // Y轴位置
                    'srcW'      =>  '412',      // 图片宽度
                    'srcH'      =>  '412',      // 图片高度
                ),
                array(
                    'srcPath'   =>  $this->userImageSrc,   // 图片路径
                    'srcX'      =>  '300',      // X轴位置
                    'srcY'      =>  '267',      // Y轴位置
                    'srcW'      =>  '150',      // 图片宽度
                    'srcH'      =>  '150',      // 图片高度
                ),
            ),
            'font' => array(
                 array(
                     'text'      => '该二维码30天('.$today.'前)有效，过期请重新获取', 	// 字体路径
                     'fontPath'  => $fontPath,// 字体路径
                     'fontSize'  => '16', 				// 字体大小
                     'fontColor' => '91,91,91', 			// 字体颜色
                     'fontX'     => '147', 				// X轴位置 支持center(自动居中)
                     'fontY'     => '1040',					// Y轴位置
                     'adjust'    => '0' 					// 位置调整
                 ),
                 array(
                     'text'      => $this->userName, 	// 字体路径
                     'fontPath'  => $fontPath,// 字体路径
                     'fontSize'  => '20', 				// 字体大小
                     'fontColor' => '91,91,91', 			// 字体颜色
                     'fontX'     => 'center', 				// X轴位置 支持center(自动居中)
                     'fontY'     => '466',					// Y轴位置
                     'adjust'    => '0' 					// 位置调整
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
            'pathName' => $images->pathName
        );
    }
}
