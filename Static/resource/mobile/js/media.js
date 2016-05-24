(function($){
    // 页脚菜单固定
    resizeWindow();
    $(window).resize(resizeWindow);
    function resizeWindow() {
        if ($(document).height() <= $(window).height()) {
            $('.footer').addClass('guding');
        } else {
            $('.footer').removeClass('guding');
        }
    }
})(window.Zepto || window.jQuery);


