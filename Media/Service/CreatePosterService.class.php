<?php
/**
 * 海报
 */
namespace Media\Service;

use Common\Service\WechatService as Wechat;

class CreatePosterService 
{
    /**
     * 获取海报
     */
    public function getPoster($uid, $fx = false, $is_forever = 0)
    {
        ignore_user_abort(true); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限
        
        $userService = new \Common\Service\UserService;

        // 用户信息
        $userInfo = $userService->getUserBaseInfo($uid);

        if (empty($userInfo)) {
            $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
           // return $this->returnJson(0, '获取用户信息失败', '');
        }

//        if ($userInfo['buy_num'] == 0) {
//            //$text = '<a href="'.C('MEDIA_SITE_URL').'">购买课程</a>';
//            //$this->_sendText($userInfo, $text);
//            //你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。立即点击“成为东家”
//            exit('你还不是东家，不能为您生成二维码海报。只有购买了任意课程，才能成为东家。<a href="'.C('MEDIA_SITE_URL').'">立即点击“成为东家”</a>');
//        }

        $posterInfo = $this->getUserPosterInfo($uid);

        if (empty($posterInfo)) {
            // 首次生成推广海报
            $this->_sendText($userInfo, '正在为您生成海报，大约需要几秒钟，请稍后...');
            
            // 制作海报
            $imageSrc   = $this->_getImageInfo($userInfo, false, $is_forever);
            if(false === $imageSrc) {
                $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
            }
            
            // 微信上传
            $mediaInfo  = $this->_uploadMedia($imageSrc, 'image');
            if(false === $mediaInfo) {
                $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
            }
            
            // 通知推荐人
            if (intval($userInfo['parent_id'])) {
                $parentInfo = $userService->getUserBaseInfo($userInfo['parent_id']);
                $this->_sendText($parentInfo, sprintf(
                            '您的粉丝%s生成了二维码，请给予他协助，<a href="%s">新手教程</a>',
                            $userInfo['user_nickname'],
                            C('MEDIA_SITE_URL').'/article/'
                        ));
            }
            
            // 添加海报扫码情况检测任务
            if (C('SPERAD_POSTER_CHECK_SCAN_TIME') > 0) {
                $cronModel = new \Common\Model\CronModel;
                $exectime = time() + C('SPERAD_POSTER_CHECK_SCAN_TIME');
                $cronModel->addCron(['type' => 2, 'exec_id' => $imageSrc['poster_id'], 'exec_time' => $exectime]);
            }
        } else {
            // 已存在海报
            
            if(time() > $posterInfo['poster_end_time'] && $posterInfo['is_forever'] == 0) {
                //echo '重新制作';
                //制作海报
                $this->_sendText($userInfo, '正在为您生成海报，大约需要几秒钟，请稍后...');
                $imageSrc   = $this->_getImageInfo($userInfo, $posterInfo);
                
                if(false === $imageSrc) {
                    $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
                    //return $this->returnJson(0, '制作海报失败', '');
                }

                //微信上传
                $mediaInfo  = $this->_uploadMedia($imageSrc, 'image');
                if(false === $mediaInfo) {
                    $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
                    //return $this->returnJson(0, '微信上传失败', ''); 
                } 
            } else if(time() > $posterInfo['wechat_upload_end_time']) {
                //echo '微信上传';
                $this->_sendText($userInfo, '正在为您发送海报，请稍后...');

                $imageSrc['pathInfo']   = $posterInfo['poster_src'];
                $imageSrc['poster_id']  = $posterInfo['id'];
                $imageSrc['pathName']   = $posterInfo['poster_images_name'];
                $imageSrc['poster_end_time']  = $posterInfo['poster_end_time'];
                //微信上传
                $mediaInfo = $this->_uploadMedia($imageSrc, 'image');
                if(false === $mediaInfo) {
                    $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
                    //return $this->returnJson(0, '微信上传失败', '');
                }
            } else {
                $this->_sendText($userInfo, '正在为您发送海报，请稍后...');
                //echo '无变化';
                $mediaInfo['media_id']      = $posterInfo['wechat_media_id'];
                $mediaInfo['end_time']      = $posterInfo['poster_end_time'];
                $mediaInfo['start_time']    = $posterInfo['poster_create_time'];
                $imageSrc['pathName']       = $posterInfo['poster_images_name'];
            }
        }

        //发送消息
        $sendBool = $this->_sendWechat($userInfo, $mediaInfo);

        if ( false === $sendBool) {
            $this->_sendText($userInfo, '万分抱歉，海报生成失败，请您重新获取...');exit();
            //return $this->returnJson(0, '发送消息失败', '');
        } else {

            if (true === $fx) {
                return $imageSrc;
            }

        }


        //$this->returnJson(1, 'success', '');
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
    private function _getImageInfo($userInfo, $posterInfo = false, $is_forever = 0)
    {
        $poster = D('Poster', 'Service');

        //　计算海报，二维码时效
        $todayStartTime = time();
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $todayEndTime = mktime(23, 59, 59, $m, $d, $y) - $todayStartTime;
        $wechatTime = 29 * 86400 + $todayEndTime;
        
        $imageSrc = $poster->getPoster($userInfo, $wechatTime, $todayStartTime, $is_forever);

        if(false === $imageSrc) {
            return false;
        }

        $data = array(
            'poster_images_name' => $imageSrc['pathName'],
            'poster_src' => $imageSrc['pathInfo'],
            'poster_create_time' => $todayStartTime,
            'poster_end_time' => ($todayStartTime + $wechatTime),
            'poster_status' => ($imageSrc['pathName'] != '') ? 1 : 0,
            'poster_is_forever' => ($is_forever) : 1 ? 0
        );
        
        if (false !== $posterInfo ) {
            $condition = ['user_id' => $posterInfo['user_id'], 'id' => $posterInfo['id']];
            $bool = D('Poster', 'Model')->posterUpdate($condition, $data);
            if (false == $bool) {
                return false;
            } else {
                $state = $posterInfo['id'];
            }
        } else {
            $data['user_id'] = $userInfo['user_id'];
            $state = D('Poster', 'Model')->addData($data);
        }

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

    private function _sendText($userInfo, $text)
    {
        $wechat = new Wechat;
        $text = array(
            'touser'    =>  $userInfo['user_wechatopenid'],
            'msgtype'   =>  'text',
            'text'     =>  array('content' => $text)
        );

        $wechat->sendCustomMessage($text);
    }
    
    /**
     * 任务检查扫码情况
     * 
     * @param array $condition
     * @return boolean
     */
    public function checkScanNotify($condition = array())
    {
        $condition['poster_from_num'] = 0;
        $posterModel = new \Common\Model\PosterModel;
        $posterList = $posterModel->where($condition)->select();
        if (empty($posterList)) {
            return true;
        }
        $userModel = new \Common\Model\UserModel;
        $content = 'Hi，您的海报还没有粉丝扫码哦，这里有份使用指南，您看看：<a href="'.C('MEDIA_SITE_URL').'/article/">点击阅读</a>';
        foreach ($posterList as $poster) {
            $userInfo = $userModel->getUserInfo(['user_id' => $poster['user_id']], 'user_id, user_wechatopenid');
            $this->_sendText($userInfo, $content);
        }
        return true;
    }
}
