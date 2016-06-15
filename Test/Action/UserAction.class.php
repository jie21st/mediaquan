<?php
namespace Test\Action;

class UserAction extends \Think\Action
{
    public function bindParentOp()
    {
        $us = new \Common\Service\UserService;
        $result = $us->bindParent(10012, 10013);
        print_r($result);
    }
    
    public function getUserInfoOp()
    {
        $us = new \Common\Service\UserService;
        $result = $us->getUserInfo(10000);
        print_r($result);
    }
    
    public function testOp()
    {
        $cs = new \Common\Service\ClassService();
        //$parents = $cs->getUserParents(10047, 3);
        //print_r($parents);
        G('begin');
        $fans = $this->getUserFansLevel3(10000);
        print_r($fans);
        foreach ($fans[1000] as $item) {
            
        }
        echo G('begin', 'end').'s';
    }
    
    /**
     * [
     *      [
     *          
     *      ]
     *      20000,
     *      
     * ]
     * @staticvar array $list
     * @param type $userId
     * @param type $level
     * @return type
     */
    
    public function getUserFansLevel3($userId) {
        $list=array();
        $userModel = new \Common\Model\UserModel;
        $users = $userModel->where(['parent_id' => $userId])->select();
        foreach ($users as $user) {
            $list[$userId][] = ['user_id' =>$user['user_id'], 'nickname'=>$user['user_nickname']];
            $result = $userModel->where(['parent_id' => $user['user_id']])->select();
            foreach ($result as $value) {
                $list[$user['user_id']][] = ['user_id' => $value['user_id'], 'nickname'=>$value['user_nickname']];
                $result3 = $userModel->where(['parent_id' => $value['user_id']])->select();
                foreach ($result3 as $value3) {
                    $list[$value['user_id']][] = ['user_id' => $value3['user_id'], 'nickname'=>$value3['user_nickname']];
                }
            }
        }
        return $list;
    }
}