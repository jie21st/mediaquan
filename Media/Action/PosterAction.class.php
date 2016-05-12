<?php
/**
 * 海报
 */
namespace Media\Action;

use Common\Service\WechatService as Wechat;

class PosterAction extends CommonAction
{
    protected $needAuth = true;
    /**
     * 获取海报
     */
    /**
     * @TODO 写入海报记录
     */
    public function getPosterOp()
    {
        // 用户信息
        $uid = session('user_id');
        $userInfo = D('User', 'Service')->getUserBaseInfo($uid);

        //制作海报
        $imageSrc = $this->_getImageInfo($userInfo);
        $mediaId = $this->_uploadMedia($imageSrc['pathInfo'], 'image');
        $this->_sendWechat($userInfo, $mediaId);

        $this->assign('imageSrc', $imageSrc['pathName']);
        $this->display();
    }

    /**
     * 制作海报
     * @param $userInfo   用户信息
     *
     * @return mixed url
     */
    private function _getImageInfo($userInfo)
    {
        $poster = D('Poster', 'Service');
        return $poster->getPoster($userInfo);
    }

    /**
     * 上传图片至微信服务器
     * @param        $url
     * @param string $type
     *
     * @return mixed
     */
    private function _uploadMedia($url, $type = "image")
    {

        $data['media'] = new \CURLFile($url);
        $wechat = new Wechat;
        $imagesInfo = $wechat->uploadMedia($data, $type);
        return $imagesInfo['media_id'];
    }

    /**
     * 发送消息
     * @param $userInfo     用户信息
     * @param $media_id     微信图片id
     */
    private function _sendWechat($userInfo, $media_id)
    {
        $data = array(
            'touser'    =>  $userInfo['user_wechatopenid'],
            'msgtype'   =>  'image',
            'image'     =>  array('media_id' => $media_id)
        );
        $wechat = new Wechat;
        $wechat->sendCustomMessage($data);
    }
}