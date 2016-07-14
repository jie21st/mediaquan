/*
* @Author: zenghp
* @Date:   2016-03-08 17:13:56
* @Last Modified by:   zenghp
* @Last Modified time: 2016-03-30 17:47:15
*/
function uploads(file, url, success, error)
{
    var data = new FormData;
    var images = file[0];
    var imagesType = [
        'image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'audio/mp3',  'video/mp4', 'application/pdf'
    ];
    var types = imagesType.indexOf(images.type);
    var size = images.size;
    if(images !== '' && types != '-1' && size > '0') {
        $.each(file, function(i, files) {
            data.append('upload_file', files);
        });

        $.ajax({
            url:url,
            type:'POST',
            data:data,
            cache: false,
            contentType: false,    //不可缺
            processData: false,    //不可缺
            success:function(e){
                (e.code == 0) ? error(e.msg) : success(e.data);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert("上传失败，请检查网络后重试");
            }
        });
    } else {
        alert('请上传png, jpg, jpeg, gif 的图片');
    }
}

function confirmDelete(msg,success) {
    $.messager.confirm('提示', msg, function(r){
        if(r){
            success();
        }
    })

    /**var confirmDelete = confirm(msg);

    if(!confirmDelete){
        return false;
    } else {
        return true;
    } **/
}



function userPower(success, power_id) {
    var powerId  = (power_id != undefined) ? power_id : $('input[name=power_id]').val();
    var url = '/power/getUserPower';
    $.post(url, {"power_id":powerId}, function(e){
        if (e.code == 1) {
            success();
        } else {
            $.messager.alert("提示", '对不起，您无此权限，如需开通请联系管理员');
            //error();
        }
    });
}
