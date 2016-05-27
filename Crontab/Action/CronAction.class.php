<?php
namespace Crontab\Action;

class CronAction extends \Think\Action
{
    /**
     * 初始化对象
     */
    public function __construct()
    {
        parent::__construct();

        register_shutdown_function(array($this, "shutdown"));
    }
    
    /**
     * 执行通用任务
     */
    public function runOp()
    {
        $cronModel = D('cron');
        $cronList = $cronModel->where(['exec_time' => ['elt', time()]])->select();
        
        if (empty($cronList)) {
            return ;
        }
        
        $cronArray = array();
        foreach ($cronList as $cron) {
            $cronArray[$cron['type']][$cron['exec_id']] = $cron;
        }
        
        $cronIds = array();
        foreach ($cronArray as $k => $v) {
            if (! method_exists($this,'cron_'.$k)) {
                $tmp = current($v);
                $cronIds[] = $tmp['id'];
                continue;
            }
            $result = call_user_func_array(array($this,'cron_'.$k), array($v));
            if (is_array($result)){
                $cronIds = array_merge($cronIds, $result);
            }
        }
        // 删除执行完成的cron信息
        if (! empty($cronIds) && is_array($cronIds)){
            $cronModel->where(['id' => ['in', $cronIds]])->delete();
        }
    }
    
    /**
     * 关注10分钟未购买
     * 
     * @param array $cron
     */
    private function cron_1($cron = array())
    {
        $condition = array('user_id' => array('in', array_keys($cron)));
        $result = D('User', 'Service')->notBuyCareNotice($condition);
        if ($result) {
            // 返回执行成功的cronid
            $cronIds = array_reduce($cron, create_function('$v,$w', '$v[] = $w["id"];return $v;'));
        } else {
            return false;
        }
        return $cronIds;
    }
    
    /**
     * 海报未扫码检测
     * 
     * @param type $cron
     */
    private function cron_2($cron = array())
    {
        $condition = array(['id' => ['in', array_keys($cron)]]);
        $result = D('CreatePoster', 'Service')->checkScanNotify($condition);
        if ($result) {
            // 返回执行成功的cronid
            $cronIds = array_reduce($cron, create_function('$v,$w', '$v[] = $w["id"];return $v;'));
        } else {
            return false;
        }
        return $cronIds;
    }
    
    public function shutdown()
    {
        exit("\n" . date('Y-m-d H:i:s') . "\tsuccess");
    }
}