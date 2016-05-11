<?php
namespace Think\Session\Driver;

class Redis {
	protected $lifeTime     = 3600;
	protected $sessionName  = '';
	protected $handle       = null;

    /**
     * 打开Session 
     * @access public 
     * @param string $savePath 
     * @param mixed $sessName  
     */
	public function open($savePath, $sessName) {
		$this->lifeTime     = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : $this->lifeTime;
		//$this->sessionName  = $sessName;
        $options            = array(
            'host'          => C('REDIS_HOST') ? : '127.0.0.1',
            'port'          => C('REDIS_PORT') ? : 6379,
            'password'      => C('REDIS_PASS') ? : '',
            'timeout'       => C('SESSION_TIMEOUT') ? C('SESSION_TIMEOUT') : 1,
            'persistent'    => C('SESSION_PERSISTENT') ? C('SESSION_PERSISTENT') : false,
        );
        $func = $options['persistent'] ? 'pconnect' : 'connect';
		$this->handle       = new \Redis;
        $options['timeout'] === false
            ? $this->handle->$func($options['host'], $options['port'])
            : $this->handle->$func($options['host'], $options['port'], $options['timeout']);
        $this->handle->auth($options['password']);
		return true;
	}

    /**
     * 关闭Session 
     * @access public 
     */
	public function close() {
		$this->gc(ini_get('session.gc_maxlifetime'));
		$this->handle->close();
		$this->handle       = null;
		return true;
	}

    /**
     * 读取Session 
     * @access public 
     * @param string $sessID 
     */
	public function read($sessID) {
        $id = C('SESSION_PREFIX') . ':' . $sessID;
        return $this->handle->get($id);
	}

    /**
     * 写入Session 
     * @access public 
     * @param string $sessID 
     * @param String $sessData  
     */
	public function write($sessID, $sessData) {
        $id = C('SESSION_PREFIX') . ':' . $sessID;
		return $this->handle->set($id, $sessData, $this->lifeTime);
	}

    /**
     * 删除Session 
     * @access public 
     * @param string $sessID 
     */
	public function destroy($sessID) {
        $id = C('SESSION_PREFIX') . ':' . $sessID;
		return $this->handle->delete($id);
	}

    /**
     * Session 垃圾回收
     * @access public 
     * @param string $sessMaxLifeTime 
     */
	public function gc($sessMaxLifeTime) {
		return true;
	}
}
