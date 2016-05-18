/* 
* @Author: zenghp
* @Date:   2016-01-11 09:04:30
* @Last Modified by:   zenghp
* @Last Modified time: 2016-01-11 09:21:44
*/

var start = 0;
var myScroll, pullDownEl, pullDownOffset, pullUpEl, pullUpOffset;
var isGet = false;

var next = true;

//初始化绑定iScroll控件
document.addEventListener('touchmove',
function(e) {
    e.preventDefault();
},
false);
document.addEventListener('DOMContentLoaded', loaded, false);

/**
* 下拉刷新
*
*/
function pullDownAction() {
    drawItem(false);
}

/**
* 滚动翻页
*/
function pullUpAction() {
    drawItem(true);
}

/**
* 初始化iScroll控件
*/
function loaded() {
    myScroll = new iScroll('wrapper', {
        scrollX: true,
        scrollY: false,
        bounce: false,
        hScrollbar: false,
        vScrollbar: false,
        snap: true,
        //此处必须为true
        momentum: false,
        lockDirection: true,
        fixedScrollbar: false,
        //只有按住屏幕的时候才能显示出滚动条
        onScrollMove: function() {

            if (this.distX > 0 && this.absDistX > (this.absDistY + 5)) {
                next = false
            }
            if (this.distX < 0 && this.absDistX > (this.absDistY + 5)) {
                next = true
            }
            /*
             if(this.distY >0 &&  this.absDistY > (this.absDistX + 5 ) ){
             pullDownAction ()
             }
             if(this.distY<0 &&  this.absDistY > (this.absDistX + 5 ) ){
             pullUpAction ();
             }
             */
        },

        onScrollEnd: function() {
            if (next) pullUpAction();
        }
    });

    setTimeout(function() {
    	pullUpAction();
        document.getElementById('wrapper').style.left = '0';
	audio.init();
    },
    800);

    var pptWidth = document.getElementById('wrapper').clientWidth;
    document.getElementById('scroller').style.width = pptWidth * pages + "px";

    // console.log(document.getElementById('body').clientHeight);
    //document.getElementById('wrap').style.top = document.getElementById('wrapper').clientHeight + "px";
    document.getElementById('warp').style.top = (document.getElementById('body').clientHeight-50) + "px";
}

/**
* 显示获取的记录
*/

function drawItem(isFarword) {
    var el = document.getElementById('thelist');
    var pptWidth = parseInt(document.getElementById('wrapper').clientWidth);
    var audioHeight = parseInt(document.getElementById('warp').clientHeight);
    var pptHeight = parseInt(document.getElementById('wrapper').clientHeight);
    var i = 0;
    var ppts = getPptWidth();

    //alert(pptWidth);
    //alert(pptHeight);
    //alert(audioHeight);

    var imagesWidth='';
    var imagesHeight='';
    if(ppts == 'ppt1') {
        imagesWidth='405';
        imagesHeight='720';
    } else {
        imagesWidth='720';
        imagesHeight='405';
    }

   var width = imagesWidth*(pptHeight-50)/imagesHeight;
   //var height = imagesHeight*pptWidth/imagesWidth - 50;
   //alert(height);
    //每次huaping预读两页
    for (; start < pages && i < 2; i++) {
        start++;
        var file = start < 10 ? "0" + start: start;
        var div = document.createElement('div');
        div.setAttribute("id", file);
        div.setAttribute("class", "ppt");
        div.style.width = pptWidth + 'px';
        div.style.height = pptHeight + 'px';
        el.appendChild(div);
        div.innerHTML = "<div><a href='#'><img id='ppt' width='"+width+"' src='/courses/getPpt/id/"+get_id+"/type/"+ppts+"/file_id/"+file+"'></a></div>";
    }
    if (i > 0) myScroll.refresh();
    isGet = false;
}

function getPptWidth() {

    //平台、设备和操作系统
    var system = {
        win: false,
        mac: false,
        xll: false
    };
    //检测平台
    var p = navigator.platform;

    system.win = p.indexOf("Win") == 0;
    system.mac = p.indexOf("Mac") == 0;
    system.x11 = (p == "X11") || (p.indexOf("Linux") == 0);

    //跳转语句
    if (system.win || system.mac || system.xll) { 
        return 'ppt';
    } else {
        return 'ppt1';
    }
}
