// var ADMIN_SITE_URL = '';
var _menus = {
   "default": [
        {
            "menuid":"1","icon": "icon-course", "menuname": "企业",
            "menus":[
     	         {"menuid": "11", "menuname": "企业管理", "icon": "icon-course_1", "url": "#"},
     	         {"menuid": "12", "menuname": "店铺管理", "icon": "icon-course_1", "url": "#"},
     	         {"menuid": "12", "menuname": "店铺海报", "icon": "icon-course_1", "url": "/poster/list"},
            ]
        },
       {
           "menuid":"3","icon": "icon-course", "menuname": "课程管理",
           "menus":[
     	         {"menuid": "21", "menuname": "VIP在线课程管理", "icon": "icon-course_1", "url": "#"},
     	         {"menuid": "22", "menuname": "线上课程管理", "icon": "icon-course_2", "url": "/class/list"},
     	         {"menuid": "23", "menuname": "线上课程章节管理", "icon": "icon-course_3", "url": "#"},
     	         {"menuid": "24", "menuname": "线下训练营管理", "icon": "icon-course_4", "url": "/camp/list"}
            ]
       },
       {
           "menuid":"4","icon": "icon-order", "menuname": "订单管理",
           "menus":[
     	         {"menuid": "31", "menuname": "VIP订单管理", "icon": "icon-order_1", "url": "/order/vip"},
     	         {"menuid": "32", "menuname": "线上课程订单管理", "icon": "icon-order_2", "url": "/order/class"},
     	         {"menuid": "33", "menuname": "线下课程订单管理", "icon": "icon-order_3", "url": "/order/camp"},
     	         //{"menuid": "94", "menuname": "线下训练营管理", "icon": "icon-course_4", "url": "http://adminbkp1.guanlizhihui.com/camp/list"}
            ]
       },
       {
            "menuid": "10", "icon": "icon-user", "menuname": "用户管理",
            "menus": [
                 { "menuid": "91", "menuname": "用户管理", "icon": "icon-user_1", "url": "/user/list" },
                 { "menuid": "92", "menuname": "个人设置", "icon": "icon-user_2", "url": "/user/people" },
             ]
        },
        {
            "menuid": "11", "icon": "icon-user", "menuname": "功能管理",
            "menus": [
                 { "menuid": "101", "menuname": "功能管理", "icon": "icon-user_1", "url": "#" },
              
             ]
        }

       ]
	};

$(function() {
		tabClose();
		tabCloseEven();
		// $.ajax({
		// 	url:GLZH_ADMIN_API.UserMenu['getMenu'],
		// 	async:false,
		// 	dataType:"JSON",
		// 	success:function(data){
		// 		_menus = eval('('+ "{'default':"+data.data+"}"+')');
		// 	}
		// });
    		// 导航菜单绑定初始化
    		addNav(_menus['default']);
    		InitLeftMenu();

    	});

      function showSubMenu(url, title, menuCategory, defaultIcon) {
          if (defaultIcon == null || defaultIcon == "") {
              defaultIcon = "icon-table";
          }
         // addTab(title, url, "icon " + defaultIcon);
          Clearnav();
          if (menuCategory != "") {
              addNav(_menus[menuCategory]);
          }
      }

    //增加
      function addNav(data) {

          $.each(data, function(i, sm) {
        	  //var paricon = "background:url('"+sm.icon+"') no-repeat;width:18px;line-height:18px; height:18px;display:inline-block;";
        	  var paricon = "width:18px;line-height:18px; height:18px;display:inline-block;";
              var menulist = "";
              menulist += '<ul>';
			 	$.each(sm.menus, function(j, o) {
			 	//var childicon = "background:url('"+o.icon+"') no-repeat;width:18px;line-height:18px; height:18px;display:inline-block;";
			 	var childicon = "width:18px;line-height:18px; height:18px;display:inline-block;";
				menulist += '<li><div><a ref="' + o.menuid + '" href="#" rel="'
				+ o.url + '" ><span style="'+childicon+'" >&nbsp;</span><span class="nav">' + o.menuname
				+ '</span></a></div></li> ';
			});
			menulist += '</ul>';

              $('#wnav').accordion('add', {
            	  id:sm.menuid,
                  title : sm.menuname,
                  content : menulist,
                  iconCls : 'panel-icon',
              });
             $("#"+sm.menuid).parent("div").find(".panel-icon").attr("style",paricon)
          });
          var pp = $('#wnav').accordion('panels');
          var t = pp[0].panel('options').title;
          $('#wnav').accordion('select', t);

      }

      function Clearnav() {
      	var pp = $('#wnav').accordion('panels');

      	$.each(pp, function(i, n) {
      		if (n) {
      			var t = n.panel('options').title;
      			$('#wnav').accordion('remove', t);
      		}
      	});

      	pp = $('#wnav').accordion('getSelected');
      	if (pp) {
      		var title = pp.panel('options').title;
      		$('#wnav').accordion('remove', title);
      	}
      }
      function hoverMenuItem() {
      	$(".easyui-accordion").find('a').hover(function() {
      		$(this).parent().addClass("hover");
      	}, function() {
      		$(this).parent().removeClass("hover");
      	});
      }
      function InitLeftMenu() {

      	hoverMenuItem();

      	$('#wnav li a').live('click', function() {
      		var tabTitle = $(this).children('.nav').text();

      		var url = $(this).attr("rel");
      		var menuid = $(this).attr("ref");
      		var icon = getIcon(menuid);

      	 	addTab(menuid,tabTitle, url, icon);
      		//$('#wnav li div').removeClass("selected");
      		//$(this).parent().addClass("selected");
      	});

      }
      function addTab(menuid,subtitle, url, icon) {
      	if (!$('#centerTab').tabs('exists', subtitle)) {
      		$('#centerTab').tabs('add', {
      			title : subtitle,
      			content : createFrame(url),
      			closable : true,
      			icon : 'tabs-icon',
      		});
      		$(".tabs-selected .tabs-icon").attr("style",icon);
      	} else {
      		$('#centerTab').tabs('select', subtitle);
      		$('#mm-tabupdate').click();
      	}
      	tabClose();
      }

      function createFrame(url) {
      	var s = '<iframe scrolling="auto" frameborder="0"  src="' + url + '" style="width:100%;height:100%;"></iframe>';
      	return s;
      }
   // 获取左侧导航的图标
      function getIcon(menuid) {
      	var icon = "background:url('";
      	$.each(_menus, function(i, n) {
      		$.each(n, function(j, o) {
      			$.each(o.menus, function(k, m){
      				if (m.menuid == menuid) {
      					icon += m.icon+"') no-repeat;width:18px;line-height:18px; height:18px;display:inline-block;";;
      					return false;
      				}
      			});
      		});
      	});
      	return icon;
      }

      function tabClose() {
      	/* 双击关闭TAB选项卡 */
      	$(".tabs-inner").dblclick(function() {
      		var subtitle = $(this).children(".tabs-closable").text();
      		$('#centerTab').tabs('close', subtitle);
      	});
      	/* 为选项卡绑定右键 */
      	$(".tabs-inner").bind('contextmenu', function(e) {

      		$('#mm').menu('show', {
      			left : e.pageX,
      			top : e.pageY
      		});

      		var subtitle = $(this).children(".tabs-closable").text();

      		$('#mm').data("currtab", subtitle);
      		$('#centerTab').tabs('select', subtitle);
      		return false;

      	});
      }

   // 绑定右键菜单事件
      function tabCloseEven() {
      	// 刷新
      	$('#mm-tabupdate').click(function() {
      		var currTab = $('#centerTab').tabs('getSelected');
      		var url = $(currTab.panel('options').content).attr('src');
      		$('#centerTab').tabs('update', {
      			tab : currTab,
      			options : {
      				content : createFrame(url)
      			}
      		});
      	});
      	// 关闭当前
      	$('#mm-tabclose').click(function() {
      		var currtab_title = $('#mm').data("currtab");
      		$('#centerTab').tabs('close', currtab_title);
      	});
      	// 全部关闭
      	$('#mm-tabcloseall').click(function() {
      		$('.tabs-inner span').each(function(i, n) {
      			var t = $(n).text();
      			$('#centerTab').tabs('close', t);
      		});
      	});
      	// 关闭除当前之外的TAB
      	$('#mm-tabcloseother').click(function() {
      		$('#mm-tabcloseright').click();
      		$('#mm-tabcloseleft').click();
      	});
      	// 关闭当前右侧的TAB
      	$('#mm-tabcloseright').click(function() {
      		var nextall = $('.tabs-selected').nextAll();
      		if (nextall.length == 0) {
      			return false;
      		}
      		nextall.each(function(i, n) {
      			var t = $('a:eq(0) span', $(n)).text();
      			$('#centerTab').tabs('close', t);
      		});
      		return false;
      	});
      	// 关闭当前左侧的TAB
      	$('#mm-tabcloseleft').click(function() {
      		var prevall = $('.tabs-selected').prevAll();
      		if (prevall.length == 0) {
      			return false;
      		}
      		prevall.each(function(i, n) {
      			var t = $('a:eq(0) span', $(n)).text();
      			$('#centerTab').tabs('close', t);
      		});
      		return false;
      	});

      	// 退出
      	$("#mm-exit").click(function() {
      		$('#mm').menu('hide');
      	});
      }
