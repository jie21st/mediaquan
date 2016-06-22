$(function(){
    var $obj = $('#userTable')
    var queryParams = {};
    var config = { // 配置项
        url : '/user/getUserList',    // 请求路径
        queryParams : queryParams, // 变量
        loadMsg : '数据加载中,请稍后...',  //消息
        pageList : [ 10, 20, 30 ], // 设置每页显示多少条
        toolbar:[
            {
				 	text:'添加用户',
				 	iconCls:'icon-add',
				 	handler:function(){
				 		window.location.href = '/user/add';
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
                var user_id = $(this).attr('id');
                //userPower(function(){
            	    var data = {'user_id':user_id};
                    confirmDelete("确认禁用该用户吗?", function(){
                        getListAndReload('/user/del', data);
                        //$obj.easySubmitAjax(config); // 获取数据
                    });
                //}, '106009009');
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
        var userName = $('#user_name').val();
        var mobile = $('#mobile').val();

        queryParams['user_name'] = userName;
        queryParams['mobile'] = mobile;
        $obj.easySubmitAjax(config);
    })
})
