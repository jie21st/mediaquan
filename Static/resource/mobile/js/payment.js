(function($, doc){
    var params = {}; //微信支付参数
    $('.js-pay').click(function () {
        var t = $(this);

        if (t.is('.disabled')) {
            return false;
        } else {
            t.addClass('disabled').text('正在努力加载，请稍后...');
        }
        
        $.ajax({
            type: 'POST',
            url: '/pay/index',
            data: pay_data || {},
            dataType: 'json',
            success: function (res) {
                t.removeClass('disabled').text('微信安全支付');
                if (res.code == 1) {
                    params = JSON.parse(res.data);
                    callwxpay();
                } else {
//                    paylog(res.msg);
                    alert('系统异常，请稍后再试!');
                }
            }
        });
    })
    
    function jsApiCall() {
        WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                params,
                function (res) {
                    WeixinJSBridge.log(res.err_msg);
                    console.log(res.err_code+res.err_desc+res.err_msg);
                    switch (res.err_msg) {
                        case 'get_brand_wcpay_request:ok':
                            window.location.replace(pay_success_url);
                            break;
                        case 'get_brand_wcpay_request:cancel':
                            paylog('取消支付');
                            break;
                        case 'get_brand_wcpay_request:fail':
                            paylog('微信支付参数错误');
                            showNativePay();
                            break;
                        default:
                            paylog(res.err_msg);
                            alert('支付失败，稍后请重试！');
                    }
                }
        );
    }
    
    function showNativePay() {
        $.ajax({
            type: 'POST',
            url: '/pay/index?trade_type=native',
            data: pay_data || {},
            dataType: 'json',
            success: function (res) {
                if (res.code == 1) {
                    var qrcode = '/api/qrcode?data='+res.data;
                    $('.native-pay-box').removeClass('fn-hide').find('.qrcode').attr('src', qrcode);
                    $('.list-payment-info, .list-balance-info, .bottom-fix').addClass('fn-hide');
                    var loop = Util.loop(function(){
                        $.ajax({
                            type: 'GET',
                            url: '/pay/queryOrderState',
                            data: pay_data || {},
                            dataType: 'json',
                            success: function (res) {
                                if (res.code == 1) {
                                    loop.stop();
                                    window.location.replace(pay_success_url);
                                }
                            }
                        });
                    }, 1200, 500);
                } else {
                    alert(res.msg);
                }
            }
        });
        
    }
    
    function paylog(str) {
        pay_data['reason'] = str;
        $.ajax({
            type: 'POST',
            url: '/pay/failed',
            data: pay_data || {},
            dataType: 'json',
            success: function (res) {
                if (res.code == 1) {
                    console.log('通知成功');
                } else {
                    console.log('通知失败' + res.msg);
                }
            }
        });
    }

    function callwxpay() {
        if (typeof WeixinJSBridge == "undefined") {
            if (document.addEventListener) {
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            } else if (document.attachEvent) {
                document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        } else {
            jsApiCall();
        }
    }
})(jQuery, document);
