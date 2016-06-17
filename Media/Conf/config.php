<?php
/**
 * 手机版配置 - 生产环境配置
 * '配置项'=>'配置值'
 */
$config = array(
    // session
    'SESSION_TYPE'      => 'Redis',
    'SESSION_PREFIX'    => 'mediaquan:session',
    'SESSION_EXPIRE'    => 259200,
    
    // URL设置
    'URL_ROUTER_ON'     => true,
    'URL_ROUTE_RULES'   => array(
        // 课程
        'class/:class_id/:chapter_id'  => 'chapter/attend?class_id=:1&chapter_id=:2',
        'class/:id\d'       => 'class/detail',
        // 订单
        'order/:id\d'       => 'class/buy',
        // 文章
        'article/:id\d'     => 'article/detail',
        // 单页面
        'contact'           => 'index/contact',
        'poster'            => 'index/poster',
        'muzhiweike'        => 'index/muzhiweike',
        // 测试
        'test/:appid'       => 'test2/receive',
    ),
    
    // 海报时效
    "POSTER_TIME" => 2592000,
    "UPLOAD_WECHAT_TIME" => 259200,
    
    // 项目配置
    'APP_SITE_URL'   => 'http://www.mediaquan.com', // 当前项目地址，手机版用java和php编写 在php中使用此地址为（java 为mobile_site_url, php为app_site_url）
);

// 加载调试配置文件
if (APP_DEBUG === true) {
    $path = MODULE_PATH . 'Conf/debug.php';
    if (file_exists($path)) {
        $debug = include $path;
        return array_merge($config, $debug);
    }
}

return $config;
