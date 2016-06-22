$(function(){
    var $obj = $('#classTable')
    var queryParams = {};
    var config = { // 配置项
        url : '/class/getclasslist',    // 请求路径
        queryParams : queryParams, // 变量
        loadMsg : '数据加载中,请稍后...',  //消息
        pageList : [ 10, 20, 30 ], // 设置每页显示多少条
        toolbar:[
            {
				 	text:'添加课程',
				 	iconCls:'icon-add',
				 	handler:function(){
				 		window.location.href = '/class/add';
				 	}
				 },

		    //      {
				  //   text:'批量删除',
				  //   iconCls:'icon-cancel',
				  //   handler:function(){
				  //   	confirmDelete("确认删除选中的这些课程吗?");
				  //   	var radioIds = [];	
				  //   	var selectedRadios = $obj.datagrid('getSelections');
				  //   	for(var i = 0;i<selectedRadios.length;i++){
				  //   		radioIds.push(selectedRadios[i].class_id); 
						// }
						// var data = {'class_id':radioIds};
						// getListAndReload('/class/del',  data);
				  //   }
		    //      }
        ],
        onLoadError : function() { alert('数据加载失败!') },
        onLoadSuccess : function() {
            $(".deleteBtn").click(function() {
                var class_id = $(this).attr('class_id');
                userPower(function(){
            	    var data = {'class_id':class_id};
                    confirmDelete("确认禁用该课程吗?", function(){
                        getListAndReload('/class/del', data);
                    });
                }, '106009009');
			});
        },
    };

    var getListAndReload = function (url, data) { // 获取数据重载
        $.post(url, data, function(){
            $obj.easyReload();
        },'json');
    }

    $obj.easySubmitAjax(config); // 获取数据
    // 查询
    $('#queryBtn').on('click', function(){
        var class_title = $('#class_title').val();
        var class_teacher = $('#class_teacher').val();
        queryParams['class_title'] = class_title;
        queryParams['class_teacher'] = class_teacher;
        $obj.easySubmitAjax(config);
    })
})
