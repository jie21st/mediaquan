(function($){
    /*微信设置*/
    $.wechat.hideOptionMenu();
    $.wechat.hideMenuItems([
        'menuItem:share:qq',
        'menuItem:share:weiboApp',
        'menuItem:share:QZone',
        'menuItem:copyUrl',
        'menuItem:openWithSafari',
        'menuItem:openWithQQBrowser'
    ])
})(window.Zepto || window.jQuery);


