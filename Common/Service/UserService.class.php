<?php
namespace Common\Service;

/**
 * 用户服务类
 */
class UserService
{
    /**
     * 获取用户详细信息
     *
     * @param mixed $uid
     * @access public
     * @return void
     */
    public function getUserInfo($uid) {
        static $cache = [];
        if (! isset($cache[$uid])) {
            $cache[$uid] = $this->getUserBaseInfo($uid);
        }
        return $cache[$uid];
    }

    /**
     * 获取用户基本信息 
     * 
     * @param mixed $userId 
     * @access public
     * @return void
     */
    public function getUserBaseInfo($userId)
    {
        $model = new \Common\Model\UserModel;
        $field = 'user_id,user_wechatopenid,user_nickname,user_truename,user_sex,user_mobile,user_wx,user_avatar,parent_id,buy_num';
        $data = $model->getUserInfo(['user_id' => $userId], $field);
        return $data;
    }

    /**
     * 获取用户全部信息 
     * 
     * @param mixed $userId 
     * @access public
     * @return void
     */
    public function getUserFullInfo($userId)
    {
        $model = D('User');
        return $model->getUserInfo(['user_id' => $userId], '*');
    }

    /**
     * 更新用户信息和缓存 
     * 
     * @param mixed $data 
     * @param mixed $userId 
     * @access public
     * @return boolean
     */
    public function updateUserInfo($data, $userId)
    {
        // 更新用户数据表
        $userModel = new \Common\Model\UserModel();
        $result = $userModel->editUser($data, ['user_id' => $userId]);
        if ($result) {
            // 更新用户缓存信息
            $this->updateUserInfoCache($userId);
            return true;
        }
        return ($result === false) ? false : true;
    }
    
    /**
     * 更新用户缓存信息
     * 
     * @param mixed $uid 
     * @access public
     * @return void
     */
    public function updateUserInfoCache($uid)
    {
        return;
    }

    public function getUserDetail($userId)
    {
        $model = D('User');
        return $model->getUserByUid($userId);
    }
}
