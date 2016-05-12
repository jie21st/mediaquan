<?php
/**
 * 图片合并类
 * @Author: zenghp
 * @Date:   2015-12-16 11:54:06
 * @Last Modified by:   zenghp
 * @Last Modified time: 2016-03-21 11:12:02
 */
namespace Common\Service;

class ImagesMerger
{
    // 目标图资源
    private $dstImage = '';
    // 原图资源
    private $srcImage = [];
    public $pathInfo;
    // 默认配置项
    private $config = array(
        'dst'       =>  '', 		// 模板地址(目标图)
        'isPrint'   =>  true,		// 是否打印
        'isSave'    =>  false, 		// 是否保存
        'savePath'  =>  '/', 		// 保存路径
        'saveName'  =>  '', 		// 保存名字
        // 缩略图
        'src' => array(
            array(
                'srcPath'   =>  '',  // 图片路径
                'srcX'      =>  '0', // X轴位置
                'srcY'      =>  '0', // Y轴位置
                'srcW'      =>  '0', // 图片宽度
                'srcH'      =>  '0', // 图片高度
            ),
        ),
        // 文字
        'font' => array(
            // array(
            // 'text'      => '欢迎关注包子堂', 	// 字体路径
            // 'fontPath'  => '', 					// 字体路径
            // 'fontSize'  => '14', 				// 字体大小
            // 'fontColor' => '0,0,0', 			// 字体颜色
            // 'fontX'     => '0', 				// X轴位置 支持center(自动居中)
            // 'fontY'     => '0',					// Y轴位置
            // 'adjust'    => '0' 					// 位置调整
            // ),
        ),
    );

    /**
     * 初始化
     * @param array $config 配置项
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        foreach ($this->config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * 运行
     * @return void
     */
    public function start()
    {
        if ($this->dst) {
            $this->dstImage = imagecreatefromstring(file_get_contents($this->dst));
            list($this->width,  , $type) = getimagesize($this->dst);
        } else {
            return array('status'=> '0', 'message' => 'resource not found');
        }

        if(! empty($this->src) and is_array($this->src)) {
            $this->_createImages();
        }

        if(! empty($this->font) and is_array($this->font)) {
            $this->_createText();
        }

        if($this->isSave === true and !empty($type)) {
            $this->saveImage($this->dstImage, $type);
        }

        if($this->isPrint === true and !empty($type)) {
            $this->printImage($this->dstImage, $type);
        }
    }

    /**
     * 图片水印
     * @return void
     */
    private function _createImages()
    {
        foreach ($this->src as $key => $value) {
            $srcImage = imagecreatefromstring(file_get_contents($value['srcPath']));
            // 获取资源图片的信息
            list($width, $height) = getimagesize($value['srcPath']);
            /* 处理图片 */
            imagecopyresized($this->dstImage, $srcImage, $value['srcX'], $value['srcY'], 0, 0, $value['srcW'], $value['srcH'], $width, $height);
        }
    }

    /**
     * 文字水印
     * @return void
     */
    private function _createText()
    {
        foreach ($this->font as $key => $value) {
            list($red, $green, $blue) = explode(',', $value['fontColor']);
            $background = imagecolorallocate($this->dstImage, $red, $green, $blue);
            // 字体居中
            if ($value['fontX'] == 'center') {
                $fontBOx = imagettfbbox($value['fontSize'], 0, $value['fontPath'], $value['text']);
                $value['fontX']  = ceil(($this->width - $fontBOx['2'])/2 - $value['adjust']);
            }

            imagefttext($this->dstImage, $value['fontSize'], 0, $value['fontX'], $value['fontY'], $background, $value['fontPath'], $value['text']);
        }
    }

    /**
     * 打印
     * @param  string 	$dstImage 图片资源
     * @param  int 		$type     图片类型
     * @return print
     */
    private function printImage($dstImage, $type)
    {
        // 1 = GIF，2 = JPG，3 = PNG
        switch ($type) {
            case '1':
                header('Content-Type: image/gif');
                imagegif($dstImage);
                break;
            case '2':
                header('Content-Type: image/jpeg');
                imagejpeg($dstImage);
                exit();
                break;
            case '3' :
                header('Content-Type, image/png');
                imagepng($dstImagel);
                break;
        }
    }

    /**
     * 保存
     * @param  string 	$dstImage 图片资源
     * @param  int 		$type     图片类型
     * @return string             $this->savePath 图片路径
     */
    private function saveImage($dstImage, $type)
    {
        if (! $this->saveName)
            $this->saveName = date('YmdHis');

        if (! is_dir($this->savePath))
            mk_dir($this->savePath, 0755);

        $path = $this->savePath . $this->saveName;

        // 1 = GIF，2 = JPG，3 = PNG
        switch ($type) {
            case '1':
                $bool = imagegif($dstImage, $path . '.gif');
                $this->pathInfo = $path . '.gif';
                $this->pathName =  $this->saveName . '.gif';
                break;

            case '2':
                $bool = imagejpeg($dstImage, $path . '.jpg');
                $this->pathInfo = $path . '.jpg';
                $this->pathName = $this->saveName . '.jpg';
                break;

            case '3' :
                $bool = imagepng($dstImage, $path . '.png');
                $this->pathInfo = $path . '.png';
                $this->pathName = $this->saveName . '.png';
                break;
        }
    }

    // 关闭资源
    public function __destruct()
    {
        imagedestroy($this->dst);
        imagedestroy($this->src);
    }

}
