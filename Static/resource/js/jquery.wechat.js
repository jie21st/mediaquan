(function($) {
    'use strict';
    
    $.wechat = {
        init: function(){
            if (!window.wx) {
                $('body').append('<script type="text/javascript" src="//res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>');
            }
        },
        share: function(options) {
            this.init();
            $.ajax({
                url: '/wechat/getJsSign',
                type: 'GET',
                data: {
                    url: encodeURIComponent(window.location.href)
                },
                dataType: 'jsonp',
                jsonp: 'callback',
                success: function (res) {
                    if (res.code == 1) {
                        var params = res.data;
                        wx.config({
                            debug: false,
                            appId: params.appId,
                            timestamp: params.timestamp,
                            nonceStr: params.nonceStr,
                            signature: params.signature,
                            jsApiList: [
                                'hideOptionMenu',
                                'showOptionMenu',
                                'hideMenuItems',
                                'showMenuItems',
                                'hideAllNonBaseMenuItem',
                                'showAllNonBaseMenuItem',
                                'closeWindow',
                                'chooseWXPay',
                                'onMenuShareAppMessage',
                                'onMenuShareTimeline',
                                'onMenuShareQQ',
                                'onMenuShareWeibo',
                                'onMenuShareQZone'
                            ]
                        });
                        wx.onMenuShareAppMessage(options)
                        wx.onMenuShareTimeline(options)
                    } else {
                        console.log('初始化wx类失败');
                    }
                }
            });  
        }
    }
   
}(window.Zepto || window.jQuery));
