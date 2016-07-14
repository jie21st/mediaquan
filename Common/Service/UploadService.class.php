<?php
namespace Common\Service;
//use Think\Action;
class UploadService //extends Action
{
	// 上传操作
	public function uploads($conf = array())
	{
		$config = array(
			//3145728
		    'maxSize'    =>    3145728,
		    'rootPath'   =>    './Uploads/Images/',
		    'savePath'   =>    '',
		    'saveName'   =>    array('uniqid',''),
		    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg', 'mp3'),
		    'autoSub'    =>    true,
		    'subName'    =>    array('date','Ymd'),
		);
		if (! empty($conf)) $config = array_merge($config, $conf);
		$path = realpath('./') . $config['rootPath'];
		if(! is_dir($path)) mkdir($path, 0775, true);

		$upload = new \Think\Upload($config);// 实例化上传类

			$info   =   $upload->upload();
			if(!$info) {// 上传错误提示错误信息
				$this->error($upload->getError());
			}else{// 上传成功 获取上传文件信息
			foreach ($info as $key => $value) {
				$path = $config['rootPath'] . $value['savepath'] . $value['savename'];
			}
			return ltrim($path,'.');
		}
	}

	public function imgUpload($conf)
	{
		$config = array(
			//3145728
		    'maxSize'    =>    3145728,
		    'rootPath'   =>    './Uploads/Images/',
		    'savePath'   =>    '',
		    'saveName'   =>    array('uniqid',''),
		    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg', 'mp3'),
		    'autoSub'    =>    true,
		    'subName'    =>    array('date','Ymd'),
		);

		if (! empty($conf)) $config = array_merge($config, $conf);

		$upload = new \Think\Upload($config);// 实例化上传类
        $info   =   $upload->upload();
        $data = [];
        $message = '';
        if(!$info) {// 上传错误提示错误信息
        	return array('error'=>$upload->getError());
        }else{// 上传成功 获取上传文件信息
        	return $info;
        }
	}
}
