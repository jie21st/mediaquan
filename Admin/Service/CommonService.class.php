<?php
namespace Admin\Service;
use Common\Service\UploadService as uploads;

class CommonService
{
    /**
     * 上传操作
     * @param  array  $config [description]
     * @return [type]         [description]
     */
    public function uploadImages($config = array())
    {
        if(empty($config))
            return false;

        $result = (new uploads)->imgUpload($config);

        if (isset($result['error'])) {
            return array('code'=>'0', 'msg' => $result['error']);
        } else {
            foreach ($result as $key => $value) {
                $data['imagePath'] = $value['savename'];
                $data['siteUrl'] = C('UPLOADS_SITE_URL') .  $config['savePath'];
            }
            return array('code'=>'1', 'msg' => $message, 'data'=>$data);
        }
    }

}