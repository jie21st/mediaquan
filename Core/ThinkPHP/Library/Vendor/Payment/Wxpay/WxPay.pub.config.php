<?php
/**
* 	配置账号信息
*/

class WxPayConf_pub
{
	//=======【基本信息设置】=====================================
	//微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	const APPID = 'wxb757c4c5a5844477';
	//受理商ID，身份标识
	const MCHID = '1243364302';
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	const KEY = '24dc2e1f84298719453037775ecb44c9';
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	const APPSECRET = 'f47e97cf577a66cea1d9a7c8f6606669';
	const JS_API_CALL_URL = 'http://$_SERVER[HTTP_HOST]/paytest/index' ;
	
	
	
	const SSLCERT_PATH = COMMON_PATH . '/Resource/Cert/apiclient_cert.pem';
	const SSLKEY_PATH = COMMON_PATH . '/Resource/Cert/apiclient_key.pem';
        const CAINFO_PATH = COMMON_PATH . '/Resource/Cert/rootca.pem';

	const NOTIFY_URL = 'http://$_SERVER[HTTP_HOST]/paytest/notify';
	const CURL_TIMEOUT = 60;
}

	
?>
