<?php
/**
 * 海报生产服务类
 */
namespace Media\Service;

use Common\Service\ImagesMerger as Images;

class PosterService
{
    public function getPoster($uid)
    {
        // 用户头像地址
        $this->uid = $uid;
        $this->_getUserImages($uid);
        $this->_getWechatRQCode($uid);
        $this->_setConfig();
        $this->_getImagePath();
    }

    /**
     * 获取用户信息
     * @param $uid　用户ID
     *
     * @return array 用户信息
     */
    private function _getUserImages($uid)
    {
        $condition = array('user_id' => $uid);
        $field = 'user_avatar';
        $userInfo = D('User')->getUserInfo($condition, $field);
        $this->userImageSrc = DIR_UPLOAD . DS . ATTACH_AVATAR . DS . $userInfo['user_avatar'];
    }

    private function _getWechatRQCode($uid, $type = '0', $expire = '2592000')
    {
//        $time = C('POSTER_TIME');
//        if ($expire > $time) $expire = $time;
//
//        $keys = [
//            'scene_id' => $uid,
//            'type' => $type,
//            'expire' => $expire,
//        ];
//
//        $url = new URL;
//        $res = $url->post(C('RQCodePath'), $keys);
//        $res = json_decode($res, true);
//        return $res['data']['url'];
        $this->wechatRQCode = DIR_UPLOAD . DS .ATTACH_POSTER . DS .'rqcode/10001_wechat.jpg';
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
        return  $images->pathInfo;
    }

    /**
     * 写入数据
     */
    private function _insertData()
    {

    }


}