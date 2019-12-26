

$(function () {

	layui.use('form', function(){
		var form = layui.form;
        var pnum = 0;
        var cnum = 0;
        var anum = 0;
        var v=$('.now-area').val();

        $.each(addressArr, function(idx, obj) {
            if($('.now-province').val() == obj.value){
                $('.now-province').parents('.layui-form-item').find(".j-province").append("<option dv='"+pnum+"' value='"+obj.value+"' data-id="+obj.id+" selected='' >"+obj.value+"</option>");
                var now_p = pnum;
                $.each(addressArr[now_p].childs, function(idxc, objc) {
                    if($('.now-city').val() == objc.value){
                        $('.now-city').parents('.layui-form-item').find(".j-city").append("<option dv='"+cnum+"' value='"+objc.value+"' data-id="+objc.id+" selected='' >"+objc.value+"</option>");
                        var now_c = cnum;
                        if(addressArr[now_p].childs[now_c].childs) {
                            $.each(addressArr[now_p].childs[now_c].childs, function (idxa, obja) {


                                if (v.trim()==obja.value.trim()) {
                                    $(".j-area").append("<option dv='" + anum + "' value='" + obja.value + "' data-id=" + obja.id + " selected='' >" + obja.value + "</option>");
                                } else {
                                    $(".j-area").append("<option dv='" + anum + "' value='" + obja.value + "' data-id=" + obja.id + " >" + obja.value + "</option>");
                                }
                                anum++;
                            });
                        }}else{
                        $(".j-city").append("<option dv='"+cnum+"' value='"+objc.value+"' data-id="+objc.id+" >"+objc.value+"</option>");
                    }

                    cnum ++;
                });

            }else{

                $(".j-province").append("<option dv='"+pnum+"' value='"+obj.value+"' data-id="+obj.id+" >"+obj.value+"</option>");
            }

            pnum ++;
        });

        form.render('select'); //刷新select选择框渲染

		//$('.j-province').empty();
		//$('.j-city').empty();
		//$(".j-province").append("<option value='' data-id='' >请选择省</option>");
		//$(".j-city").append("<option value='' data-id='' >请选择市</option>");



		form.on('select(j-province)', function(data){   //选择省
		    cnum = 0;//市序列
		  //console.log(data.elem); //得到select原始DOM对象
		  //console.log(data.value); //得到被选中的值
		  //console.log(addressArr[data.value]); //得到被选中的值
		  //console.log($(this).parents('.layui-input-inline').nextAll('.layui-input-inline').find('.j-area').html()); //得到美化后的DOM对象
		  var $city = $("#ad2");
		  var $area = $("#ad3");;
		  $city.empty();
		  $city.append("<option value='' data-id='' >请选择市</option>");
		  var a=document.getElementById('ad1');
			data.value=a.getElementsByTagName('option')[a.selectedIndex].getAttribute('dv');
		  if(data.value){   //有选择省

		    $.each(addressArr[data.value].childs, function(idx, obj) {
		    //console.log(obj.id);
		    $city.append("<option dv='"+cnum+"' value='"+obj.value+"' data-id="+obj.id+" >"+obj.value+"</option>");
		    cnum ++;
		    });
		    $area.empty();
		    $area.append("<option value='' data-id='' >请选择县/区</option>");
		  }
		  form.render('select'); //刷新select选择框渲染

		});
		form.on('select(j-city)', function(data){   //选择市
		  //console.log(data.elem); //得到select原始DOM对象
		  //console.log(data.value); //得到被选中的值
		  var $province_val = $(this).parents('.layui-input-inline').prev('.layui-input-inline').find('.layui-unselect :input').val();
		  pnum = 0;  //省序列
		  var porder;
		  $.each(addressArr, function(idx, obj) {
			  if(obj.value == $province_val ){
				  porder = pnum;
				  return false;
			  }
			  pnum++;
		  });
		  //console.log('省'+porder); 省位置
		  //console.log($(this).parents('.layui-input-inline').next('.layui-input-inline').find('.j-city').html()); //得到美化后的DOM对象
		  var $city = $(this).parents('.layui-input-inline').next('.layui-input-inline').find('.j-city');
		  var $area = $(this).parents('.layui-input-inline').nextAll('.layui-input-inline').find('.j-area');

		  $area.empty();
			//console.log(addressArr[porder].childs[data.value]);

		  var jslength=0;
			for(var address in addressArr[porder].childs[data.dv]){
			jslength++;
	      }
		  //console.log(jslength);    判断是否有下级
		  if(jslength == 2){
			$area.append("<option value='' data-id='' ></option>");   //无下级
	      }else{
			$area.append("<option value='' data-id='' >请选择县/区</option>");
		  }
            a=document.getElementById('ad2');
            data.value=a.getElementsByTagName('option')[a.selectedIndex].getAttribute('dv');
		  if(data.value ){   //有选择市
			//console.log(addressArr[porder].childs[data.value].childs);
			cnum = 0;
		    $.each(addressArr[porder].childs[data.value].childs, function(idx, obj) {
		    //console.log(obj.id);
		    $area.append("<option dv='"+cnum+"' value='"+obj.value+"' data-id="+obj.id+" >"+obj.value+"</option>");
		    cnum ++;
		    });
		  }
		  form.render('select'); //刷新select选择框渲染

		});

	});
});

