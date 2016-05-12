<?php

namespace Media\Action;

use Common\Service\ImagesMerger as Images;

class PosterAction extends CommonAction
{

    public function getPosterOp()
    {
        $uid = session('user_id');
        $url = $this->_getImageInfo($uid);
        $mediaId = $this->_uploadMedia($url);
        $this->_sendWechat($mediaId);
    }
    
    private function _getImageInfo($uid)
    {
        $poster = D('Poster', 'Service');
        $poster->getPoster($uid);
    }

    private function _uploadMedia()
    {

    }

    private function _sendWechat()
    {

    }
}