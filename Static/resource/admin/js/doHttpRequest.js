var doHttpRequest = {

    sendCommonRequest: function(param, httpurl, inputopts, success, error) {
        success = success || function() {};
        error = error || function() {};

        var opts = $.extend({}, {
            type: 'POST',
            dataType: 'jsonp',
            jsonp: 'callback',
            data: param
        }, inputopts);


        sendRequest(httpurl, opts).then(function(response) {
            var code = response.code;
            if (code == 1) {
                success(response);
            } else {
                error(response);
            }
        });
    },

    getmsgtpl: function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getMsgTpl'], opts, success, error);
    },
    getMsgTplbyid: function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getMsgTplbyid'], opts, success, error);
    },
    addOneMsgTpl:function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['addOneMsgTpl'], opts, success, error);
    },
    copyOneMsg: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['copyOneMsg'], opts, success, error);
    },
    modifyMsgTpl: function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['modifyMsgTpl'], opts, success, error);
    },
     getMsgTplbyPage: function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getMsgTplbyPage'], opts, success, error);
    },
    chgMsgTplStatus: function(param, success, error) {

        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['chgMsgTplStatus'], opts, success, error);
    },
    getSchemesByPage: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['getSchemesByPage'], opts, success, error);
    },
    getSchemebyid: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['getSchemebyid'], opts, success, error);
    },
    chgSchemeStatus: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['chgSchemeStatus'], opts, success, error);
    },
    modifyScheme: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['modifyScheme'], opts, success, error);
    },
    addScheme: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['addScheme'], opts, success, error);
    },
    copyOneScheme: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['copyOneMsg'], opts, success, error);
    },
    
   getMsgSendInfobyPage: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getMsgSendInfobyPage'], opts, success, error);
    },
    getSendinfobyid: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getSendinfobyid'], opts, success, error);
    },
    chgSendInfo: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['chgSendInfo'], opts, success, error);
    },
    chgSendinfoStatus: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['chgSendinfoStatus'], opts, success, error);
    },
    getColomundef: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getColomundef'], opts, success, error);
    },
    delMsgTplData: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['delMsgTplData'], opts, success, error);
    },
    delMsgTplSql: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['delMsgTplSql'], opts, success, error);
    },
    delSchemeEle: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SCHEME['delSchemeEle'], opts, success, error);
    },
    QryUsers4View: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_USER['QryUsers4View'], opts, success, error);
    },
    getUserLevels: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getUserLevels'], opts, success, error);
    },
    getChannels: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.MSG_TPL['getChannels'], opts, success, error);
    },
    
    getQrCodebyid: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.QRCODE['getQrCodebyid'], opts, success, error);
    },
    getQrCodesByPage: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.QRCODE['getQrCodesByPage'], opts, success, error);
    },
    ModifyQrcode: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.QRCODE['ModifyQrcode'], opts, success, error);
    },
    addQrcode: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.QRCODE['addQrcode'], opts, success, error);
    },
    chgQrStatus: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.QRCODE['chgQrStatus'], opts, success, error);
    },
 
    getSlangById: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SLANG['getSlangById'], opts, success, error);
    },
    chgSlangStatus: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SLANG['chgSlangStatus'], opts, success, error);
    },
    addSlang: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SLANG['addSlang'], opts, success, error);
    },
    getSlangsByPage: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SLANG['getSlangsByPage'], opts, success, error);
    },
    modifySlang: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.SLANG['modifySlang'], opts, success, error);
    },
    uploadResImg: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['upload'], opts, success, error);
    },
    deleteResImg: function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['removeImg'], opts, success, error);
    },
    qryResImg:function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['qryResImg'], opts, success, error);
    },
    getResImgByPage:function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['getResImgByPage'], opts, success, error);
    },
    getResImgTextByPage:function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['getResImgTextByPage'], opts, success, error);
    },
    delResImgText:function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['delResImgText'], opts, success, error);
    },
    modifyResImgText:function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['modifyResImgText'], opts, success, error);
    },
    getResImgText4waterpall :function(param, success, error) {
        var opts = {};
        this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['getResImgText4waterpall'], opts, success, error);
    },
    getWechatActivityById:function(param, success, error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['getWechatActivityById'], opts, success, error);
    },
    delOneActivity:function(param, success, error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['delActivityById'], opts, success, error);
    },
    batchIsDelete:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['batchIsDelete'], opts, success, error);
    },
    queryActivityInfo:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['queryActivityInfo'], opts, success, error);
    },
    qryLevelName:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['qryLevelName'], opts, success, error);
    },
    getProvinces:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT['getProvinces'], opts, success, error);
    },
    findById:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT['findById'], opts, success, error);
    },
    excelExport:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT['excelExport'], opts, success, error);
    },
    activityUserExport:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['activityUserExport'], opts, success, error);
    },
    poiExport:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_ACTIVITY['poiExport'], opts, success, error);
    },
    modifyClientInfo:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT['modifyClientInfo'], opts, success, error);
    },
    modifyLevel:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT['modifyLevel'], opts, success, error);
    },
    queryLevelName:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_CLIENT_LEVEL['queryLevelName'], opts, success, error);
    },
    getAllFuncName:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.PortalFunc['getAllFuncName'], opts, success, error);
    },
    insertUserFunc:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.Authority['insertUserFunc'], opts, success, error);
    },
    getByUserId:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.Authority['getByUserId'], opts, success, error);
    },
    getLoginUserAndClientUser:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.Authority['getLoginUserAndClientUser'], opts, success, error);
    },
    getMenu:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.UserMenu['getMenu'], opts, success, error);    	
    },
    exportWchatLevelLog:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.WECHAT_LEVEL_LOG['exportExcel'], opts, success, error);    	
    },
    addResImgText:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['addResText'], opts, success, error);    	
    },
    getResTextById:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['getResTextById'], opts, success, error);    	
    },
    updateResText:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['updateResText'], opts, success, error);    	
    },
    deleteResText:function(param,success,error){
    	var opts = {};
    	this.sendCommonRequest(param, GLZH_ADMIN_API.RESIMG['deleteResText'], opts, success, error);    	
    },
}
