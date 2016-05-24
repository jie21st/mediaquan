(function($) {
    'use strict';
    
    $.wechat = {
        isRequest: false,
        signData: {},
        init: function(callback)
        {
            var self = this;
            if (this.isRequest === false) {
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
                            wx.config({
                                debug: false,
                                appId: res.data.appId,
                                timestamp: res.data.timestamp,
                                nonceStr: res.data.nonceStr,
                                signature: res.data.signature,
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
                                    'onMenuShareTimeline'
//                                    'onMenuShareQQ',
//                                    'onMenuShareWeibo',
//                                    'onMenuShareQZone'
                                ]
                            });
                            callback();
                        }
                        self.isRequest = true;
                    }
                });
            } else {
                callback();
            }
        },
        share: function(options) {
            this.init(function(){
                wx.ready(function(){
                    wx.showOptionMenu();
                    $.wechat.hideMenuItems([
                        'menuItem:share:qq',
                        'menuItem:share:weiboApp',
                        'menuItem:share:QZone',
                        'menuItem:copyUrl',
                        'menuItem:openWithSafari',
                        'menuItem:openWithQQBrowser'
                    ]);
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
        },
        showOptionMenu: function() {
            this.init(function(){
                wx.ready(function(){
                    wx.showOptionMenu();
                })
            })
        },
        hideMenuItems: function(items){
            this.init(function(){
                wx.ready(function(){
                    wx.hideMenuItems({
                        menuList: items
                    });
                })
            })
        }
    }
   
}(window.Zepto || window.jQuery));
