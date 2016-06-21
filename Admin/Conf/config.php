<?php

$config = array(
    'APP_SITE_URL'              => 'http://adminbkp.mediaquan.com',
    'ADMIN_LOGIN_KEY'           => 'zAq!xSw@cDe#vFr$BgT%',

    //session
    'SESSION_OPTIONS'           => array('name'=>'zXcVbNdFjK#$@', 'expire'=>3600),
);

if (true === APP_DEBUG) {
    $path = MODULE_PATH . 'Conf/debug.php';
    if (file_exists($path)) {
        return array_merge($config, include $path);
    }
}

return $config;
