<?php
/**
 * Created by PhpStorm.
 * User: liyi
 * Date: 16/7/14
 * Time: 下午6:53
 * ps:七牛上传类
 */
namespace Common\Service;
Vendor('qiniu.autoload');
use \Qiniu\Auth;
use \Qiniu\Storage\UploadManager;
class QiniuService {
	public function __construct(){


	}

	public function  initialize(){
		$accessKey = 'CjaDbgNH6MjjulwpFSyfPAFv-MUkGH2BOM2noLC-';
		$secretKey = 'kuFL_eo7yB8c60q1vdf9TiTNOB22UIqIkRHZEZs2';
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'test';
		$token = $auth->uploadToken($bucket);
	}
	public function  get() {
		echo 'aa';
	}
}