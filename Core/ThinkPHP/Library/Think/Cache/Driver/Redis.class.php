<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Cache\Driver;
use Think\Cache;
defined('THINK_PATH') or exit();

/**
 * Redis缓存驱动 
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
class Redis extends Cache {
	 /**
	 * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options=array()) {
        if ( !extension_loaded('redis') ) {
            E(L('_NOT_SUPPORT_').':redis');
        }
        $options = array_merge(array (
            'host'          => C('REDIS_HOST') ? : '127.0.0.1',
            'port'          => C('REDIS_PORT') ? : 6379,
            'password'      => C('REDIS_PASS') ? : '',
            'timeout'       => C('DATA_CACHE_TIMEOUT') ? : false,
            'persistent'    => false,
        ),$options);

        $this->options =  $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');        
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;        
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler  = new \Redis;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
        $this->handler->auth($options['password']);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        N('cache_read',1);
        $value = $this->handler->get($this->options['prefix'].$name);
        $jsonData  = json_decode( $value, true );
        return ($jsonData === NULL) ? $value : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $name   =   $this->options['prefix'].$name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value  =  (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if(is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        if($result && $this->options['length']>0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }
     
    /**
     * hSet 
     * 
     * @param string $name 
     * @param mixed $key 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function hSet($name, $key, $value = '') {
        $name = $this->options['prefix'].$name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_array($value) || is_object($value)) ? json_encode($value) : $value;
        if (is_array($key)) {
            foreach($key as $k => $v) {
                $result = $this->handler->hSet($name, $k, $v);
            }
        } else {
            $result = $this->handler->hSet($name, $key, $value);
        }
        return $result;
    }

    public function hGet($name, $key) {
        $value = $this->handler->hGet($this->options['prefix'].$name, $key);
        return $value;
    }

    public function hGetAll($name) {
        return $this->handler->hGetAll($this->options['prefix'].$name);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name) {
        return $this->handler->delete($this->options['prefix'].$name);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return $this->handler->flushDB();
    }
    
    public function zAdd($key, $score, $member) {
        $key = $this->options['prefix'].$key;
        return $this->handler->zAdd($key, $score, $member);
    }
    
    /**
     * 为有序集 key 的成员 member 的 score 值加上增量 increment
     * 
     * @param type $key
     * @param type $increment
     * @param type $member
     * @return type
     */
    public function zIncrBy($key, $increment, $member) {
        $key = $this->options['prefix'].$key;
        return $this->handler->zIncrBy($key, $increment, $member);
    }
    
    /**
     * 返回有序集 key 中，指定区间内的成员
     * 
     * @param type $key
     * @param type $start
     * @param type $stop
     */
    public function zRevRange($key, $start = 0, $stop = -1, $withscores = false)
    {
        $key = $this->options['prefix'].$key;
        return $this->handler->zRevRange($key, $start, $stop, $withscores);
    }
}
