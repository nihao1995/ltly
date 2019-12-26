// JavaScript Document
/*需求大厅二维码*/
$(function () {

	$('#shijian').hover(function () {
		layer.tips('厕所', '#shijian',{
			tips: [4, '#78BA32'],
			time: 0
		});
	},function(){
		//layer.closeAll('tips');
	});	
    $('.jy-task').hover(function () {
        $("li").removeClass('jy-task2');
        $(this).addClass('jy-task2');
    });

    // 用户菜单展开效果
    $(".user-side .side-menu dt .square").click(function () {
        var $this = $(this);
        var dd = $this.parent("dt").siblings("dd");
        $this.toggleClass("square-plus");
        dd.slideToggle();
    });

});
//yy做
$(document).ready(function (Object) {
    $('.security_check_img1').click(function (Object) {
        $('.security_check_img1').css({ "background-image": "url(images/radio_normal.png)" });
        $(this).css({ "background-image": "url(images/radio_select.png)" });
        x = $(this).offset();
        y = $('.security_check_jt').offset();
        var tp = $(this).offset().top;

        $('.security_check_jt').animate({
            top: (tp - 850) + "px"

        })
    })




    window.onload = function (Object) {
        $('.score-state-right').removeAttr("style")

        var i = 360;
        var a = $('.score-state-right').width();

        if (a < 360) {
            i++;
            $('.score-state-right').animate({ "width": i + "px" }, "slow")
        }
        $('.cxjc').click(function (Object) {
            $('.score-state-right').removeAttr("style")

            var i = 360;
            var a = $('.score-state-right').width();

            if (a < 360) {
                i++;
                $('.score-state-right').animate({ "width": i + "px" }, "100")

            }
        })

    }
})

//邮箱绑定
$(function () {
    $('.band_email_bottom_img2').click(function () {
        $(this).hide();
        $('.send_verification_left,.send_verification_right').hide();
        $('.authentication_options').show();
        $('.band_email_bottom_img3').show();
    })
    $('.band_email_bottom_img3').click(function () {
        $(this).hide();
        $('.send_verification_left,.send_verification_right').show();
        $('.authentication_options').hide();
        $('.band_email_bottom_img2').show();
    })
    $('.band_email_img1').click(function () {
        $('.band_email_warp').hide();
    })
    $('.bonding').click(function () {
        $('.band_email_warp').show();
    })
    $('.authentication_options li').click(function () {
        var a = $(this).text();
        $('.phone_verification span').text(a);
        $('.band_email_bottom_img2').show();
        $('.authentication_options').hide();
        $('.send_verification_left,.send_verification_right').show();

    })
    var timer = "";
    var nums = 60;
    var i = 4;
    var validCode = true;//定义该变量是为了处理后面的重复点击事件
    $(".send").on('click', function () {
        console.log("111");
        var code = $(this);



        if (validCode) {
            validCode = false;
            timer = setInterval(function () {
                if (nums > 0) {
                    nums--;
                    code.text(nums + "秒后重新发送");
                    code.addClass("gray-bg");

                    code.attr('value', ("重新发送" + nums));

                } else if (nums == 0) {
                    code.attr('value', ("重新发送"));
                }
                else {
                    clearInterval(timer);
                    nums = 60;//重置回去
                    validCode = true;
                    code.removeClass("gray-bg");

                }

            }, 1000)
        }



    })
})
$(function () {
    $('.left-nav-li').click(function () {
        if ($(this).hasClass('left-nav')) {
            return;
        }

        var whatTab = $(this).index();

        var al = $(this).parent().parent().parent().parent().children().last().children().children()
        $('.help_center_zc').hide()



        $('.help_center_zc').eq(whatTab).show();



        var howFar = 40 * (whatTab + 1);
        $(".left-nav").animate({
            top: howFar + "px"

        });
        var arr = new Array();
        var divs = $(".help_center_zc");


    });

    $('.help_center_right_zc_h4').click(function () {
        $('.help_center_right_zc_h4').css({ "background-image": "url(images/feedback_bg.png)", "background-position": " 0 -18px" });
        $(this).css({ "background-image": "url(images/feedback_bg.png)", "background-position": " 0 2px" });
        $('.help_center_right_zc_li p').hide();
        $(this).next().show();

    })


});