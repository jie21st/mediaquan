<?php
namespace Common\Model;

class OrderModel extends CommonModel
{
    /**
     * addOrder
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function addOrder($data)
    {
        $data['create_time'] = time();
        return $this->add($data);
    }
    
    /**
     * 插入订单扩展表信息
     * 
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderClass($data) {
        return $this->table('order_goods')->add($data);
    }
    
    /**
     * addOrderPay 
     * 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function addOrderPay($data)
    {
        return (new \Think\Model())->table('m_order_pay')->add($data);
    }
    
    /**
     * 添加订单日志
     */
    public function addOrderLog($data) {
       $data['log_role'] = str_replace(array('buyer','seller','system'),array('买家','商家','系统'), $data['log_role']);
       $data['log_time'] = time();
       return (new \Think\Model())->table('m_order_log')->add($data);
    }
    
    /**
     * 取单条订单信息
     *
     * @param mixed $condition
     * @param string $field
     * @access public
     * @return void
     */
    public function getOrderInfo($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }
    
    
    /**
     * 取得订单列表
     *
     * @param type $condition
     * @param type $field
     * @param type $order
     * @return type
     */
    public function getOrderList($condition = array(), $field = '*', $order = 'order_id desc', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    /**
     * getOrderPayInfo 
     * 
     * @param mixed $condition 
     * @access public
     * @return void
     */
    public function getOrderPayInfo($condition)
    {
        return $this->table('m_order_pay')->where($condition)->find();
    }
    
    /**
     * 更改订单信息
     *
     * @param mixed $data
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function editOrder($data, $condition)
    {
        return $this->where($condition)->save($data);
    }

    /**
     * editOrderPay 
     * 
     * @param mixed $data 
     * @param mixed $condition 
     * @access public
     * @return void
     */
    public function editOrderPay($data, $condition)
    {
        return (new \Think\Model())->table('m_order_pay')->where($condition)->save($data);
    }
}
