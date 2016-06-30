
function formUserOperation(value, row, index) {
    var string  = '';
    var userId  = row.admin_id;
    var userState = (row.admin_state==1)?'禁用':'启用';
    var state = (row.admin_state==1)?0:1;
    var DS = ' | '
    string += '<a href="/user/edit?admin_id='+userId+'">编辑</a> | ' ;
    string += '<a href="javascript:void(0);" id='+userId+' state="'+state+'" class="deleteBtn">'+userState+'</a> | ';
    string += '<a href="javascript:void(0)" id='+userId+'   class="power">权限管理</a> | ';
    string += '<a href="/user/resetPasswd?id='+userId+'"    class="reset">重置密码</a> | ';
    string += '<a href="javascript:void(0)" id='+userId+'   class="del">删除</a>';
    return string;
}

function formUserState(value, row, index) {
    var str = ''; 
    switch(value) {
        case '0':
            str += '禁用';
            break;
        case '1':
            str += '启用'
            break;
    } 
    return str;
}

function formDatetime(value, row, index){
	if(0 == value) {
		return '';
	}
	return datetimeUtil.formatDatetime("yyyy-MM-dd hh:mm:ss", value);
}

function formSetUserState(value, row, index) {
    switch(value) {
        case '0':
            str += ''
    }
}


/** 课程管理 删除 修改 **/

function formatClassOperation(value,row,index){
	var id = row.class_id;
	 //'<a href="/classUser/list?class_id=' + id + '">用户</a>'
		//+ ' | '
	return	'<a href="/class/edit?class_id=' + id + '">编辑</a>'
		//+ ' | '
		//+ '<a href="javascript:void(0);" class_id="'+id+'" class="deleteBtn">禁用</a>'
		//+ ' | '
		//+ '<a href="http://wap.guanlizhihui.com/class/' + id + '.html" target="_blank">查看</a>';
		//+ '<a href="'+app_url+'/class/' + id + '.html" target="_blank">查看</a>';
}








//======= = 旧 =====
function formatAccessLevel(value,row,index){
	var accessAuth;
	switch(value){
		case 0:
			accessAuth = "普通会员";
			break;
		case 1:
			accessAuth = "亲情会员";
			break;
		case 2:
			accessAuth = "VIP会员";
			break;
		case 3:
			accessAuth = "私塾塾友";
			break;
		case 99:
			accessAuth = "内部会员";
			break;
		case 100:
			accessAuth = "测试人员";
			break;
	
	}
	return accessAuth;
}

function formatCourseStatus(value,row,index){
	var status;
	switch(value){
		case 0:
			status = "预告课程";
			break;
		case 1:
			status = "已结束";
			break;
		case 2:
			status = "进行中";
			break;
	}
    return status;
}

function formatRadioStatus(value,row,index){
    var status;
	switch(value){
		case 0:
			status = "审核通过";
			break;
		case 1:
			status = "待审核";
			break;
		case 2:
			status = "审核不过";
			break;
	}
    return status;
}

function formatCourseType(value,row,index){
	var type;
	switch (value) {
	case 2:
		type = "精品课程";
		break;
	case 1000:
		type = "首席微信官实战训练营";
		break;
	}
	return type;
}


function formatCourseOperation(value,row,index){
	var id = row.id;
	return '<input type="hidden" value="' + id + '" /><a href="/course/courseAddUpd.html?id=' + id + '">修改</a> | <a href="javascript:void(0);" class="deleteBtn">删除</a>';
}

function formatRadioOperation(value,row,index){
	var id = row.id;
	return '<input type="hidden" value="' + id + '" /><a href="/radio/radioAddUpd.html?id=' + id + '">修改</a> | <a href="javascript:void(0);" class="deleteBtn">删除</a>';
}

function formatDuration(value,row,index){
	return datetimeUtil.seconds2DurationStr(value);
}

function formatGroupOperation(value,row,index){
	var class_id = row.class_id;
	var group_id = row.group_id;
	var is_ending = row.is_ending;
	var group_num = row.group_num;

	var edit = '<a href="/group/edit?class_id=' + class_id + '&group_id=' + group_id + '">修改</a>';
	var end = '<a href="javascript:void(0);" class="endBtn" class_id="'+class_id+'"group_id='+group_id+' >结业</a>';
	var del = '<a href="javascript:void(0);" class_id="'+class_id+'"group_id='+group_id+' class="deleteBtn">禁用</a>';
	if(is_ending  == 1 ) {
		return '';
	} else if (group_num > 0 && is_ending  == 0) {
		return edit +' | '+ end;
	} else if (group_num == 0 && is_ending  == 0) {
		return edit +' | '+ end +' | ' + del;
	}
}

function formatIsEnding(value,row,index){
	if(value == 0) {
		return '否';
	} else if(value == 1) {
		return '<span style="color:red;">是</span>';
	}
}

function formatUnameNick(value,row,index){
	var nick = row.nickname;
	var username = row.clientname;
	return username + ' / ' + nick;
}

function formatMobile(value,row,index){
	var mobile = row.mobile;
	var degree = row.degree;
	return mobile + ' / ' + degree;
}

function formatUserPrice(value,row,index)
{
	var userPriceInfo = row.user_price;
	var class_price = row.class_price;
	var one = userPriceInfo[0];
	var tow = userPriceInfo[2];

	one = (one == undefined) ? class_price : one;
	tow = (tow == undefined) ? class_price : tow;
	return class_price + ' / ' + one + ' / ' + tow;
}

function formatCampUserPrice(value,row,index)
{
	var userPriceInfo = row.user_price;
	var camp_price = row.camp_price;
	var one = userPriceInfo[0];
	var tow = userPriceInfo[2];

	one = (one == undefined) ? camp_price : one;
	tow = (tow == undefined) ? camp_price : tow;
	return camp_price + ' / ' + one + ' / ' + tow;
}

function formAllowResell(value,row,index){
	if(value == 0) {
		return '否';
	} else if(value == 1) {
		return '<span style="color:red;">是</span>';
	}
}

function formatIsEnd(value,row,index){
	if(value == 0) {
		return '';
	} else if(value > 0) {
		return datetimeUtil.formatDatetime("yyyy-MM-dd hh:mm:ss",value);
	}
}


/** 训练营管理 删除 修改 **/

function formatCampOperation(value,row,index){
	var id = row.camp_id;
	return '<a href="/place/list?camp_id=' + id + '">训练营地点</a>' 
	+ ' | '
	+ '<a href="/campUser/list?camp_id=' + id + '">用户</a>'
	+ ' | '
	+ '<a href="/camp/edit?camp_id=' + id + '">编辑</a>'
	+ ' | '
	+ '<a href="javascript:void(0);" camp_id="'+id+'" class="deleteBtn">禁用</a>';
}

function formatPlaceStartAndEnd(value, row, index) {
	var startTime = row.place_begin_date;
	var endTime = row.place_end_date;
	return startTime + ' / ' + endTime;
}

function formPlaceStatus(value, row, index)
{
	var placeStatus = row.place_status;
	if (placeStatus == 0) {
		return '未开始';
	} else if(placeStatus == '1') {
		return '进行中';
	} else if(placeStatus == '2') {
		return '已结束';
	}

}

function formatPlaceOperation(value, row, index) 
{
	var placeStatus = row.place_status;
	var place_id = row.place_id;
	var camp_id = row.camp_id;
	var edit = '<a href="/place/edit?place_id='+place_id+'&camp_id='+camp_id+'">修改</a>';
	var del = '<a href="javascript:void(0);" class="deleteBtn" place_id="'+place_id+'">禁用</a>';
	if (placeStatus == 0) {
		return edit + ' | ' + del;
	} else if(placeStatus == '1') {
		return edit;
	} else if(placeStatus == '2') {
		return edit;
	}
}

function formCommisType(value,row,index){
	if(value == 0) {
		return '无佣金';
	} else if(value == 1) {
		return '固定佣金';
	} else if(value == 2) {
		return '订单额百分比';
	}
} 

function formCommisVal(value, row, index) {
	var commis_type = row.commis_type;
	var commis_val = row.commis_val;

	if(commis_type == 0) {
		return commis_val;
	} else if(commis_type == 1) {
		return commis_val + '元';
	} else if(commis_type == 2) {
		return commis_val + '%';
	}
}

function formNumber(value, row, index) {
	var camp_id = row.camp_id;
	return '<a href="/campUser/list?camp_id=' + camp_id + '"> '+value+' </a>'
}

function formPlaceNumber(value, row, index) {
	var camp_id = row.camp_id;
	var place_id = row.place_id;
	return '<a href="/campUser/list?camp_id=' + camp_id + '&place_id='+place_id+'"> '+value+' </a>'
}

function formGetUserLevel(value, row, index)
{
	return row.userInfo.level_desc;
}

function formGetUserUserNameAndNickName(value, row, index)
{
	return row.userInfo.user_name +' / '+ row.userInfo.nick_name;
}

function formGetUserMobileAndWechat(value, row, index)
{
	var mobile = row.userInfo.mobile;
	var degree = row.userInfo.degree;

	if(undefined == degree) {
		return mobile;
	} else {
		return mobile + ' / ' + degree
	}
}

function formGerOrderState(value, row, index)
{
	var status;
	switch(value){
		case '0':
			status = "<span style='color:red'>未支付</span>";
			break;
		case '1':
			status = "已支付";
			break;
	}
    return status;
}

function formOrderTimeAndCreateTime(value, row, index)
{
	var time1 = '';
	var orderTime = row.payment_time;
	var createTime = row.create_time;
	if(orderTime > 0){
		time1 = datetimeUtil.formatDatetime("yyyy-MM-dd hh:mm:ss", orderTime);
	}
	var time2 = datetimeUtil.formatDatetime("yyyy-MM-dd hh:mm:ss", createTime);
	return time2  + "<br/>" +  time1;
}

function formGetUserOrderAmount(value, row, index)
{
	var pd_amount = row.pd_amount;
	var order_amount = row.order_amount;

	if(pd_amount > 0) {
		return order_amount + ' / ' + pd_amount;
	} else {
		return order_amount;
	}
}


function formGetClassOrderState(value, row, index)
{
	var status;
	switch(value){
		case '0':
			status = "已作废";
			break;
		case '10':
			status = "<span style='color:red'>未支付</span>";
			break;
		case '20':
			status = "已支付";
            switch(row.refund_state) {
                case '0' :
                    //status +='(无退款)';
                    break;
                case '1' :
                    status +="(<span style='color:red'>部分退款</span>)";
                    break;
                case '2' :
                    status +="(<span style='color:red''>全部退款</span>)";
                    break;
            }
			break;
	}
    // 退款状态: 0是无退款,1是部分退款,2是全部退款
    
    return status;
}

function formGetUserOrderSnAndPaySn(value, row, index)
{
	var order_sn = row.order_sn;
	var pay_sn	 = row.pay_sn;

	if(pay_sn != null) {
		return order_sn + '<br/>' + pay_sn;
	} else {
		return order_sn;
	}

}

function formatChangePower(value, row, index)
{
    var recId = row.rec_id;
	var user_id = row.id;
	var class_id = $('input[name=class_id]').val();
    var applyState = row.apply_state;

    if (applyState == 1) {

	    return '<a href="/change/power?class_id='+class_id+'&user_id='+user_id+'">更换权限</a>  <a href="javascript:void(0);" class="refund" rec="'+recId+'">申请退款</a>';

    } else {

        return '';

    }
}

function formUserNumber(value, row, index) {
	var class_id = row.class_id;
	return '<a href="/classUser/list?class_id=' + class_id + '"> '+value+' </a>'
}

function formatApplyState(value, row, index)
{
    if (value == 1) {

        return '报名成功';

    } else if (value == 2) {

        return '<span style="color:red">已取消报名</span>';

    } else if (value == 3) {

        return '<span style="color:blue">权限已转移</span>';

        return '权限已转移';

    }
}
