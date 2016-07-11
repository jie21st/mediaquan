<?php
/**
 * Created by PhpStorm.
 * User: LiYi
 * Date: 16/6/23
 * Time: 上午10:50
 * PS：课程列表相关
 */
namespace Admin\Action;


use Admin\Service\ClassService;

class ClassAction extends CommonAction
{
	/**
	 * 课程列表页
	 */
	public function listOp(){
		$this->display();
	}

	protected $reult = array(
		array('class_id', 'number',  '课程ID必须!', 2, '', 2),
		array('store_id', 'require',  '店铺名称必须!', 1, '', 3),
		array('teacher_id', 'require',  '店铺名称必须!', 1, '', 3),
		array('class_title', 'require',  '请填写课程名称', 1, '', 3),
		array('class_image', 'require',  '请上传课程主图', 1, '', 3),
		array('class_price', 'require',  '请填写课程原价', 1, '', 3),
		array('class_sort', '0,255',  '请填写正确的课程排序', 2, 'between', 3),
		array('body', 'require',  '请填写课程描述', 1, '', 3),
		array('commis_rate', 'require',  '请填写佣金比例', 2, '', 3),
		array('fx_title', 'require',  '请填写分享标题', 1, '', 3),
		array('fx_desc', 'require',  '请填写分享描述', 1, '', 3),
		array('fx_img', 'require',  '请上传分享缩略图', 1, '', 3),
	);

	/**
	 * 添加课程
	 */
	public function addOp()
	{
		if(IS_GET){
			//获取店铺信息
			$this->stores = M('store')->field(array('store_id,store_name'))->select();
			$this->display();
		}
		if (IS_POST) {
			$classModel = M('class');

			if (!$classModel->validate($this->reult)->create()) {
				$error = $classModel->getError();
				$this->ajaxReturn(['code'=>0, 'msg' => $error]);
			} else {
				$data = array();
				$data['store_id']         = I('post.store_id');
				$data['store_name']     = M('store')->field(array('store_name'))->where(array('store_id'=>$data['store_id']))->find()['store_name'];
				$data['class_title']     = I('post.class_title');
				$data['class_image']     = I('post.class_image');
				$data['teacher_id']     = I('post.teacher_id');
				$data['teacher_name']     = M('store_teacher')->field(array('teacher_name'))->where(array('store_id'=>$data['store_id']))->find()['teacher_name'];
				$data['class_price']       = I('post.class_price');
				$data['class_sort']       = I('post.class_sort');
				$data['class_body']        = I('post.body');
				$data['commis_rate']  = I('post.commis_rate');
				$data['class_addtime']  = time();
				$data['class_state'] = 1;
				$data['fx_title'] = I('post.fx_title');
				$data['fx_img'] = I('post.fx_img');
				$data['fx_desc'] = I('post.fx_desc');
				//学习人数
				$data['study_num'] = 0;
				$bool = $classModel->add($data);
				if($bool) {
					$this->ajaxReturn(['code'=>1, 'msg' => 'success']);
				} else {
					$this->ajaxReturn(['code'=>0, 'msg' => '更新数据失败']);
				}
			}
		}
	}

	/**
	 * 获取店铺讲师
	 */
	public function getTeachersOp(){
		$store_id = I('store_id' , '0' , 'intval');
		if(!IS_AJAX || $store_id == 0){
			echo json_encode(array('code' => 0));
			return;
		}
		$teachers = M('store_teacher')->field(array('teacher_id,teacher_name'))->where(array('store_id'=>$store_id))->select();
		if($teachers){
			$this->ajaxReturn(array('code' => 1 , 'data' => $teachers));
		}else{
			$this->ajaxReturn(array('code' => 0));
		}
	}

	/**
	 * 上传图片
	 **/
	public function uploadImagesOp()
	{
		$config = array(
			'maxSize'  => 3145728,
			'rootPath' => DIR_UPLOAD,
			'savePath' => '/' . ATTACH_CLASS . '/',
			'saveName' => array('uniqid', ''),
			'exts'     => array('jpg', 'gif', 'png', 'jpeg'),
			'autoSub'  => true,
			'subName'  => array(),
		);
		$message = D('Class', 'Service')->uploadImages($config);
		$this->ajaxReturn($message);
	}
	
	/**
	 * 获取课程列表数据
	 *
	 */
	public function getclasslistOp () {
		$page          = I('post.page', 1, 'intval');
		$limit         = I('post.rows', 10, 'intval');
		$class_title   = I('post.class_title', '');
		$class_teacher = I('post.class_teacher', '');

		$condition = array('is_del' => array('eq', '0'));

		if ($class_title != '') {
			$condition['class_title'] = array('like', '%' . $class_title . '%');
		}

		if ($class_teacher != '') {
			$condition['teacher_name'] = array('like', '%' . $class_teacher . '%');
		}
		$classService = new \Admin\Service\ClassService();
		$list = $classService->getClassList($condition,$page,$limit);
		$this->ajaxReturn($list);
	}


	/**
	 * 编辑课程列表
	 */
	public function editOp()
	{
		$class_id = I('class_id');
		if (IS_GET) {
			//获取店铺信息
			$this->stores = M('store')->field(array('store_id , store_name'))->select();
			//获取店铺讲师
			$this->teachers = M('store_teacher')->where(array('store_id'=>$this->stores['0']['store_id']))->field(array('teacher_id , teacher_name'))->select();
			$this->classInfo = M('class')->where(array('class_id' => $class_id))->find();
			$this->display();
		}

		if (IS_POST) {
			$classModel = M('class');
			if (!$classModel->validate($this->reult)->create()) {
				$error = $classModel->getError();
				$this->ajaxReturn(['code'=>0, 'msg' => $error]);
			} else {
				$data = array();
				$data['store_id']         = I('post.store_id');
				$data['store_name']     = M('store')->field(array('store_name'))->where(array('store_id'=>$data['store_id']))->find()['store_name'];
				$data['class_title']     = I('post.class_title');
				$data['class_image']     = I('post.class_image');
				$data['teacher_id']     = I('post.teacher_id');
				$data['teacher_name']     = M('store_teacher')->field(array('teacher_name'))->where(array('store_id'=>$data['store_id']))->find()['teacher_name'];
				$data['class_price']       = I('post.class_price');
				$data['class_sort']       = I('post.class_sort');
				$data['class_body']        = I('post.body');
				$data['commis_rate']  = I('post.commis_rate');
				$data['fx_title'] = I('post.fx_title');
				$data['fx_img'] = I('post.fx_img');
				$data['fx_desc'] = I('post.fx_desc');
				$bool = $classModel->where("class_id=$class_id")->save($data);
//				$bool = $classModel->save($data);
//				dump($bool);die;
				if(false !== $bool) {
					$this->ajaxReturn(['code'=>1, 'msg' => 'success']);
				} else {
					$this->ajaxReturn(['code'=>0, 'msg' => '更新数据失败']);
				}
			}
		}
	}
}
