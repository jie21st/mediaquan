<?php
/**
 * 入口文件
 *
 * 统一入口，进行初始化信息
 */

define('GLZH_ROOT_PATH', str_replace('\\','/',dirname(__FILE__)));

define('COMMON_PATH', GLZH_ROOT_PATH . '/Common/');
define('RUNTIME_PATH', GLZH_ROOT_PATH . '/Runtime/');
define('THINK_PATH', GLZH_ROOT_PATH . '/Core/ThinkPHP/');

define('DIR_ADMIN', GLZH_ROOT_PATH . '/Admin');
#define('DIR_API', GLZH_ROOT_PATH . '/Api');
define('DIR_MEDIA', GLZH_ROOT_PATH . '/Media');
define('DIR_RESOURCE', GLZH_ROOT_PATH . '/Static/resource');
define('DIR_UPLOAD', GLZH_ROOT_PATH . '/Static/uploads');

define('DS', '/');
define('ATTACH_COMMON','common');
define('ATTACH_CLASS', 'class');
define('ATTACH_CHAPTER', 'chapter');
define('ATTACH_CLASS_GROUP', 'class/group');  // 班级图片上传目录
define('ATTACH_CLASS_SHARE', 'class/share');  // 课程分享上传目录
define('ATTACH_DIPLOMA', 'class/diploma');
define('ATTACH_EDITOR', 'editor');
define('ATTACH_AVATAR', 'avatar');
#define('ATTACH_CAMP', 'camp');
#define('ATTACH_CAMP_PLACE', 'camp/place');
define('ATTACH_SELLER', 'seller');
define('ATTACH_TEACHER', 'teacher');

define('ATTACH_POSTER', 'poster'); // 海报目录

/**
 * 订单状态
 */
// 已取消
define('ORDER_STATE_CANCEL', '0');
// 已产生但未支付
define('ORDER_STATE_NEW', '10');
// 已支付
define('ORDER_STATE_PAY', '20');
// 订单过期时间，2小时，60*60*2
define('ORDER_EXPIRE', 7200);
