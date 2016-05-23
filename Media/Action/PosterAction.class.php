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
    public function getPosterOp($uid)
    {
        // 用户信息
        //$uid = session('user_id');
        $userInfo = D('User', 'Service')->getUserBaseInfo($uid);

        if (empty($userInfo)) {
            return $this->returnJson(0, '获取用户信息失败', '');
        }

//        if ($userInfo['buy_num'] == 0) {
//            //$text = '<a href="'.C('MEDIA_SITE_URL').'">购买课程</a>';
//            //$this->_sendText($userInfo, $text);
//            //你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。立即点击“成为东家”
//            exit('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="'.C('MEDIA_SITE_URL').'">立即点击“成为东家”</a>');
//        }

        $posterInfo = $this->getUserPosterInfo($uid);

        if(time() > $posterInfo['poster_end_time']) {
            //echo '重新制作';
            //制作海报
            $imageSrc   = $this->_getImageInfo($userInfo);
            if(false === $imageSrc) {
                return $this->returnJson(0, '制作海报失败', '');
            }
            //微信上传
            $mediaInfo  = $this->_uploadMedia($imageSrc, 'image');
            if(false === $mediaInfo) {
                return $this->returnJson(0, '微信上传失败', '');
            }
        } else if(time() > $posterInfo['wechat_upload_end_time']) {
            //echo '微信上传';
            $imageSrc['pathInfo']   = $posterInfo['poster_src'];
            $imageSrc['poster_id']  = $posterInfo['id'];
            $imageSrc['pathName']   = $posterInfo['poster_images_name'];
            $imageSrc['poster_end_time']  = $posterInfo['poster_end_time'];
            //微信上传
            $mediaInfo = $this->_uploadMedia($imageSrc, 'image');
            if(false === $mediaInfo) {
                return $this->returnJson(0, '微信上传失败', '');
            }
        } else {
            //echo '无变化';
            $mediaInfo['media_id']      = $posterInfo['wechat_media_id'];
            $mediaInfo['end_time']      = $posterInfo['poster_end_time'];
            $mediaInfo['start_time']    = $posterInfo['poster_create_time'];
            $imageSrc['pathName']       = $posterInfo['poster_images_name'];
        }

        //发送消息
        $sendBool = $this->_sendWechat($userInfo, $mediaInfo);

        if ( false === $sendBool) {
            return $this->returnJson(0, '发送消息失败', '');
        }

        return $this->returnJson(1, 'success', '');
        //$this->assign('imageSrc', $imageSrc['pathName']);
        //$this->display();
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

        //　计算海报，二维码时效
        $todayStartTime = time();
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $todayEndTime = mktime(23, 59, 59, $m, $d, $y) - $todayStartTime;
        $wechatTime = 29 * 86400 + $todayEndTime;
        $imageSrc = $poster->getPoster($userInfo, $wechatTime, $todayStartTime);


        if(false === $imageSrc) {
            return false;
        }

        $data = array(
            'user_id' => $userInfo['user_id'],
            'poster_images_name' => $imageSrc['pathName'],
            'poster_src' => $imageSrc['pathInfo'],
            'poster_create_time' => $todayStartTime,
            'poster_end_time' => ($todayStartTime + $wechatTime),
            'poster_status' => ($imageSrc['pathName'] != '') ? 1 : 0,
        );

        $state = D('Poster', 'Model')->addData($data);

        if(!$state) {
            return false;
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
            return false;
//            $error = $wechat->error();
//
//            $data = array(
//                'wechat_error_code'     => $error['errCode'],
//                'wechat_error_message'  => $error['errMsg'],
//            );
//            D('Poster', 'Model')->posterUpdate($condition, $data);
//            exit('海报制作失败!请重新生成或者联系客服人员');
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
            return false;
//            exit('海报制作失败!请重新生成或者联系客服人员');
        }

        return array(
            'media_id'   => $imagesInfo['media_id'],
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
        return $wechat->sendCustomMessage($image);
    }

//    private function _sendText($userInfo, $text)
//    {
//        $wechat = new Wechat;
//        $text = array(
//            'touser'    =>  $userInfo['user_wechatopenid'],
//            'msgtype'   =>  'text',
//            'text'     =>  array('content' => $text)
//        );
//
//        $wechat->sendCustomMessage($text);
//    }

}
