$(function(){
    var $obj = $('#chapterTable')
    var queryParams = {};
    var config = { // 配置项
        url : '/chapter/getchapterlist',    // 请求路径
        queryParams : queryParams, // 变量
        loadMsg : '数据加载中,请稍后...',  //消息
        pageList : [ 10, 20, 30 ], // 设置每页显示多少条
        toolbar:[
            {
				 	text:'添加课程章节',
				 	iconCls:'icon-add',
				 	handler:function(){
				 		window.location.href = '/chapter/add';
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
        //onLoadError : function() { alert('数据加载失败!') },
        onLoadSuccess : function() {
            $(".del").click(function() {
                var chapter_id = $(this).attr('chapter_id');
                var data = {'chapter_id':chapter_id};
                confirmDelete("确认禁用该课程吗?", function(){
                    getListAndReload('/chapter/del', data);
                });
			});
        },
    };

    var getListAndReload = function (url, data) { // 获取数据重载
        $.post(url, data, function(){
            $obj.easyReload();
        },'json');
    };

    $obj.easySubmitAjax(config); // 获取数据
    // 查询
    $('#queryBtn').on('click', function(){
        var chapter_title = $('#chapter_title').val();
        var status = $('#status').val();
        queryParams['chapter_title'] = chapter_title;
        queryParams['status'] = status;
        $obj.easySubmitAjax(config);
    });


    //禁用课程
    //$('.del').on('click', function(){
    //    alert('aa');
    //    var chapter_id    = $(this).attr('chapter_id');
    //    var data        = {'chapter_id':chapter_id};
    //    confirmDelete('确定禁用该课程?', function(){
    //        getListAndReload('/chapter/del', data);
    //    })
    //});

});
