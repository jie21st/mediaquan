<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxbbaf282fda56c460';
	//受理商ID，身份标识
	const MCHID = '1338874701';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '6fea71bf79906524a0f417b7df1b7e40';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = '5a1274946216c518cf35a0e561bd81fd';
	const JS_API_CALL_URL = 'http://$_SERVER[HTTP_HOST]/paytest/index' ;
	
	
	
	const SSLCERT_PATH = COMMON_PATH . '/Resource/Cert/apiclient_cert.pem';
	const SSLKEY_PATH = COMMON_PATH . '/Resource/Cert/apiclient_key.pem';
        const CAINFO_PATH = COMMON_PATH . '/Resource/Cert/rootca.pem';

	const NOTIFY_URL = 'http://$_SERVER[HTTP_HOST]/paytest/notify';
	const CURL_TIMEOUT = 60;
}

	
?>
