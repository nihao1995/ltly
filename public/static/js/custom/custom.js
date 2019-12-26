
/*
*本文件方法依赖layer.js  jquery.js支持
*2019-6-10
*/
/*全选/全不选 name=zy_bat_all_ck   name=zy_bat_ck*/
$(document).on('click','input[name=zy_bat_all_ck]',function(){
	let selected=$(this).is(':checked');
	$(document).find('input[name=zy_bat_ck]').prop('checked',selected);
});

//批量操作按钮
$(document).on('change','select[name=zy_bat_operat]',function(){
	let data=getBatCkData();
	let _this=$(this);
	if(data.length==0){
		layer.msg('请至少选择一项！',function(){});
		$($(this).find('option')[0]).prop('selected',true);
		return false;
	}
	layer.confirm('确定要'+$(this).find('option:selected').text()+'选中的项吗？',{title:'操作提示',btn:['确定','取消']},function(){
		submitData(data,_this.val());
	},function(){
		layer.msg('操作取消',function(){});
	});
});

//获取批量操作数据
var getBatCkData=function(){
	let data=[];
	$(document).find('input[name=zy_bat_ck]').each(function(){
		if($(this).is(':checked')){
			let id=$(this).val();
			data.push(id);
		}
	});
	return data;
} 
//输入框验证 只能输入金额格式
$(document).on('keyup','.zy-number-amount',function(){

	var str=$(this).val();
	console.log(100000);
	str=str.replace(/[^\d|\.]/g,'');
	var len1 = str.substr(0, 1);
	var len2 = str.substr(1, 1);
	//如果第一位是0，第二位不是点，就用数字把点替换掉
	if (str.length > 1 && len1 == 0 && len2 != ".") {
	str = str.substr(1, 1);
	}
	//第一位不能是.
	if (len1 == ".") {
	str = "";
	}
	//限制只能输入一个小数点
	if (str.indexOf(".") != -1) {
	var str_ = str.substr(str.indexOf(".") + 1);
	if (str_.indexOf(".") != -1) {
	  str = str.substr(0, str.indexOf(".") + str_.indexOf(".") + 1);
	}
	}
	//正则替换，保留数字和小数点
	//str = str.replace(/[^\d^\.]+/g,'')
	//如果需要保留小数点后两位，则用下面公式
	str = str.replace(/\.\d\d\d$/,'')
	$(this).val(str);
})

//输入框验证 只能输入数字
$(document).on('keyup','.zy-number',function(){
	let max=$(this).attr('max');
	let min=$(this).attr('min');
	var str=$(this).val();
	var len1 = str.substr(0, 1);
	var len2 = str.substr(1, 1);
	//如果第一位是0，第二位不是点，就用数字把点替换掉
	if (str.length > 1 && len1 == 0 ) {
		str = str.substr(1, 1);
	}
	str=str.replace(/[^\d]/g,'');
	if(typeof(max)!='undefined' && str>parseInt(max)){
		str=max;
	}
	if(typeof(min)!='undefined' && str<parseInt(min)){
		str='';
	}
	$(this).val(str);
});

//提交数据时禁用按钮
var banBtn = function(_this){
	console.log($(_this).data('title'));
	if($(_this).data('title') != undefined){
		return false;
	}
	var text = $(_this).text();
	$(_this).text(text+'中...');
	$(_this).attr('disabled','disabled');
	$(_this).css('pointer-events','none');
	$(_this).data('title',text);
}
//提交数据后启用按钮
var cancelBan = function(_this){
	var text = $(_this).data('title');
	$(_this).text(text);
	$(_this).removeAttr('disabled');
	$(_this).css('pointer-events','auto');
}


//按钮提交
$(document).on('click','.zy-ajax-confirm',function( e ){
	e.stopPropagation();
	if( layer == undefined ){
		console.log('layer未加载...');
		return false;
	}
	let _this = $(this);
	let dataMsg = (_this.data('msg') == undefined || _this.data('msg') == '' ) ? '操作确认（确定/取消）。' : _this.data('msg');
	let url = _this.attr('href');
	var index = layer.confirm( dataMsg ,
		{ btn: ['确定','取消'] , title:'操作提示' , icon:3 } ,
		function (){
			$.get(url,function( data ){
				data = JSON.parse(data);
				console.log(data);
				let icon = ( data.code == 200 ) ? 1:5;
				let message = ( data.message == undefined ) ? '请求异常！' : data.message;
				layer.msg( message ,{ icon : icon } );
				if( icon == 1 ){
					setTimeout(function(){
						window.location.reload();
					},1200);
				}
			});
		} ,
		function(){
			layer.msg('操作取消');
		}
	);
	return false;
});