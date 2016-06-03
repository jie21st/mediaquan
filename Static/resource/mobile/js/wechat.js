(function($) {
    'use strict';
    
    $.wechat = {
        URL : {
		GET_SIGN: '/wechat/getJsSign',
		WEIXIN_JS: "http://res.wx.qq.com/open/js/jweixin-1.0.0.js"
	},
        getScript: function (option) {
		var script = document.createElement("script");
		script.type = "text/javascript";
		script.src = option.url;
		script.charset = option.charset || "UTF-8";
		script.onload = script.onreadystatechange = function() {
			if (!this.readyState || this.readyState == "loaded" || this.readyState == "complete") {
				this.onload = this.onreadystatechange = null;
				option.callback && option.callback();
			}
		}
		document.getElementsByTagName("head")[0].appendChild(script);
	},
        init: function(fn) {
            // 如果不是在微信浏览器， 则返回
            if (navigator.userAgent.toLowerCase().match(/micromessenger/i) != "micromessenger") return;
            
            var _this = this;
            _this.getScript({
                url: _this.URL.WEIXIN_JS,
                callback: function(){
                    $.ajax({
                        url: _this.URL.GET_SIGN,
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
                                
                                wx.ready(function () {
                                    fn && fn();
				});
                            }
                        }
                    });
                }
            });
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
   
}(window.jQuery));
