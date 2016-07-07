<?php
/**
 * 通行证模块初始化文件
 */
define('APP_PATH','./');
define('APP_DEBUG', true);  // 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('BUILD_DIR_SECURE', false);
define('BIND_MODULE', 'Wechat');
if (! include(dirname(__FILE__).'/global.php')) {
    exit('global.php isn\'t exists!');
}
require THINK_PATH.'ThinkPHP.php';
