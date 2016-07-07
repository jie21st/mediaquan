<?php
namespace Common\Model;

/**
 * 系统设置模型
 */
class SettingModel extends \Think\Model
{
    protected $tableName = 'settings';
    
    /**
     * 取得设置项
     * 
     * @param type $name
     * @return type
     */
    public function get($name)
    {
        $result = $this->where(['name' => $name])->getField('value');
        if ($result) {
            if (is_serialized($result)) {
                $result = unserialize($result);
            }
        }
        
        return $result;
    }
    
    /**
     * 设置
     * 
     * @param type $name
     * @param type $value
     * @return type
     */
    public function set($name, $value = '')
    {
        $data = array();
        $data['name'] = $name;
        if (is_array($value)) {
            $value = serialize($value);
        }
        $data['value'] = $value;
        return $this->add($data, [], true);
    }
}
