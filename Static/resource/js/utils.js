var Util = {
    /**
     * 格式化时长
     *
     * @param int   time  时长单位为秒
     * @return string 12:05
     */
    formatTime: function(time) {
        var i = 0,
            s = parseInt(time);
        if (s > 60) {
            i = parseInt(s / 60);
            s = parseInt(s % 60);
        }
        return this.strPad(i, 2) + ':' + this.strPad(s, 2);
    },

    /**
     * 使用另一个字符串填充字符串为指定长度 
     * @todo pad_string长度大于1后处理
     *
     * @param string num
     * @param int    n
     * @return string
     */
    strPad: function(input, pad_length, pad_string, pad_type) {
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
    },

    /**
     * 格式化日期
     *
     * @param string format    日期格式 (yyyy-MM-dd h:m:s)
     * @param int    timestamp 时间戳
     * @return string
     */
    formatDate: function(format, timestamp) {
        if (timestamp == '') {
            return '';
        }

        var d = new Date(parseInt(this.strPad(timestamp, 13, '0', 'right')));
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
    },

    /**
     * 计算字符串长度
     *
     */
    getStrLen: function(str){
        return str.replace(/[^\x00-\xff]/g, "3p").length;
    },

    /**
     * 字符串截取
     *
     * @param string     字符串
     * @param textLength 截取长度 
     * @return string
     */
    truncateText: function(str, textLength) {
        var length = textLength || 12;
        if (str) {
            var f = str.substr(0, length);
            if (f.length < str.length) {
                f += "..."
            }
            return f;
        }
        return str;
    },
    transSpecialSymbol:function(str){
    	var htmlChar="&<>";
    	if(null == str ||"undefined" == typeof(str) || ""==str ){
    		return str;
    	}
    	var temstr=str;
    	var strArr = temstr.split(''); 
    	for(var i = 0; i< temstr.length;i++){
    		if(htmlChar.indexOf(str.charAt(i)) !=-1){
    			switch (str.charAt(i)) { 
    			case '<':
    				strArr.splice(i,1,'&#60;'); 
    				break;
    			case '>':
    				strArr.splice(i,1,'&#62;'); 
    				break; 
    			case '&':
    				strArr.splice(i,1,'&#38;'); 
    			}
    			
    		}
    		
    	}
    	
    	return strArr.join('');	
    },
    jsDateDiff: function (publishTime){       
        var d_minutes,d_hours,d_days;       
        var timeNow = parseInt(new Date().getTime()/1000);       
        var d;       
        d = timeNow - publishTime/1000;       
        d_days = parseInt(d/86400);       
        d_hours = parseInt(d/3600);       
        d_minutes = parseInt(d/60); 
        var  d_second=parseInt(d/1);
        if(d_days>0 && d_days<4){       
            return d_days+"天前";       
        }else if(d_days<=0 && d_hours>0){       
            return d_hours+"小时前";       
        }else if(d_hours<=0 && d_minutes>0){       
            return d_minutes+"分钟前";       
        }else if(d_second>=0 && d_minutes<=0 )
        {
        	//return "刚刚"; 
        	if(d_second<=30){
        		return "刚刚"; 
        	}
        	return d_second+"秒前"; 
        } 	
        else{       
               
            return this.formatDate('yyyy.MM.dd',publishTime);       
        }       
    },
    
    /**
     * 轮询
     *
     * milliSec：每隔多少毫秒轮询一次
     * maxTimes：最多轮询多少次
     *
     */
    loop: function(fn, milliSec, maxTimes) {
        milliSec = milliSec || 10000;
        maxTimes = maxTimes || 2;
        var _interval = -1;
        var instance = {
            times: 0
        };

        var exec = function() {
            if (instance.times >= maxTimes) {
                instance.stop();
            } else {
                fn();
                if (instance && typeof instance.times == 'number') {
                    instance.times++;
                }
            }
        };

        instance.start = function() {
            exec();
            _interval = window.setInterval(function() {
                exec();
            }, milliSec);
        };
        instance.stop = function() {
            _interval = window.clearInterval(_interval);
            instance = null;
        };

        instance.pause = function() {
            _interval = window.clearInterval(_interval);
        };

        instance.pause();
        instance.start();

        return instance;
    }
}
