var WXFX_GLZ = {
    /*
    var param={
    infoTitle:,
    appid:,
    timestamp:,
    nonceStr:,
    signature:,
    infoImgUrl:,
    infoDesc:,
    xurl:,	
    };
    */
    init: function(params) {
        this.params = params || [];
        this.intiWXconfig();
    },
    intiWXconfig: function() {
        var self = this;
        wx.config({
            debug: false,
            appId: self.params.appid,
            timestamp: self.params.timestamp,
            nonceStr: self.params.nonceStr,
            signature: self.params.signature,
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
        wx.ready(function() {
            // 分享给朋友
            wx.onMenuShareAppMessage({
                title: self.params.infoTitle,
                desc: self.params.infoDesc,
                link: self.params.xurl,
                imgUrl: self.params.infoImgUrl,
                success: function() {},
                cancel: function() {}
            });
            // 分享到朋友圈
            wx.onMenuShareTimeline({
                title: self.params.infoTitle,
                link: self.params.xurl,
                imgUrl: self.params.infoImgUrl,
                success: function() {},
                cancel: function() {},
                fail: function(res) {
                    alert(JSON.stringify(res));
                }
            });
            // 分享到QQ
            wx.onMenuShareQQ({
                title: self.params.infoTitle,
                desc: self.params.infoDesc,
                link: self.params.xurl,
                imgUrl: self.params.infoImgUrl
            });
            // 分享到腾讯微博
            wx.onMenuShareWeibo({
                title: self.params.infoTitle,
                desc: self.params.infoDesc,
                link: self.params.xurl,
                imgUrl: self.params.infoImgUrl
            });
            // 分享到QQ空间
            wx.onMenuShareQZone({
                title: self.params.infoTitle,
                desc: self.params.infoDesc,
                link: self.params.xurl,
                imgUrl: self.params.infoImgUrl
            });
        });
    }
}

