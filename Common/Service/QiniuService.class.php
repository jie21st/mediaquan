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
     * 空间名称
     * @var string 
     */
    protected $bucket;
    
    /**
     * 域名
     * @var string
     */
    protected $url;

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
        $this->url = $setting['url'];
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
        static $token = null;
        // 生成上传Token
        if (empty($token)) {
            $token = $this->auth->uploadToken($this->bucket);
        }
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
    
    /**
     * 
     * @param type $filename   course/pdf/123.pdf
     * @param type $savepath   /mnt/www/Static/uploads/course/pdf/123
     * @param type $width
     * @param type $density
     * @param type $quality
     * @return mixed 失败返回false 成功返回['page_num' => 分数, 'list' => 文件列表]
     * course/pdf/123/1.jpg
     */
    public function pdf2jpg($filename, $savepath, $width = 800, $density = 150, $quality = 80)
    {
        $url = $this->url . DS . $filename . '?odconv/jpg/info';
        $urlUtil = new \Org\Util\URL();
        $result = $urlUtil->get_contents($url);
        if (! $result) {
            return false;
        }
        $json = json_decode($result, true);
        if ($json && $json['page_num']) {
            $files = array();
            for ($i=1; $i <= $json['page_num']; $i++) {
                $url = $this->url .DS.$filename.'?odconv/jpg/page/'.$i.'/density/'.$density.'/quality/'.$quality.'/resize/'.$width;
                $content = $urlUtil->get_contents($url);
                
                $name = str_pad($i, 2, "0", STR_PAD_LEFT);
                $newpath = $savepath . DS . $name . '.jpg';
                file_put_contents($newpath, $content);
                $files[] = $name . '.jpg';
            }
            
            return array(
                'page_num' => $json['page_num'],
                'list' => $files
            );
        }
        return false;
    }
}
