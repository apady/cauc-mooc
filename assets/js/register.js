require('../sourse/register.css');



$(function () {
    //发送验证码
    $("#getpi").click(function () {
        var disabled=$("#getpi").attr("disabled");
        if(disabled){
            return false;
        }

        var phonenumber=$("#phonenumber").val();


        $.ajax({
            async:false,
            type: "POST",
            url: "register/code-sender",
            data: {"phone":phonenumber},
            dataType: "text",
            async:false,
            success:function(data){
                messageOk(data);
                var countdown=60;
                var sendbutton=document.getElementById('getpi');
                //倒计时
                var timeinterval=setInterval(function () {
                    if (countdown==0){
                        sendbutton.removeAttribute("disabled");
                        sendbutton.innerHTML="点击获取";
                        countdown = 60;
                        clearInterval(timeinterval);
                    }
                    else{
                        sendbutton.setAttribute("disabled",true);
                        sendbutton.innerHTML="重新发送(" + countdown + ")";
                        countdown--;
                    }

                },1000)

            },
            error:function(err){
                console.log(err);
            }
        });

    })



    //警告信息
    function erroralarm(info) {
        //提示框抖动
        var tip=document.getElementsByClassName("tip")[0];
        tip.style.display="block";
        tip.innerHTML=info;
        var a = true;
        var startTime = new Date().getTime();
        var interval = setInterval(function() {
            /*
            * 根据a的值，做不同的设置
            * */
            tip.style.left = (a ? -3 : 3) + 'px';

            a = !a;
            if(new Date().getTime() - startTime > 400){
                clearInterval(interval);}
        }, 50);
    }
    function closealarm() {
        var tip=document.getElementsByClassName("tip")[0];
        tip.style.display="none";

    }
    // 用户名校验
    $("#username").on('input propertychange',function () {
        var uPattern = /^[a-zA-Z0-9_-]{4,16}$/;

        if(uPattern.test($("#username").val())){
            $.ajax({                                 //AJAX
                type:"post",
                url:"check/username",                      //请求URL,对应后台Controller中的路由
                data:{"username": $("#username").val()},//往后台提交的数据项
                success:function(data){                    //后台返回响应结果时会自动执行该回调函数
                    if(data==1)
                    {
                        erroralarm("用户名已存在");

                        $("#tiplight1").css({"display":"block","background":"#e4574c"});
                        isusername=false;
                    }
                    if(data==0) {
                        flag1=1;
                        $("#tiplight1").css({"display":"block","background":"#9ddd54"});
                        closealarm();
                        isusername=true;

                    }
                },
                dataType:"text"
            })
        }
        else{
            $("#tiplight1").css({"display":"block","background":"#e4574c"});
            isusername=false;
        }
    })
    //密码校验
    $("#password").on('input propertychange',function () {
        if($("#password").val()!=""){
            if($("#password").val().length<6||$("#password").val().length>16)
            {

                $("#tiplight2").css({"display":"block","background":"#e4574c"});
                ispassword=false;
            }
            else{
                $("#tiplight2").css({"display":"block","background":"#9ddd54"});
                ispassword=true;

            }
        }
    })
    //手机号校验
    $("#phonenumber").on('input propertychange',function () {
        var mPattern = /^(((13[0-9])|(14[579])|(15([0-3]|[5-9]))|(16[6])|(17[0135678])|(18[0-9])|(19[89]))\d{8})$/;

        if(mPattern.test($("#phonenumber").val())) {
            $.ajax({                                 //AJAX
                type:"post",
                url:"check/mobile",                      //请求URL,对应后台Controller中的路由
                data:{"mobile": $("#phonenumber").val()},//往后台提交的数据项
                success:function(data){                    //后台返回响应结果时会自动执行该回调函数
                    if(data==1)
                    {
                        erroralarm("手机号已存在");

                        $("#tiplight3").css({"display":"block","background":"#e4574c"});
                        document.getElementById("getpi").setAttribute("disabled",true);
                        isphone=false;
                    }
                    if(data==0) {
                        flag1=1;
                        $("#tiplight3").css({"display":"block","background":"#9ddd54"});
                        closealarm();
                        document.getElementById("getpi").removeAttribute("disabled");
                        isphone=true;

                    }
                },
                dataType:"text"
            })

        }
        else{
            $("#tiplight3").css({"display":"block","background":"#e4574c"});
            isphone=false;
            document.getElementById("getpi").setAttribute("disabled",true);
        }
    })
    //验证码长度和类型校验
    $("#yanzhengma").on('input propertychange',function () {
        if($("#yanzhengma").val() == "" || isNaN($("#yanzhengma").val()) || $("#yanzhengma").val().length != 6 ) {
            iscode=false;
        }
        else{
            iscode=true;
        }
    })
    //协议校验
    $("#agree").change(function() {
        if($("#agree").is(':checked')){
            isagree=true;
        }
        else{
            isagree=false;
        }
    });
    // 提交按钮触发
    var isusername=false;
    var ispassword=false;
    var isphone=false;
    var isagree=false;
    var iscode=false;

    $("#username,#password,#phonenumber,#yanzhengma,#agree").on("change",function () {
        if(isusername+ispassword+isphone+isagree+iscode==5)
            document.getElementById("register-submit").removeAttribute("disabled");
        else
            document.getElementById("register-submit").setAttribute("disabled",true);
    })
    //提交信息
    $("#register-submit").on("click",function () {
        $.ajax({
            type:"post",
            url:"register/submit",
            data:{"username": $("#username").val(),"password": $("#password").val(),"mobile": $("#phonenumber").val(),"code": $("#yanzhengma").val()},
            success:function(data){
                if(data==1){
                    erroralarm("验证码错误");
                }
                if(data==2){

                    messageOk("注册成功！");
                    $("#tab-login").click();
                }
            },
            dataType:"text"
        })
    })

})



