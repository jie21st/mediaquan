<?php
/**
 * 任务计划执行入口
 */
define('APP_PATH',dirname(dirname(__FILE__)) . '/');
define('BIND_MODULE', 'Crontab');
define('MODE_NAME', 'cli'); // 采用CLI运行模式运行
if (! include(dirname(dirname(__FILE__)).'/global.php')) {
    exit('global.php isn\'t exists!');
}
define('APP_DEBUG', true);  // 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
require THINK_PATH.'ThinkPHP.php';



