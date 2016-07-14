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
				 }

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

    //绑定课件类型的change事件
    $("#mediaType").bind("change",function () {
        var val = $("#mediaType").val();
        if("1" == val){//视频
            $("#audioId").hide();
            // $("#pdfId").hide();
            $("#videoId").show();
        }else{//音频
            $("#videoId").hide();
            $("#audioId").show();
            // $("#pdfId").show();
        }
    });

    //获取老师名字
    $('#class_id').change(function(){
        var class_id = $(this).val();
        if(class_id == '-1'){
            $('#teacher_name').val('');
            return false;
        }else{
            $.ajax({
                type: "post",
                url: "/chapter/getTeacherName",
                data: {'class_id':class_id},
                dataType: "json",
                success: function (data, textStatus) {
                    if (data.code == 0) {
                        $.messager.alert('提示', data.msg, 'warning');
                    } else {
                        $('#teacher_name').val(data.teacher_name);
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    $.messager.alert('提示', '数据添加失败,请重新提交或者寻求管理员帮助', 'error');
                }
            })
        }
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
