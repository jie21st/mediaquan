<?php
namespace Common\Model;

/**
 * 公共模型类
 */
class CommonModel extends \Think\Model
{

    /**
     * 获取全部数据
     * @param  [type] $condition [description]
     * @return [type]            [description]
     */
    public function getTableAll($condition)
    {
        return $this->where($condition)->select();
    }

    public function getTableFileAll($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->select();
    }

    public function getTablePageLimitAll($condition, $page = 1, $limit = 1000)
    {
        return $this->where($condition)->page($page)->limit($limit)->select();
    }

    public function getTableFiledPageLimitAll($condition, $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->page($page)->limit($limit)->select();
    }

    public function getTableOrderPageLimitAll($condition, $page = 1, $limit = 1000)
    {
        return $this->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    public function getTableFiledOrderPageLimitAll($condition, $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->select();
    }

    /**
     * 获取单条数据
     * @param  [type] $condition [description]
     * @return [type]            [description]
     */
    public function getTableFind($condition)
    {
        return $this->where($condition)->find();
    }

    public function getTableFileFind($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    public function getTablePageLimitFind($condition, $page = 1, $limit = 1000)
    {
        return $this->where($condition)->page($page)->limit($limit)->find();
    }

    public function getTableFiledPageLimitFind($condition, $field = '*', $order = '', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->page($page)->limit($limit)->find();
    }

    public function getTableOrderPageLimitFind($condition, $page = 1, $limit = 1000)
    {
        return $this->where($condition)->order($order)->page($page)->limit($limit)->find();
    }

    public function getTableFiledOrderPageLimitFind($condition, $field = '*', $page = 1, $limit = 1000)
    {
        return $this->field($field)->where($condition)->order($order)->page($page)->limit($limit)->find();
    }

    /**
     * 统计数据
     * @param  [type] $condition [description]
     * @param  string $field     [description]
     * @return [type]            [description]
     */
    public function totalTableAll($condition, $field = '*')
    {
        return $this->where($condition)->count($field);
    }

    /**
     * 添加数据
     */

    /**
     * 更新数据
     */
    
    /**
     * 删除数据
     */

}
