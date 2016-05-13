<?php
namespace Common\Model;

class PredepositModel extends CommonModel
{
    protected $tableName = 'pd_cash';
    
    /**
     * 添加充值记录
     * 
     * @param type $data
     */
    public function addPdRecharge($data)
    {
        return (new \Think\Model())->table('m_pd_recharge')->add($data);
    }
    
    /**
     * 添加提现记录
     * 
     * @param array $data
     */
    public function addPdCash($data)
    {
        return $this->add($data);
    }
    
    /**
     * 编辑提现记录
     * 
     * @param unknown $data
     * @param unknown $condition
     */
    public function editPdCash($data,$condition = array()) {
        return $this->where($condition)->save($data);
    }
    
    /**
     * 编辑充值记录
     * 
     * @param type $data
     * @param type $condition
     * @return type
     */
    public function editPdRecharge($data, $condition = array())
    {
        return (new \Think\Model())->table('m_pd_recharge')->where($condition)->save($data);
    }
    
    /**
     * 删除提现记录
     * 
     * @param unknown $condition
     */
    public function delPdCash($condition) {
        return $this->where($condition)->delete();
    }
    
    /**
     * 取得单条充值信息
     * 
     * @param unknown $condition
     * @param string $fields
     */
    public function getPdRechargeInfo($condition = array(), $fields = '') {
        return (new \Think\Model())->table('m_pd_recharge')->field($fields)->where($condition)->find();
    }
    
    /**
     * 取得单条提现信息
     * 
     * @param unknown $condition
     * @param string $fields
     */
    public function getPdCashInfo($condition = array(), $fields = '*') {
        return $this->where($condition)->field($fields)->find();
    }
    
    /**
     * 取提现单信息总数
     * 
     * @param unknown $condition
     */
    public function getPdCashCount($condition = array()) {
        return $this->where($condition)->count();
    }
    
    /**
     * 取提现单信息总额
     * 
     * @param type $condition
     * @return type
     */
    public function getPdCashSum($condition = array())
    {
        return $this->where($condition)->sum('pdc_amount');
    }
    
    /**
     * 取得提现列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $fields
     * @param string $order
     * @return type
     */
    public function getPdCashList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        return (new \Think\Model())->table('m_pd_cash')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
    }
    
    /**
     * 取得预存款变更日志总金额金额
     * 
     * @param type $condition
     * @param type $field
     * @return type
     */
    public function getPdLogAmountSum($condition = array(), $field = 'lg_av_amount') {
        return (new \Think\Model())->table('glzh_pd_log')->where($condition)->sum($field);
    }
    
    /**
     * 取得预存款变更日志列表
     * 
     * @param type $condition
     * @param type $fields
     * @param type $order
     * @param type $page
     * @param type $limit
     * @return type
     */
    public function getPdLogList($condition = array(), $fields = '', $order = '', $page = 1, $limit = 100) {
        return (new \Think\Model())->table('glzh_pd_log')->field($fields)->where($condition)->order($order)->limit($limit)->page($page)->select();
    }
}
