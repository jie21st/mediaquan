<?php
namespace Common\Model;

class PaymentModel extends CommonModel
{
    /**
     *  开启状态标识
     */
    const STATE_OPEN = 1;

    /**
     * 读开启中的取单行信息
     *
     * @param mixed $condition
     * @access public
     * @return void
     */
    public function getPaymentOpenInfo($condition)
    {
        $condition['payment_state'] = self::STATE_OPEN;
        return $this->where($condition)->find();
    }

    /**
     * 更新信息
     *
     * @param array $data 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editPayment($data, $condition)
    {
        return $this->where($condition)->save($data);
    }

    public function getPaymentList($condition = array(), $field = '*')
    {
        return $this->field($field)->where($condition)->select();
    }
}
