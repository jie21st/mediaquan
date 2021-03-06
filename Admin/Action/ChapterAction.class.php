<?php
/**
 * Created by PhpStorm.
 * User: LiYi
 * Date: 16/6/23
 * Time: 上午10:50
 * PS：章节列表相关
 */
namespace Admin\Action;


use Admin\Service\ClassService;

class ChapterAction extends CommonAction
{
	/**
	 * 章节列表页
	 */
	public function listOp(){
		$this->display();
	}


	protected $reult = array(
		array('chapter_title', 'require',  '章节标题必须!', 1, '', 3),
		array('class_id', 'require',  '课程类型必须!', 1, '', 3),
		array('teacher_name', 'require',  '讲师名称必须!', 1, '', 3),
		array('cover_img_url', 'require',  '封面图必须!', 1, '', 3),
		array('audioUploadBtn', 'require',  '音频必须!', 1, '', 3),
		array('uploadPPTBtn', 'require',  'PDF文件必须', 1, '', 3),
		array('duration_start', 'require',  '时长开始必须!', 1, '00', 3),
		array('duration_end', 'require',  '时长结束必须!', 1, '00', 3),
		array('start_datetime', 'require',  '开始时间必须!', 2, '', 3),
		array('status', 'require',  '状态必须!', 1, '1', 3),
		array('description', 'require',  '摘要必须!', 1, '', 3),
	);


	/**
	 * 添加课程
	 */
	public function addOp()
	{
		if(IS_GET){
			//获取课程列表信息
			$classService = new \Common\Model\ClassModel();
			$this->class = $classService->getClassList('', 'class_id, class_title', 'class_id desc');
			$this->display();
		}
		if (IS_POST) {
			$classChapterModel = M('class_chapter');

			if (!$classChapterModel->validate($this->reult)->create()) {
				$error = $classChapterModel->getError();
				$this->ajaxReturn(['code'=>0, 'msg' => $error]);
			} else {
				$data = array();
				$data['chapter_title']         = I('post.chapter_title');
				$data['class_id']     = I('post.class_id');
				$data['teacher_name']     = I('post.teacher_name');
				$data['cover_img_url']     = I('post.cover_img_url');
				$data['audio_url']       = I('post.audioUploadBtn');
				$data['audio_url_on_qiniu']       = I('post.audioUploadBtn');
				$data['video_url']       = I('post.videoUploadBtn');
				$data['ppt_dir']       =  I('post.uploadPPTBtn');
				$data['ppt_length']       = I('post.ppt_length', 0, 'intval');
				$data['duration_start'] = I('post.duration_start', '0', 'intval');
				$data['duration_end'] = I('post.duration_end', '0', 'intval');
				$data['duration']        = I('post.duration_end', '0', 'intval') - I('post.duration_start', '0', 'intval');
				$data['start_datetime']  = I('post.start_datetime');
				$data['status']  = I('post.status', '1', 'intval');
				$data['description']  = I('post.description');
				$data['create_datetime']  = date('Y-m-d H:i:s');
				$data['creator_id']  = session('admin_user.admin_id');
				$data['tool_type'] = I('post.tool_type', '0', 'intval');
				$bool = $classChapterModel->add($data);
				if(false !== $bool) {
					$this->ajaxReturn(['code'=>1, 'msg' => 'success']);
				} else {
					$this->ajaxReturn(['code'=>0, 'msg' => '更新数据失败']);
				}
			}
		}
	}

	/**
	 *禁用章节
	 */
	public function delOp(){
		$chapter_id = I('chapter_id' , '0' , 'intval');
		if(!IS_AJAX || $chapter_id == 0){
			echo json_encode(array('code' => 0));
			return;
		}
		$bool = M('class_chapter')->where(array('chapter_id'=>$chapter_id))->save(array('status' => 0));
		if($bool !== false){
			$this->ajaxReturn(array('code' => 1 , 'data' => $bool));
		}else{
			$this->ajaxReturn(array('code' => 0));
		}
	}

	/**
	 * 上传
	 **/
	public function uploadImagesOp()
	{
		if (!IS_AJAX) {
			return false;
		}
		$fileArr = array('image', 'file', 'video', 'audio');
		$filetype = I('get.filetype', 'image');
		if (!in_array($filetype, $fileArr)) $this->ajaxReturn(array('code' => 0, 'msg' => '上传类型错误!'));
		$setting = (new \Common\Model\SettingModel())->get('upload')[$filetype];
		$config = array(
			'maxSize'  => $setting['limit']*1024,
			'rootPath' => DIR_UPLOAD,
			'savePath' => '/' . ATTACH_CHAPTER . '/' . $filetype . '/',
			'saveName' => array('uniqid', ''),
			'exts'     => $setting['extentions'],
			'autoSub'  => true,
			'subName'  => array(),
		);

		try {
			$upload = new \Think\Upload($config);
			$info = $upload->uploadOne($_FILES['upload_file']);
			if (!$info) {
				throw new \Exception($upload->getError());
			}

			//上传七牛
			$filename = $filetype . DS . $info['savename'];
			$filepath = DIR_UPLOAD . DS . ATTACH_CHAPTER . DS . $filename;
			$QinniuService = new \Common\Service\QiniuService();
			$res = $QinniuService->upload($filepath, $filename);
			if (!$res) {
				throw  new \Exception("上传失败");
			}
			$message = array(
				'code' => 1,
				'data' => array(
					'url' => C('UPLOADS_SITE_URL') . DS . ATTACH_CHAPTER . DS . $filetype . DS . $info['savename'],
					'filename' => $filename,
				)
			);
			//如果上传文件为PDF 则转图片
			if ($filetype == 'file') {
				//PDE转图片
				$parts = pathinfo($filename);
				$newdirname = $parts['dirname'] . DS . $parts['filename']; //pdf/new
				$savepath = DIR_UPLOAD . DS . ATTACH_CHAPTER . DS . $newdirname;    //  /mnt/www/Static/uploads/pdf/new
				$result = pdf2image($filepath, $savepath);
				if ( !$result ){
					throw new \Exception("PDF转图片失败!");
				}
				$message['data']['page_num'] = count($result);
				foreach ($result as $file) {
					$pdfRes = $QinniuService->upload($savepath . DS . $file, $newdirname . DS . $file);
					if (!$pdfRes) {
						throw new \Exception("PDF图片上传失败");
					}
				}
				$message['data']['filename'] = $newdirname;
			}
			$this->ajaxReturn($message);
		} catch (\Exception $e) {
			$this->ajaxReturn(array('code' => 0, 'msg' => $e->getMessage()));
		}
	}
	/**
	 * 获取课程列表数据
	 *
	 */
	public function getchapterlistOp () {
		$page          = I('post.page', 1, 'intval');
		$limit         = I('post.rows', 10, 'intval');
		$chapter_title   = I('post.chapter_title', '');
		$status = I('post.status', '-1', 'intval');

		$condition = array();

		if ($chapter_title != '') {
			$condition['chapter_title'] = array('like', '%' . $chapter_title . '%');
		}

		if ($status != '-1') {
			$condition['status'] = array('eq',  $status);
		}
		$chapterService = new \Admin\Service\ChapterService();
		$list = $chapterService->getChapterList($condition,$page,$limit);
		$this->ajaxReturn($list);
	}


	/**
	 * 编辑课程列表
	 */
	public function editOp()
	{
		$chapter_id = I('chapter_id');
		if (IS_GET) {
			//获取课程列表信息
			$classService = new \Common\Model\ClassModel();
			$this->class = $classService->getClassList('', 'class_id, class_title', 'class_id desc');
			$this->chapterInfo = M('class_chapter')->where(array('chapter_id' => $chapter_id))->find();
			$this->display();
		}
		if (IS_POST) {
			$classChapterModel = M('class_chapter');
			if (!$classChapterModel->validate($this->reult)->create()) {
				$error = $classChapterModel->getError();
				$this->ajaxReturn(['code'=>0, 'msg' => $error]);
			} else {
				$data = array();
				$data['chapter_title']         = I('post.chapter_title');
				$data['class_id']     = I('post.class_id');
				$data['teacher_name']     = I('post.teacher_name');
				$data['cover_img_url']     = I('post.cover_img_url');
				$data['audio_url']       = I('post.audioUploadBtn');
				$data['video_url']       = I('post.videoUploadBtn');
				$data['ppt_dir']       = I('post.uploadPPTBtn');
				$data['duration_start'] = I('post.duration_start', '0', 'intval');
				$data['duration_end'] = I('post.duration_end', '0', 'intval');
				$data['duration']        = I('post.duration_end', '0', 'intval') - I('post.duration_start', '0', 'intval');
				$data['start_datetime']  = I('post.start_datetime');
				$data['status']  = I('post.status', '1', 'intval');
				$data['description']  = I('post.description');
				$data['tool_type'] = I('post.tool_type', '0', 'intval');
				$bool = $classChapterModel->where(array("chapter_id=$chapter_id"))->save($data);
				if(false !== $bool) {
					$this->ajaxReturn(['code'=>1, 'msg' => 'success']);
				} else {
					$this->ajaxReturn(['code'=>0, 'msg' => '更新数据失败']);
				}
			}
		}
	}
	/**
	 * 获取讲师信息
	 * @return bool
	 */
	public function getTeacherNameOp(){
		if(!IS_POST) return false;
		$class_id = I('post.class_id', '-1', 'intval');
		$teacher_name = M('class')->field('teacher_name')->where(array('class_id'=>$class_id))->find();
		if( $teacher_name ){
			$this->ajaxReturn(['code' => 1, 'msg' => 'success', 'teacher_name' => $teacher_name['teacher_name']]);
		}else{
			$this->ajaxReturn(['code' => 0, 'msg' => '获取数据失败']);
		}
	}
}
