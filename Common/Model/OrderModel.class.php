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
