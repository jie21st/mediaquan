
var API_URL_PREFIX = '';
var RESPONSE_CODE = {
	    '_PARAM_ERROR_': 10101,
	    '_TOKEN_ERROR_': 10105,
	    '_VALID_ACCESS_': 20102
	};
var GLZH_ADMIN_API = {
    MSG_TPL: {
        'getMsgTpl'      : API_URL_PREFIX + '/msgtpl/getAllMsgTpl',
        'getMsgTplbyid':API_URL_PREFIX + '/msgtpl/getMsgTplbyid',
        'modifyMsgTpl':API_URL_PREFIX + '/msgtpl/modifyMsgTpl',
        'addOneMsgTpl':API_URL_PREFIX + '/msgtpl/addOneMsgTpl',
        'getMsgTplbyPage':API_URL_PREFIX + '/msgtpl/getMsgTplbyPage',
        'chgMsgTplStatus':API_URL_PREFIX + '/msgtpl/ChgMsgTplStatus',
        'getMsgSendInfobyPage':API_URL_PREFIX + '/msgtpl/getMsgSendInfobyPage',
        'getSendinfobyid':API_URL_PREFIX + '/msgtpl/getSendinfobyid',
        'chgSendInfo':API_URL_PREFIX + '/msgtpl/chgSendInfo',
        'chgSendinfoStatus':API_URL_PREFIX + '/msgtpl/chgSendinfoStatus',
        'delMsgTplData':API_URL_PREFIX + '/msgtpl/delMsgTplData',
        'delMsgTplSql':API_URL_PREFIX + '/msgtpl/delMsgTplSql',
        'getColomundef':API_URL_PREFIX + '/msgtpl/getColomundef',
        'getChannels':API_URL_PREFIX + '/msgtpl/getChannels',
        'getUserLevels':API_URL_PREFIX + '/msgtpl/getUserLevels',
        'copyOneMsg':API_URL_PREFIX + '/msgtpl/copyOneMsgById',
        'qrySendLog':API_URL_PREFIX + '/msgtpl/querySendLogByPage',
    },
    SCHEME: {
        'getSchemesByPage'      : API_URL_PREFIX + '/scheme/getSchemesByPage',
        'getSchemebyid'      : API_URL_PREFIX + '/scheme/getSchemebyid',
        'chgSchemeStatus'      : API_URL_PREFIX + '/scheme/chgSchemeStatus',
        'modifyScheme'      : API_URL_PREFIX + '/scheme/modifyScheme',
        'addScheme':  API_URL_PREFIX + '/scheme/addScheme',
        'delSchemeEle':  API_URL_PREFIX + '/scheme/delSchemeEle',
        'copyOneMsg':  API_URL_PREFIX + '/scheme/copyOneMsgBySchemeId'
    },
    WECHAT_USER:{
    	'QryUsers4View'      : API_URL_PREFIX + '/wechatuser/QryUsers4View'
    },
    QRCODE:{
    	'getQrCodebyid'      : API_URL_PREFIX + '/qrcode/getQrCodebyid',
    	'getQrCodesByPage'   : API_URL_PREFIX + '/qrcode/getQrCodesByPage',
    	'ModifyQrcode'      : API_URL_PREFIX + '/qrcode/modifyQrcode',
    	'addQrcode'      	: API_URL_PREFIX + '/qrcode/addQrcode',
    	'chgQrStatus':		 API_URL_PREFIX + '/qrcode/chgQrStatus'
    },
    SLANG:{
    	'getSlangById'      : API_URL_PREFIX + '/slang/getSlangById',
    	'chgSlangStatus'      : API_URL_PREFIX + '/slang/chgSlangStatus',
    	'addSlang'      	: 	API_URL_PREFIX + '/slang/addSlang',
    	'getSlangsByPage'      : API_URL_PREFIX + '/slang/getSlangsByPage',
    	'modifySlang'      : API_URL_PREFIX + '/slang/modifySlang'
    },
    BASE_USER:{
    	'login'      :		API_URL_PREFIX + '/baseuser/login'
    },
    RESIMG:{
    	'upload'      :		API_URL_PREFIX + '/imagetext/uploadImg',
    	'removeImg'      :		API_URL_PREFIX + '/imagetext/removeImg',
    	'getResImgByPage'      :		API_URL_PREFIX + '/imagetext/getResImgByPage',
    	'getResImgTextByPage'      :		API_URL_PREFIX + '/imagetext/getResImgTextByPage',
    	'getResImgText4waterpall' :		API_URL_PREFIX + '/imagetext/getResImgText4waterpall',
    	'delResImgText'      :		API_URL_PREFIX + '/imagetext/delResImgText',
    	'modifyResImgText'      :		API_URL_PREFIX + '/imagetext/modifyResImgText',
      	'getResTextByPage'		:		API_URL_PREFIX + '/restext/getResTextByPage',
    	'addResText'		:		API_URL_PREFIX + '/restext/addResText',
    	'updateResText'		:		API_URL_PREFIX + '/restext/updateResText',
    	'getResTextById'		:		API_URL_PREFIX + '/restext/getResTextById',
    	'deleteResText'		:		API_URL_PREFIX + '/restext/deleteResText',
    },
    GLZH_CLASS:{
    	'getCoursesByPage'   :    API_URL_PREFIX + '/glzhclass/getCoursesByPage'
    },
    WECHAT_ACTIVITY:{
    	'getWechatActivityByPage':  API_URL_PREFIX + '/wechatactivity/getWechatActivityByPage',
    	'getWechatActivityById'  :  API_URL_PREFIX + '/wechatactivity/getWechatActivityById',
    	'addOrUpdateActivity'    :  API_URL_PREFIX + '/wechatactivity/addOrUpdateActivity',
    	'delActivityById'        :  API_URL_PREFIX + '/wechatactivity/delActivityById',
    	'batchIsDelete'          :  API_URL_PREFIX + '/wechatactivity/batchIsDelete',
    	'queryActivityInfo'      :  API_URL_PREFIX + '/wechatactivity/queryActivityInfo',
    	'qryLevelName'           :  API_URL_PREFIX + '/wechatactivity/qryLevelName',
    	'activityUserExport'     :  API_URL_PREFIX + '/activityuserexport/exportActivityUsersInfo',
    	'poiExport'              :  API_URL_PREFIX + '/poiexport/poiExportExcel',
    	
    },
    WECHAT_CLIENT:{
    	'getAllClient'           :  API_URL_PREFIX + '/clientmanagement/getAllClient',
    	'getProvinces'           :  API_URL_PREFIX + '/clientmanagement/getProvinces',
    	'findById'           	 :  API_URL_PREFIX + '/clientmanagement/findById',
    	'excelExport'            :  API_URL_PREFIX + '/excelexport/exportClientInfo',
    	'modifyClientInfo'       :  API_URL_PREFIX + '/clientmanagement/modifyClientInfo',
    	'modifyLevel'			 :  API_URL_PREFIX + '/clientmanagement/modifyLevel'
    },
    WECHAT_CLIENT_LEVEL:{
    	'queryLevelName'         :  API_URL_PREFIX + '/clientmanagement/queryLevelName'
    },
    PortalFunc:{
    	'getAllFuncName'         :  API_URL_PREFIX + '/portalfunc/getPortalFuncTree'
    },
    Authority:{
    	'insertUserFunc'         :  API_URL_PREFIX + '/userfunc/insertUserFunc',
    	'getByUserId'         :  API_URL_PREFIX + '/userfunc/getByUserId',
    	'getLoginUserAndClientUser' :API_URL_PREFIX + '/userfunc/getLoginUserAndClientUser',
    },
    UserMenu:{
    	'getMenu'				 :  API_URL_PREFIX+'/menu/getMenu'
    },
    WECHAT_LEVEL_LOG:{
    	'exportExcel'				 :  API_URL_PREFIX+'/wechatlog/exportExcel'
    },
}
var config = {};
config.uploadResourceUrl = "http://static1.guanlizhihui.com/uploads/";
//config.uploadResourceUrl = "/uploads/";
var CoverImg='/';
var modifyActivityImg="/images/activity/coverImg/";
