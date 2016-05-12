<?php
/**
 * 手机版配置 - 生产环境配置
 * '配置项'=>'配置值'
 */
$config = array(
    // cookie
    'COOKIE_DOMAIN'     => 'test.mediaquan.com',
    
    // session
    'SESSION_TYPE'      => 'Redis',
    'SESSION_PREFIX'    => 'mediaquan:session',
    
    // URL设置
    'URL_ROUTER_ON'     => true,
    'URL_ROUTE_RULES'   => array(
        // 课程
        'class/:id\d'       => 'class/index',
        // 订单
        'order/:id\d'       => 'class/buy',
        // 单页面
        'benefit'           => 'page/benefit',
        '/^z\/(.*)$/'       => 'page/:1',
        // 训练营
        'camp/:camp_id\d'   => 'camp/join',
    ),
    
    // 海报时效
    "POSTER_TIME" => 2592000,
    "UPLOAD_WECHAT_TIME" => 25920,
    
    // 项目配置
    'APP_SITE_URL'   => 'http://test.mediaquan.com', // 当前项目地址，手机版用java和php编写 在php中使用此地址为（java 为mobile_site_url, php为app_site_url）
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

