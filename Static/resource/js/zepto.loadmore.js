;
(function($) {
    //'use strict'; 
    $.fn.loadmore = function(options) {
        var loadmore = new glzh_loadmore(this, options);
        $(this).data("scroll_load", loadmore)
        return loadmore;
    };
    var glzh_loadmore = function(element, options) {
        this.$el = element;
        var opts = {
            contain: "ui-scroll",
            loadingBtn: "ui-refresh",
            loadingText: "正在加载中...",
            loadText: "上拉加载更多",
            loadedText: "没有更多了",
            thread: 50,
            load: function() {
                console.log("load")
            }
        };
        this._settings = $.extend({}, opts, options);
        this.y = this.$el.offset().top + this.$el.height();
        this.pageY = $(window).height();
        this.init()
    };

    glzh_loadmore.prototype = {
        init: function() {
            this.$el.addClass("ui-scroll");
            if (this.y >= this.pageY) {
                this.$moreBtn = $("<div>" + this._settings.loadText + "</div>").addClass(this._settings.loadingBtn);
                this.$el.append(this.$moreBtn);
                $(document).on("scroll", $.proxy(this._eventHandler, this));
                this.$el.on("touchstart touchmove touchend touchcancel", $.proxy(this._eventHandler, this));
            }
        },
        afterLoad: function() {
            this._update = !1;
            this._refresh();
            this.$moreBtn.html(this._settings.loadText)
        },
        lastLoad: function() {
            this._refresh();
            this.$moreBtn.html(this._settings.loadedText)
        },
        _refresh: function() {
            this.y = this.$el.offset().top + this.$el.height()
        },
        _eventHandler: function(t) {
            var e = this;
            switch (t.type) {
                case "touchmove":
                    clearTimeout(e._endTimer);
                    e._endTimer = setTimeout(function() {
                        e._endHandler()
                    }, 300);
                    e._moveHandler(t);
                    break;
                case "scroll":
                    e._moveHandler(t)
            }
        },
        _moveHandler: function(t) {
            if (this.y <= this.pageY + window.scrollY) {
                this._update || (this._update = !0, this.$moreBtn.html(this._settings.loadingText), this._settings.load());
            }
        },
        
    };
})(window.Zepto || window.jQuery);
