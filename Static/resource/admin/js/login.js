function login(){
    var Account=$("#str").val().trim();
    var password=$("#pwd").val().trim();

    if(Account==""){
        $("#err-tiper").html("请输入邮箱/手机号");
        return;
    }else if((Account!="")&&(password=="")){
        $("#err-tiper").html("请输入密码");
        return;
    }

    $.ajax({
        type: "post",
        url:  "/login/verify",
        data: {user:Account,passwd:password},
        dataType:'json',

        success:function(data){
            if(data.code=='1'){
                location.href='/index';
            }else{
                if(data.code=='0' && data.flag=='2'){
                    $("#err-tiper").html('用户已被禁用');
                }else{
                    $("#err-tiper").html('用户名或密码错误，请您核对您的用户名和密码');
                }
            }
        },
        error:function(data){
            alert("登录遇到问题，请联系管理员");
        }
    });
}
