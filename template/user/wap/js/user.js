(function() {

    function isMoblie() {
        if(!navigator.userAgent.match(/(iPhone|iPod|Android|ios)/i)){
            $("body").addClass("app_NoMoblie");
        }else{
            $("body").removeClass("app_NoMoblie");
        }
    }

    $('body').on('click','.app_meskSection',function () {
        $('.app_header_second').hide();
        $('.app_meskSection').hide();
        $('#header').removeClass('app_headerFixed2');
        $('.moreIco').removeClass('closeIco');
    });

    $('.app_header_back').click(function () {
        window.history.go(-1);
    });

    $('.moreIco').click(function () {
        if($(this).hasClass('closeIco')){
            $('#header').removeClass('app_headerFixed2');
            $(this).removeClass('closeIco');
            $('.app_header_second').hide();
            $('.app_meskSection').hide();
        }else{
            $('#header').addClass('app_headerFixed2');
            $(this).addClass('closeIco');
            $('.app_header_second').show();
            $('.app_meskSection').show();
        }
    });

    /** 点击完成按钮 */
    $('#success').click(function () {
        $('#administration').show();
        $(this).hide();
        $('#list .rightJustTime').show();
        $('#list .deleteBlock').hide();
    });

    /** 点击管理 */
    $('#administration').click(function () {
        $(this).hide();
        $('#success').show();
        $('#list .rightJustTime').hide();
        $('#list .deleteBlock').show();

    });

    $(window).resize(function () {
        isMoblie();
    });

    newcode=function(){
        var verifyimg = $("#imgCode").attr("src");
        if (verifyimg.indexOf('?') > 0) {
            $("#imgCode").attr("src", verifyimg + '&random=' + Math.random());
        } else {
            $("#imgCode").attr("src", verifyimg.replace(/\?.*$/, '') + '?' + Math.random());
        }
        $("#code").val('');
    }

    $("#imgCode").click(function() {
        newcode();
    });

    var i,intervalid;
    $('.codeBtn').click(function(){
        var send_data = JSON.parse($(this).attr('send-data'));
        send_data['type'] = 'passw';
        $.get($(this).attr('send-url'),send_data,function(data){
            if(data.code==1){
                layer.open({content: data.msg,time: 4});
                $('.codeBtn').attr({"disabled":"true"});
                i=180;
                intervalid = setInterval("codefun()",1000);
            }else{
                layer.open({content: data.msg,time: 2});
            }
        });
    });

    codefun=function(){
        if (i == 0){
            $(".codeBtn").text("获取验证码").removeAttr("disabled");
            clearInterval(intervalid);
        }else{
            $('.codeBtn').text(i+"秒后重获取");
            i--;
        }
    }

})();