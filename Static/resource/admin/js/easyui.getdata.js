(function(window, $){
    var $obj;
    var config = {
        url : '',
        queryParams : {},
        loadMsg : '数据加载中,请稍后...',
        pageList : [ 10, 20, 30 ], // 设置每页显示多少条
        toolbar:[{text:'添加课程',iconCls:'icon-add',}],
        onLoadError : function() {alert('数据加载失败!')},
        onLoadSuccess : function() {},
        onDel : function() {},
    };

    var start = {
        init : function (conf) {
            $.each(conf, function(name, value){
                config[name] = value;
            })
        },

        ajax : function () {
            $obj.datagrid({
                toolbar:config.toolbar,
                url : config.url,
                queryParams:config.queryParams,
                loadMsg : config.loadMsg,
                pageList : config.pageList, 
                onLoadError : config.onLoadError,
                onLoadSuccess : config.onLoadSuccess
            }).datagrid('acceptChanges');
        },
    }

    $.fn.easySubmitAjax = function(config) {
        $obj = $(this);
        start.init(config); // 合并配置项
        start.ajax();
    };

    $.fn.easyReload = function () {
        $obj.datagrid('reload');
    }
    
})(window, jQuery)
