<?php
/**
 * 递归复制 
 * 
 * @param type $src
 * @param type $dst
 * @return boolean
 */
function recurse_copy($src, $dst) { 
    if (is_dir($src)) {
        $dir = opendir($src); 
        mk_dir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    recurse_copy($src . '/' . $file, $dst . '/' . $file); 
                } else { 
                    echo '<p>复制文件'.$src . '/' . $file .'到'.$dst . '/' . $file.'</p>';
                    copy($src . '/' . $file,$dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir);
        return true;
    } elseif (is_file) {
        return copy($src, $dst);
    } else {
        return false; 
    }
} 

/**
 * 递归创建目录 
 * 
 * @param mixed $dir 
 * @param int $mode 
 * @access public
 * @return void
 */
function mk_dir($dir, $mode = 0777) {
    if (is_dir($dir) || @mkdir($dir, $mode))
        return true;
    if (!mk_dir(dirname($dir), $mode))
        return false;
    return @mkdir($dir, $mode);
}

/**
 * 授权数据加密解密 
 * 
 * @param mixed $data 
 * @param string $operation 
 * @param string $key 
 * @param mixed $expire 
 * @access public
 * @return void
 */
function authcode($data, $operation = 'DECODE') {
    if ($operation == 'DECODE') {
        return base64_decode($data);
    } else {
        return base64_encode($data);
    }
}

/**
 * 重新格式化已格式化的日期时间
 *
 * @param string $form_format 
 * @param string $date 
 * @param string $to_format 
 * @return void
 */
function date_format_from_format(string $form_format, string $date, string $to_format) {
    $date = DateTime::createFromFormat($form_format, $date);
    if (! $date) {
        return null;
    }
    return $date->format($to_format);
}

/**
 * api接口数据加密/解密
 * 
 * @param string $str       加密数据
 * @param string $operation 编码类型
 * @return array
 */
function api_code($str, $operation = 'DECODE') {
    if ($operation == 'DECODE') {
        $data = base64_decode($str);
        return [
            'data'    => $data,
            'secret'  => md5($data . C('API_ACCESS_ENCODING_KEY')),
        ];
    } else {
        return [
            'code'    => base64_encode($str),
            'secret'  => md5($str . C('API_ACCESS_ENCODING_KEY')),
        ];
    }
}

/**
 * 取得订单支付类型文字输出形式
 *
 * @param array $payment_code
 * @return string
 */
function orderPaymentName($payment_code) {
    return str_replace(
            array('offline','online','alipay','wxpay', 'predeposit', 'agentpay'), 
            array('线下支付','在线付款','支付宝','微信支付', '包子币', '找人代付'), 
            $payment_code);
}

/**
 * 取得课程订单状态文字输出形式
 * 
 * @param array $orderInfo 订单数组
 * @return string $state 描述输出
 */
function orderState($orderInfo) {
    switch ($orderInfo['order_state']) {
        case ORDER_STATE_CANCEL:
            $state = '交易关闭';
            break;
        case ORDER_STATE_NEW:
            $state = '等待付款';
            break;
        case ORDER_STATE_PAY:
            $state = '已付款';
            break;
    }
    
    return $state;
}

/**
 * 价格格式化
 *
 * @param float    $price
 * @return string $price_format
 */
function glzh_price_format($price) {
    $price_format   = number_format($price,2,'.','');
    return $price_format;
}

/**
 * 格式化时间
 * 
 * @param type $time
 * @return string 03:45
 */
function format_time($time) {
    $i = 0;
    $s = intval($time);
    if ($s > 60) {
        $i = floor($s / 60);
        $s = intval($s % 60);
    }
    return str_pad($i, 2, '0', STR_PAD_LEFT) . ':' . str_pad($s, 2, '0', STR_PAD_LEFT);
}

/**
 * 获取地区名称
 * 
 * @param type $code
 * @return type
 */
function getRegionName($code) {
    return M('base_region')->where(array('Specll'=>$code))->getField('RegionName');
}

/**
 * 移除表情
 * 
 * @param type $text
 * @return type
 */
function remove_emoji($text){
      return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
}

/**
 * 这个星期一 
 * 
 * @param int $timestamp 
 * @param mixed $is_return_timestamp 
 * @access public
 * @return void
 */
function this_monday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $monday_date = date('Y-m-d', $timestamp-86400*date('w',$timestamp)+(date('w',$timestamp)>0?86400:-/*6*86400*/518400));
        if($is_return_timestamp){
            $cache[$id] = strtotime($monday_date);
        }else{
            $cache[$id] = $monday_date;
        }
    }
    return $cache[$id];
}

/**
 * 这个星期天 
 * 
 * @param int $timestamp 
 * @param mixed $is_return_timestamp 
 * @access public
 * @return void
 */
function this_sunday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $sunday = this_monday($timestamp) + /*6*86400*/518400;
        if($is_return_timestamp){
            $cache[$id] = $sunday;
        }else{
            $cache[$id] = date('Y-m-d',$sunday);
        }
    }
    return $cache[$id];
}

/**
 * 下载远程图片
 * @param  [type]  $url             [ 图片路径 ]
 * @param  string  $fileName        [ 保存名称 默认md5(time())]
 * @param  string  $fileSaveDirPath [ 保存目录 默认Public目录 按时间 ]
 * @param  string  $fileSaveType    [ 文件后缀 默认原文件后缀 ]
 * @param  integer $type            [ 请求类型 ]
 * @return string                   [ URL ]
 */
function downloadFiles($url, $fileName='', $fileSaveDirPath='', $fileSaveType = '', $type=1)
{
    if ($url == '') return false;
    if ($fileName == '') $fileName = md5(time());
    if ($fileSaveDirPath == '') $fileSaveDirPath = PUBLIC_PATH.date('Y-m-d');
    if (! is_dir($fileSaveDirPath)) mkdir($fileSaveDirPath, 0755, true);

    if ($type){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close();
    } else {
        ob_start();
        readfile($url);
        $file = ob_get_contents();
        ob_end_clean();
    }

    if ($fileSaveType == '') {
        list(, , $type) = getimagesizefromstring($file);
        $fileSaveType = getImagesType($type);
    }
    $filePath = $fileSaveDirPath . '/' .$fileName . '.' . $fileSaveType;
    $fp = fopen($filePath, 'w') or die('Unable to open file!');
    fwrite($fp, $file);
    fclose($fp);
    return $filePath;
}

function getImagesType($type)
{
    //1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
    if (! $type) return false;

    switch ($type) {
        case '1':
            return 'gif';
            break;
        case '2':
            return 'jpg';
            break;
        case '3':
            return 'png';
            break;

    }
}

/**
 * 取上一步来源地址
 *
 * @param
 * @return string 字符串类型的返回结果
 */
function getReferer(){
	return empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
}

function showMessage($msg, $url = '', $type = 'html') {
    // 如果默认为空，则跳转至上一步链接
    $url = ($url !='' ? $url : getReferer());
    
    // 输出类型
    switch ($type) {
        case 'json':
            echo json_encode([
                'msg' => $msg,
                'url' => $url,
            ]);
            break;
        case 'javascript':
            echo "<script>";
            echo "alert('". $msg ."');";
            echo "location.href='". $url ."'";
            echo "</script>";
            break;
        default:
            // html输出形式
            echo $msg;
            break;
    }
    
    exit;
}

/**
 * 判断是否为手机移动终端
 * @return boolean  true or false
 */
function is_mobile_request()  
{  
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';  
    $mobile_browser = '0';

    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))  
        $mobile_browser++;

    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))  
        $mobile_browser++;

    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))  
        $mobile_browser++;

    if(isset($_SERVER['HTTP_PROFILE']))  
        $mobile_browser++;  

    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));  
    $mobile_agents = array(  
        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',  
        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',  
        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',  
        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',  
        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',  
        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',  
        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',  
        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',  
        'wapr','webc','winw','winw','xda','xda-'
    );

    if(in_array($mobile_ua, $mobile_agents))  
        $mobile_browser++;  
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)  
        $mobile_browser++;  
    // Pre-final check to reset everything if the user is on Windows  
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)  
        $mobile_browser=0;  
    // But WP7 is also Windows, with a slightly different characteristic  
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)  
        $mobile_browser++; 

    return ($mobile_browser>0) ? true : false;
}

/**
 * 统计文件数量
 * @param  [type]  $path 目录路径
 * @param  integer $type 1 递归统计 0 当前目录 子目录不统计
 * @return [type]        [description]
 */
function total_file($path = null, $type = 0)
{
    if(is_null($path)) return false;

    static $total = 0;
    $arr = scandir($path);
    foreach ($arr as $key => $value) {
        if($value == '.' or $value == '..') continue;

        if(is_file($path . $value)) {
            $total++;
        }
        if(is_dir($path . $value) and $type == 1) {
            total_file($path . $value .'/', $type);
        }
    }
    return $total;
}

/**
 * 通知消息 内容转换函数
 *
 * @param string $message 内容模板
 * @param array $param 内容参数数组
 * @return string 通知内容
 */
function glzhReplaceText($message,$param){
    if(! is_array($param)) {
        return false;
    }
    $param['send_time']	= date('Y-m-d H:i');
    foreach ($param as $k=>$v){
        $message = str_replace('{$'.$k.'}', $v, $message);
    }

    return $message;
}

/**
 * 取得用户头像图片
 *
 * @param string $member_avatar
 * @return string
 */
function getMemberAvatar($member_avatar){
    if (empty($member_avatar)) {
        return C('UPLOADS_SITE_URL') . DS . ATTACH_COMMON . DS . C('default_user_avatar');
    } else {
        if (file_exists(DIR_UPLOAD . DS . ATTACH_AVATAR . DS . $member_avatar)){
            return C('UPLOADS_SITE_URL') . DS . ATTACH_AVATAR . DS . $member_avatar;
        } else {
            return C('UPLOADS_SITE_URL') . DS . ATTACH_COMMON . DS . C('default_user_avatar');
        }
    }
}

/**
 * 取得分销商推广二维码
 * 
 * @param type $qrcode
 * @return type
 */
function getSellerInviteQrcode($qrcode)
{
    return C('UPLOADS_SITE_URL') . DS . ATTACH_SELLER . DS . $qrcode;
}

/**
 * 编辑器内容
 *
 * @param int $id 编辑器id名称，与name同名
 * @param string $value 编辑器内容
 * @param string $width 宽
 * @param string $height 高
 */
function showEditor($id, $value='', $width='800', $height='400', $media_open=false, $type='all'){
    //是否开启多媒体
    $media = '';
    if ($media_open){
            $media = ", 'flash', 'media'";
    }
    switch($type) {
        case 'basic':
            $items = "['source', '|', 'fullscreen', 'undo', 'redo']";
            break;
        case 'simple':
            $items = "['source', '|', 'fullscreen', 'undo', 'redo', '|',
                'fontfamily', 'fontsize', 'forecolor', 'backcolor', 'bold', 'italic', 'underline',
                'removeformat', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
                'insertunorderedlist', '|', 'emotion', 'simpleupload', 'link']";
            break;
        default:
            $items = "['source', '|', 'fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',
                'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
                'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
                'superscript', '|', 'selectall', 'clearhtml','quickformat','|',
                'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
                'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image'".$media.", 'table', 'hr', 'emoticons', 'link', 'unlink', '|', 'about']";
            break;
    }
    
    echo '<script id="'. $id .'" name="'. $id .'" type="text/plain">'.$value.'</script>';
    echo '
<script type="text/javascript" charset="utf-8" src="'.C('RESOURCE_SITE_URL').'/vendor/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="'.C('RESOURCE_SITE_URL').'/vendor/ueditor/ueditor.all.js"></script>
<script>
    var editor = UE.getEditor("'.$id.'",{
        toolbars: ['.$items.'],
        initialFrameWidth: "'.$width.'",
        initialFrameHeight: "'.$height.'",
        wordCount: false,
        serverUrl: "/editor/index",
    });
</script>';
    return;
}

/**
 * 遍历获取目录下的指定类型的文件
 * 
 * @param $path
 * @param array $files
 * @return array
 */
function getFiles($path, $allowFiles, &$files = array())
{
    if (!is_dir($path)) return null;
    if(substr($path, strlen($path) - 1) != '/') $path .= '/';
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $path2 = $path . $file;
            if (is_dir($path2)) {
                getfiles($path2, $allowFiles, $files);
            } else {
                if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                    $files[] = array(
                        'url'=> substr($path2, strlen(DIR_UPLOAD.DS.ATTACH_EDITOR)),
                        'mtime'=> filemtime($path2)
                    );
                }
            }
        }
    }
    return $files;
}