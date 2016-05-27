<?php
namespace Common\Model;

class CronModel extends CommonModel
{
    /**
     * 添加到任务队列
     *
     * @param array $data 任务数据
     * @param boolean $ifdel 是否删除以原记录
     */
    public function addcron($data = array(), $ifdel = false) {
        // 删除原纪录
        if ($ifdel) {
            $this->delCron(['type' => $data['type'], 'exec_id' => $data['exec_id']]);
        }
        return $this->add($data);
    }
    
    /**
     * 取单条任务信息
     * @param array $condition
     */
    public function getCronInfo($condition = array()) {
        return $this->where($condition)->find();
    }
    
    /**
     * 任务队列列表
     * 
     * @param array $condition
     * @param number $limit
     * @return array
     */
    public function getCronList($condition, $limit = 100) {
        return $this->where($condition)->limit($limit)->select();
    }
    
    /**
     * 保存任务队列
     * 
     * @param unknown $insert
     * @return array
     */
    public function addCronAll($insert) {
        return $this->addAll($insert);
    }
    
    /**
     * 删除任务队列
     * 
     * @param array $condition
     * @return array
     */
    public function delCron($condition) {
        return $this->where($condition)->delete();
    }
}

