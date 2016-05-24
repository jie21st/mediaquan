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
    ]);
    
    // 页脚菜单固定
    resizeWindow();
    $(window).resize(resizeWindow);
    function resizeWindow()
    {
        if ($(document).height() <= $(window).height()) {
            $('.footer').addClass('guding');
        } else {
            $('.footer').removeClass('guding');
        }
    }
})(window.Zepto || window.jQuery);


