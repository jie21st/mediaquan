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
    public function getPosterOp()
    {
        // 用户信息
        $uid = session('user_id');
        $userInfo = D('User', 'Service')->getUserBaseInfo($uid);
        $posterInfo = $this->getUserPosterInfo($uid);

        if(time() > $posterInfo['poster_end_time']) {
            //制作海报
            $imageSrc   = $this->_getImageInfo($userInfo);
            //微信上传
            $mediaInfo  = $this->_uploadMedia($imageSrc, 'image');
        } else if(time() > $posterInfo['wechat_upload_end_time']) {
            $imageSrc['pathInfo']   = $posterInfo['poster_src'];
            $imageSrc['poster_id']  = $posterInfo['id'];
            $imageSrc['pathName']   = $posterInfo['poster_images_name'];
            $imageSrc['poster_end_time']  = $posterInfo['poster_end_time'];

            //微信上传
            $mediaInfo = $this->_uploadMedia($imageSrc, 'image');
        } else {
            $mediaInfo['media_id']      = $posterInfo['wechat_media_id'];
            $mediaInfo['end_time']      = $posterInfo['poster_end_time'];
            $mediaInfo['start_time']    = $posterInfo['poster_create_time'];
            $imageSrc['pathName']       = $posterInfo['poster_images_name'];
        }

        //发送消息
        $this->_sendWechat($userInfo, $mediaInfo);
        $this->assign('imageSrc', $imageSrc['pathName']);
        $this->display();
    }

    /**
     * 用户最新海报
     * @param $uid
     *
     * @return mixed
     */
    private function getUserPosterInfo($uid)
    {
        $poster = D('Poster', 'Model');
        $condition = array('user_id'=>$uid);
        return $poster->getUserPoster($condition);
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
        $imageSrc = $poster->getPoster($userInfo);

        if(false === $imageSrc) {
            exit('海报制作失败!请重新生成或者联系客服人员');
        }

        $data = array(
            'user_id' => $userInfo['user_id'],
            'poster_images_name' => $imageSrc['pathName'],
            'poster_src' => $imageSrc['pathInfo'],
            'poster_create_time' => $imageSrc['time'],
            'poster_end_time' => ($imageSrc['time'] + C('POSTER_TIME')),
            'poster_status' => ($imageSrc['pathName'] != '') ? 1 : 0,
        );

        $state = D('Poster', 'Model')->addData($data);

        if(!$state) {
            exit('海报制作失败!请重新生成或者联系客服人员');
        }

        $imageSrc['poster_id'] = $state;
        $imageSrc['poster_end_time'] = $data['poster_end_time'];
        return $imageSrc;
    }

    /**
     * 上传图片至微信服务器
     * @param        $url
     * @param string $type
     *
     * @return mixed
     */
    private function _uploadMedia($imageSrc, $type = "image")
    {
        $url = $imageSrc['pathInfo'];
        $data['media'] = new \CURLFile($url);
        $wechat = new Wechat;
        $imagesInfo = $wechat->uploadMedia($data, $type);

        $condition =  array('id'    => $imageSrc['poster_id']);

        // 上传失败
        if(false === $imagesInfo) {
            $error = $wechat->error();

            $data = array(
                'wechat_error_code'     => $error['errCode'],
                'wechat_error_message'  => $error['errMsg'],
            );
            D('Poster', 'Model')->posterUpdate($condition, $data);
            exit('海报制作失败!请重新生成或者联系客服人员');
        }

        //上传成功
        $data = array(
            'wechat_upload_status'  => 1,
            'wechat_upload_type'    => $imagesInfo['type'],
            'wechat_media_id'       => $imagesInfo['media_id'],
            'wechat_upload_start_time'  => $imagesInfo['created_at'],
            'wechat_upload_end_time'    => $imagesInfo['created_at'] + C('UPLOAD_WECHAT_TIME')
        );

        $bool = D('Poster', 'Model')->posterUpdate($condition, $data);

        if(!$bool) {
            exit('海报制作失败!请重新生成或者联系客服人员');
        }

        return array(
            'media_id'   => $imagesInfo['media_id'],
            'start_time' => $data['wechat_upload_start_time'],
            'end_time'   => $imageSrc['poster_end_time']
        );
    }

    /**
     * 发送消息
     * @param $userInfo     用户信息
     * @param $media_id     微信图片id
     */
    private function _sendWechat($userInfo, $mediaInfo)
    {
        $wechat = new Wechat;

        $image = array(
            'touser'    =>  $userInfo['user_wechatopenid'],
            'msgtype'   =>  'image',
            'image'     =>  array('media_id' => $mediaInfo['media_id'])
        );

        $wechat->sendCustomMessage($image);
        $start = date('Y-m-d H:i:s', $mediaInfo['start_time']);
        $end   = date('Y-m-d H:i:s', $mediaInfo['end_time']);
        $content = <<<STR
海报生效时间：
$start
海报失效时间：
$end
STR;

        $text = array(
            'touser'    =>  $userInfo['user_wechatopenid'],
            'msgtype'   =>  'text',
            'text'      =>  array(
                'content' => $content
            )
        );

        $wechat->sendCustomMessage($text);
    }

}