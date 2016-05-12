<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxd18b1177628b7f9a';
	//受理商ID，身份标识
	const MCHID = '1327251001';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = 'd88d992b0485fadcf08706e4456505dd';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = '124f73ec548d7b372bf4a612e81753c4';
	const JS_API_CALL_URL = 'http://$_SERVER[HTTP_HOST]/paytest/index' ;
	
	
	
	const SSLCERT_PATH = COMMON_PATH . '/Resource/Cert/apiclient_cert.pem';
	const SSLKEY_PATH = COMMON_PATH . '/Resource/Cert/apiclient_key.pem';
        const CAINFO_PATH = COMMON_PATH . '/Resource/Cert/rootca.pem';

	const NOTIFY_URL = 'http://$_SERVER[HTTP_HOST]/paytest/notify';
	const CURL_TIMEOUT = 60;
}

	
?>
