<?php
/**
 * 公共配置 - 生产环境配置
 * '配置项'=>'配置值'
 */
return array(
    // 应用设定
    'ACTION_SUFFIX'     => 'Op', // 操作方法后缀
    'MODULE_DENY_LIST'  => array('Common', 'Runtime'),

    // 默认设定
    'DEFAULT_MODULE'    => 'Mobile', // 默认模块
    'DEFAULT_C_LAYER'   => 'Action', // 默认的控制器层名称

    // cookie
    'COOKIE_DOMAIN'     => '.mediaquan.com',
    'COOKIE_EXPIRE'     => 86400, // 1天

    // URL设置
    'URL_MODEL'         => 2, // URL访问模式 0 普通模式, 1 PATHINFO模式(默认), 2 REWRITE模式, 3 兼容模式
    'URL_HTML_SUFFIX'   => 'html',
    
    // 项目配置
    'MOBILE_SITE_URL'   => 'http://wap.mediaquan.com',
    'WECHAT_SITE_URL'   => 'http://passport.mediaquan.com',
    'RESOURCE_SITE_URL' => 'http://static.mediaquan.com/resource',
    'UPLOADS_SITE_URL'  => 'http://static.mediaquan.com/uploads',

    // 模板引擎设置
    'TMPL_L_DELIM'      => '<{',
    'TMPL_R_DELIM'      => '}>',
    'TMPL_PARSE_STRING' => array(
        'RESOURCE_SITE_URL' => 'http://static.mediaquan.com/resource',
        'UPLOADS_SITE_URL'  => 'http://static.mediaquan.com/uploads',
    ),

    // 数据库设置
    'DB_TYPE'           => 'mysql', // 数据库类型
    'DB_HOST'           => '127.0.0.1', // 服务器地址
    'DB_NAME'           => 'mediaquan', // 数据库名
    'DB_USER'           => 'root', // 用户名
    'DB_PWD'            => 'm1y2s3q4l5', // 密码
    'DB_PORT'           => '3306', // 端口
    'DB_PREFIX'         => '', // 数据库表前缀

    // 数据缓存
    'DATA_CACHE_TYPE'   => 'Redis',
    'REDIS_HOST'        => '10.51.58.34',
    'REDIS_PORT'        => '6319',
    'REDIS_PASS'        => 'f1o2o3b4a5r6e7d8',

    // 数据模型
    'READ_DATA_MAP'     => true,

    // 日志记录
    'LOG_RECORD'        => true,
    'LOG_LEVEL'         => 'EMERG,ALERT,CRIT,ERR',
    
    // 微信配置
    'WECHAT_TOKEN'      => '',
    'WECHAT_APPID'      => 'wxf96a2fbcc0b24037',
    'WECHAT_APPSECRET'  => '05250ace86a17d8ed53d2d24aa748c76',
    
    // 海报
    "POSTER_TIME" => 2592000, // 海报有效期
    
    // 其他配置
    'DEFAULT_USER_AVATAR' => 'personImg.jpg',  // 默认头像
);
