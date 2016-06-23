var datetimeUtil = {};

datetimeUtil.seconds2DurationStr = function(totalSeconds){
	var minutes = Math.floor(totalSeconds / 60);
	minutes = minutes < 10 ? "0" + minutes : minutes;
	var seconds = Math.floor(totalSeconds % 60);
	seconds = seconds < 10 ? "0" + seconds : seconds;
	return minutes + ":" + seconds;
};

datetimeUtil.getDurationBySeconds = function(minutes,seconds){
	return minutes*60 + seconds*1;
};

datetimeUtil.getHours = function(totalSeconds){
	return Math.floor(totalSeconds/3600);
};

datetimeUtil.getMinutes = function(totalSeconds){
	var minutes = Math.floor(totalSeconds / 60);
	return minutes < 10 ? "0" + minutes : minutes;
};

datetimeUtil.getSeconds = function(totalSeconds){
	var seconds = Math.floor(totalSeconds % 60);
	return seconds < 10 ? "0" + seconds : seconds;
};


datetimeUtil.durationStr2Seconds = function(durationStr){
	var strArr = durationStr.replace(/\s/g,"").split(":");
	var length = strArr.length;
	if(length == 2){ // mm:ss
		return strArr[0] * 60 + strArr[1] * 1; 
	}else if(length == 3){ // hh:mm:ss
		return strArr[0] * 3600 + strArr[1] * 60 + strArr[2] * 1; 
	} 
};

datetimeUtil.formatTime = function(time) {
    var i = 0,
        s = parseInt(time);
    if (s > 60) {
        i = parseInt(s / 60);
        s = parseInt(s % 60);
    }
    return this.strPad(i, 2) + ':' + this.strPad(s, 2);
};

datetimeUtil.strPad = function(input, pad_length, pad_string, pad_type) {
    var pad_string = pad_string || '0';
    var pad_type   = pad_type || 'left';
    var i = (input + '').length;
    while (i++ < pad_length) {
        if (pad_type == 'left') {
            input = pad_string + input;
        } else {
            input = input + pad_string
        }
    }
    return input;
};

datetimeUtil.formatDatetime = function(format, timestamp) {
    if (timestamp == '') {
        return '';
    }

    var d = new Date(parseInt(datetimeUtil.strPad(timestamp, 13, '0', 'right')));
    var date = {
        "M+": d.getMonth() + 1,
        "d+": d.getDate(),
        "h+": d.getHours(),
        "m+": d.getMinutes(),
        "s+": d.getSeconds(),
        "q+": Math.floor((d.getMonth() + 3) / 3),
        "S+": d.getMilliseconds()
    };
    if (/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (d.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in date) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
        }
    }
    return format;
};