<?php
namespace Common\Model;

/**
 * 店铺粉丝模型
 */
class FansModel extends CommonModel
{
    protected $tableName = 'store_wechat_fans';
    
    public function getFansInfo($condition, $field = '')
    {
        return $this->field($field)->where($condition)->find();
    }
}
