(function($) {
    'use strict';
    
    $.wechat = {
        isRequest: false,
        signData: {},
        init: function(callback)
        {
            var self = this;
            if (isRequest === false) {
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
                            self.signData = res.data;
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
                            callback(res.data);
                        }
                        
                        self.isRequest = true;
                    }
                });
            } else {
                callback(this.signData);
            }
        },
        share: function(options) {
            this.init(function(params){
                wx.ready(function(){
                    wx.onMenuShareAppMessage(options);
                    wx.onMenuShareTimeline(options);
                })
            })
        },
        hideOptionMenu: function() {
            this.init(function(){
                wx.ready(function(){
                    wx.hideOptionMenu();
                })
            })
        }
    }
   
}(window.Zepto || window.jQuery));
