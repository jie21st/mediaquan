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
    'DEFAULT_MODULE'    => 'Media', // 默认模块
    'DEFAULT_C_LAYER'   => 'Action', // 默认的控制器层名称

    // cookie
    'COOKIE_DOMAIN'     => '.mediaquan.com',
//    'COOKIE_EXPIRE'     => 259200, // 3天

    // URL设置
    'URL_MODEL'         => 2, // URL访问模式 0 普通模式, 1 PATHINFO模式(默认), 2 REWRITE模式, 3 兼容模式
    'URL_HTML_SUFFIX'   => 'html',
    
    // 项目配置
    'MEDIA_SITE_URL'   => 'http://test.mediaquan.com',
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
    'DB_PREFIX'         => 'm_', // 数据库表前缀

    // 数据缓存
    'DATA_CACHE_TYPE'   => 'Redis',
    'DATA_CACHE_PREFIX' => 'mediaquan:',
    'REDIS_HOST'        => '127.0.0.1',
    'REDIS_PORT'        => '6319',
    'REDIS_PASS'        => 'f1o2o3b4a5r6e7d8',
    

    // 数据模型
    'READ_DATA_MAP'     => true,

    // 日志记录
    'LOG_TYPE'          => 'file',
    'LOG_RECORD'        => true,
    'LOG_LEVEL'         => 'EMERG,ALERT,CRIT,ERR',
    
    // 微信配置
    'WECHAT_TOKEN'      => '86a8c273c5a0110d49a0dd7c724ac3fc',
    'WECHAT_APPID'      => 'wxd18b1177628b7f9a',
    'WECHAT_APPSECRET'  => '124f73ec548d7b372bf4a612e81753c4',
    'WECHAT_ENCODING'   => 'NJqKhReOkr5JchPCCzVZVFWrlgPRrMT6VxL4Dbs0wbF',
    
    // 推广配置
    'SPREAD_POSTER_USE'                 => true,                 // 推广海报是否启用
    'SPREAD_POSTER_EXPIRE'              => 2592000,              // 推广海报有效期 单位秒）
    'SPERAD_POSTER_GENERATE_NEEDBUY'    => true,                 // 推广生成海报是否需要购买
    'SPERAD_SELLER_GAINS_AMOUNT'        => 0,                    // 推广推荐人获得金额   大于0则对推荐人奖励相应金额，否则不奖励
    'SPERAD_POSTER_CHECK_SCAN_TIME'     => 43200,                // 推广海报生成后多少时间后进行未扫码检测
    
    // 其他配置
    'DEFAULT_USER_AVATAR'       => 'personImg.jpg',         // 默认头像
    'DEFAULT_USER_PARENT'       => array(),                 // 用户默认推荐人列表
    'USER_SUBSCRIBE_CASE_TIME'  => 600,                     // 用户关注多少时间后检查未购买或未生成海报发送通知 为0则不触发
    
    // 分销配置
    'SELLER_LEVEL_RATE'     => array(
        array(100),
        array(80,20),
        array(70,20,10)
    ),
);
