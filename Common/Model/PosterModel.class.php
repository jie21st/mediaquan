<?php
namespace Common\Model;

class PosterModel extends CommonModel
{
    /**
     * 真实数据表名称
     *
     * @var string
     * @access protected
     */
    protected $trueTableName = 'm_poster';


    /**
     * 获取最新海报
     * @param $condition
     *
     * @return mixed
     */
    public function getUserPoster($condition)
    {
        return $this->where($condition)->order('id desc')->find();
    }

    /**
     * 添加海报信息
     * @param $data
     *
     * @return mixed
     */
    public function addData($data)
    {
        return $this->data($data)->add();
    }

    /**
     * 更新信息
     * @param $condition
     * @param $data
     *
     * @return mixed
     */
    public function posterUpdate($condition, $data)
    {
        return $this->where($condition)->save($data);
    }
}