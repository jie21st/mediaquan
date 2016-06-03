<?php
namespace Common\Service;

/**
 * 用户服务类
 */
class UserService
{
    protected $redis;
    
    public function __construct()
    {
        $this->redis = \Think\Cache::getInstance('Redis');
    }
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
            $redis = \Think\Cache::getInstance('Redis');
            $name = "user:{$uid}:info";
            $data = $redis->hGetAll($name);
            if (empty($data)) {
                // 读取数据库
                $model = new \Common\Model\UserModel;
                $field = 'user_id,user_nickname,user_truename,user_sex,user_mobile,user_wx,user_avatar,user_wechatopenid,parent_id,buy_num';
                $data = $model->getUserInfo(['user_id' => $uid], $field);
                $redis->hSet($name, $data);// 写入缓存
            }
            $cache[$uid] = $data;
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
    
    /**
     * 未购买关怀通知
     * 
     * @param type $condition
     */
    public function notBuyCareNotice($condition = array()) {
        $userModel = new \Common\Model\UserModel;
        $userList = $userModel->where($condition)->select();
        if (empty($userList)) {
            return true;
        }
        $wechatService = new \Common\Service\WechatService();
        $posterModel = new \Common\Model\PosterModel();
        foreach ($userList as $user) {
            // 判断是否已购买
            if (intval($user['buy_num'])) {
                continue;
            }
            // 查询用户海报
            $poster = $posterModel->getUserPoster(['user_id' => $user['user_id']]);
            if (!empty($poster)) {
                continue;
            }
            
            $wechatService->sendCustomMessage([
                'touser' => $user['user_wechatopenid'],
                'msgtype' => 'text',
                'text' => ['content' => 'Hi，拇指微课不仅有好的课程，还有一些有趣的玩法，<a href="'.C('MEDIA_SITE_URL').'/article/">点击查看使用指南</a>']
            ]);
        }
        
        return true;
    }
    
    /**
     * 绑定推荐人
     * 
     * @param type $userId
     * @param type $parentId
     */
    public function bindParent($userId, $parentId)
    {
        if (intval($parentId) == 0 || $userId == $parentId) {
            return ['error' => '无效数据'];
        }
        
        $userModel = new \Common\Model\UserModel;
        
        $userInfo = $this->getUserInfo($userId);
        if (intval($userInfo['parent_id']) !== 0) {
            return ['error' => '已存在推荐人'];
        }
        
        // 判断该用户有无粉丝。有则不绑定
        $fansList = $userModel->where(['parent_id' => $userId])->find();
        if (!empty($fansList)) {
            return ['error' => '已有自己粉丝，不能成为别人的粉丝'];
        }
        
        $parentInfo = $this->getUserInfo($parentId);
        if (empty($parentInfo)) {
            return ['error' => '推荐人信息不存在'];
        }
        
        if ($parentInfo['parent_id'] == $userId) {
            return ['error' => '两者已存在关系'];
        }
        
        // 绑定关系
        $update = array();
        $update['parent_id'] = $parentId;
        $result = $userModel->editUser($update, ['user_id' => $userId]);
        if (! $result) {
            return ['error' => '更新失败'];
        }
        $this->redis->hSet("user:{$userId}:info", 'parent_id', $parentId);
        $this->redis->zAdd("user:{$parentId}:fans", time(), $userId);
        return true;
    }
}
