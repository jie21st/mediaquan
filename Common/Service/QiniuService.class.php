<?php
/**
 * 七牛文件类
 */
namespace Common\Service;

class QiniuService {
    /**
     * 鉴权对象
     * @var object 
     */
    protected $auth;
    
    /**
     * 上传空间名称
     * @var string 
     */
    protected $bucket;

    /**
     * 构造方法
     */
    public function __construct() {
        require_once VENDOR_PATH . 'Qiniu/autoload.php';
        $setting = (new \Common\Model\SettingModel())->get('remote');
        if (empty($setting)) {
            trigger_error('配置不正确', E_USER_ERROR);
        }
        
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($setting['access_key'], $setting['secret_key']);
        
        $this->auth = $auth;
        $this->bucket = $setting['bucket'];
    }
    
    /**
     * 文件上传
     * 
     * @param type $filepath 文件路径
     * @param type $filename 文件名称（上传到七牛后保存的文件名）
     * @return mixed 成功返回结果，失败返回false
     */
    public function upload($filepath, $filename)
    {
        // 生成上传Token
        $token = $this->auth->uploadToken($this->bucket);
        
        // 构建 UploadManager 对象
        $uploadMgr = new \Qiniu\Storage\UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $filename, $filepath);
        
        if ($err !== null) {
            return false;
        } else {
            return $ret;
        }
    }
    
    /**
     * 文件删除
     * 
     * @param string $filename 文件名称（上传到七牛后保存的文件名）
     * @return boolean
     */
    public function delete($filename)
    {
        //初始化BucketManager
        $bucketMgr = new \Qiniu\Storage\BucketManager($this->auth);
        $err = $bucketMgr->delete($this->bucket, $filename);
        return $err === null ? true : false;
    }
}
