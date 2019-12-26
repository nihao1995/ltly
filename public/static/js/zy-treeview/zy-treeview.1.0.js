/**
 * 插件名称：前端简单树形插件
 * 依赖库: jquery 1.9及以上版本
 * author:唯有代码阔以解忧
 * date:2019-1-16
 * opt参数解释：
 * {
 * data:array  数据源,
 * checkbox: bool  是否带复选框
 * fontColor:string 文字颜色 
 * deep:int 子级菜单展开深度 默认为0展开所有子级
 * indent: int 子级菜单缩进距离
 * closeIcon:string 关闭状态下的图标
 * openIcon:string 开启状态下的图标
 * }
 */
;(function($){
	//构造
	var Treeview=function(ele,opt){
		this.ele=ele;
		this.default={
			data:[],//数据源
			checkbox:false,//复选
			radio:false,//单选
			fontColor:'',
			deep:0,
			indent:30,
			closeIcon:'▶',
			openIcon:'▼',
			ajaxGetChildList:null,//获取子级菜单方法
			debug:false//调试开关
		};
		this.options=$.extend({},this.default,opt);
	}
	//定义方法
	Treeview.prototype={
		config:{
			//无子级图标
			noChildIcon:'&nbsp;&nbsp;'
		},
		//初始化方法
		initialize:function(){
			//清空节点里的内容
			$(this.ele).empty();
			//创建树形
			var res=this.createTree(this.options.data);
			res='<ul style="list-style-type:none;min-width:200px;margin:0px;padding:0px;border:1px solid #ccc;">'+res+'</ul>';
			$(this.ele).append(res);
			//设置样式
			this.setStyle();
			//触发事件
			this.triggerEvent();
		},
		//调试输出信息
		printDebugInfo:function(info){
			if(this.options.debug){
				console.log(info);
			}
		},
		//创建树形
		createTree:function(data,str=""){
			var _this=this;
			//遍历
			$.each(data,function(index,item){
				//缩进距离
				var indent=_this.options.indent*item.level;
				//var icon=(typeof(item.nodes)!="undefined")?'</span><span class="switch_icon" data-switch="off" style=";width:30px;line-height:36px;text-align:center;height:36px;">':
				//'</span><span class="glyphicon  glyphicon-remove  switch_icon" data-switch="off" style="overflow:hidden;width:30px;line-height:0px;text-align:center;height:0px;">';
				//复选框
				var cbx="";
				//参数开启复选框，并且数据中定义了checked字段
				if(true==_this.options.checkbox ){//&& typeof(item.checked)!='undefined'){
					var ced=item.checked?'checked="true"':""
					cbx='<span style="margin:5px;text-align:center;"><input class="tree_cbx" style="cursor:pointer;" type="checkbox" '+ced+' /></span>';
				}
				//单选按钮
				if(false==_this.options.checkbox && true==_this.options.radio){	
					cbx='<span style="margin:5px;text-align:center;"><input class="tree_radio" name="tree_radio" style="cursor:pointer;" type="radio"  /></span>';
				}
				//默认展开深度
				var li_style='display:none;';
				var icon=_this.options.closeIcon;
				var turn_off="turn_off";
				//根据定义深度展开
				if(_this.options.deep==0  ||  _this.options.deep>item.level){
					icon=_this.options.openIcon;//开关打开
					turn_off="";
					li_style="";
					//如果又子级，并且为空 只显示图标为关闭状态
					if(typeof(item.nodes)!="undefined" && item.nodes.length==0){
						icon=_this.options.closeIcon;
						turn_off="turn_off";
					}
					//如果有子级，但是不为空 打开
					if(typeof(item.nodes)!="undefined" && item.nodes.length>0){
						icon=_this.options.openIcon;
						turn_off="";
					}
				}

				if(_this.options.deep!=0 && (_this.options.deep==item.level+1)){
						icon=_this.options.closeIcon;
						turn_off="turn_off";
				}
				li_style+="padding-left:"+indent+'px;';
				// 没有子级没有图标
				icon=(typeof(item.nodes)!="undefined")?icon:_this.config.noChildIcon;
				var no_child=(typeof(item.nodes)!="undefined")?'yes':'no';
				icon='<span class="switch_icon '+turn_off+'" data-child="'+no_child+'" style="display:inline-block;width:30px;line-height:36px;text-align:center;height:36px;">'+icon+'</span>';
				//id
				var id='id="nodeid_'+item.id+'"';
				//parent_id
				var pid='class="pid_'+item.parent_id+'"';
				//生成选项
				str+='<li style="'+li_style+'"  '+id+'  '+pid+' data-tag="'+item.id+'" data-level="'+item.level+'">'+icon+cbx+'<span class="li_text" style="color:'+_this.options.fontColor+';font-weight:500;">'
				+item.text+'</span></li>';
				//递归 
				if(typeof(item.nodes)!='undefined' ){
					str=_this.createTree(item.nodes,str);
				}
			});
			return str;
		},
		//设置样式
		setStyle:function(){
			$(this.ele).find('li').each(function(){
				//设置一级栏目
				$(this).css({'width':'100%','height':'36px','line-height':'36px','border-bottom':'1px solid #ccc','cursor':'pointer'});
				$(this).find('.apanel').css({'width':'10px','height':'30px','color':'#18BC9C','margin':'10px','display':'none','fontSize':'16px'});
			});
		},
		//触发事件
		triggerEvent:function(){
			var _this=this;
			//选项li事件
			$(this.ele).on('mouseover','li',function(){
				$(this).css({'background-color':'#F6F4F4'});
				$(this).find('.apanel').css('display','inline-block');
			});
			$(this.ele).on('mouseout','li',function(){
				$(this).css({'background-color':''});
				$(this).find('.apanel').css('display','none');
			});
			//子级开关.switch_icon 事件//单机事件
			$(this.ele).off('click','.switch_icon').on('click','.switch_icon',function(even){
/*				even.stopPropagation;//阻止冒泡
				event.cancelBubble  = true;*/
						//没有子选项
						if($(this).data('child')=="no"){return false;}
						//切换开关图标的样式
						var open=_this.switchStyle($(this));//开关状态
						//展开子级
						if(open){
							//判断子级是否加载
							var xid=$(this).parent().data('tag');
							if($(_this.ele).find('.pid_'+xid).length==0){
								//加载子级列表 根据父级id
								if(_this.ajax_get_child_list(xid)==false) return false;
								return false;
							}
							//根据用户定义深度展开子菜单
							var child=_this.recursiveChild($(this).parent().data('tag'),_this.options.deep);
							$.each(child,function(index,value){
								var node=$(_this.ele).find('#nodeid_'+value);
								node.css('display','');
								if(node.find('.switch_icon').data('child')!="no"){
									//如果用户定义的深度大于栏目级数就展开
									if(_this.options.deep==0 || _this.options.deep>node.data('level')){
										//如果没有子级就不执行展开操作
										if($(_this.ele).find('.pid_'+value).length!=0){
											_this.switchStyle(node.find('.switch_icon'));
										}
										
									}
								}
							});
						}else{
							//关闭子级菜单时，此级下所有子菜单
							var child=_this.recursiveChild($(this).parent().data('tag'));
							$.each(child,function(index,value){
								var node=$(_this.ele).find('#nodeid_'+value);
								node.css('display','none');
								if(node.find('.switch_icon').data('child')!="no" && !(node.find('.switch_icon').hasClass('turn_off'))){
									_this.switchStyle(node.find('.switch_icon'));
								}
							});
						}
					return false;
			});
			//选项复选框事件
				//复选框改变状态事件
				$(this.ele).on('change','.tree_cbx',function(){
					var id=$(this).parent().parent().data('tag');
					var status=$(this).is(":checked");
					//选中的父级和子级需要一起被选中
					//取消的子级一起被取消选中
					//获取父级
					var parent=status?_this.recursiveParent(id):[];
					//获取子级
					var child=_this.recursiveChild(id);
					//合并数组
					var list=parent.concat(child);
					if(list.length>0){
						$.each(list,function(index,value){
							$(_this.ele).find('#nodeid_'+value).children().find('.tree_cbx').prop('checked',status);
						});
					}
				});

			/**************triggerEvent方法结束***************/
		},
		//获取子级   返回子级id 不包含传入的id
		//nodeId  节点的标记id 不是元素id
		//ids array  返回的标记id列表
		//deep	int  设置查询深度  
		//level  当前遍历深度  无需设置
		recursiveChild:function(nodeId,deep=0,level=0,ids=[]){
			var _this=this;
			//根据id获取元素
			var ele=$(_this.ele).find('#nodeid_'+nodeId);
			//如果存在
			if(ele.length>0){
				//判断遍历深度 
				if(deep==0 || deep>level){
					level=level+1;
					//寻找子元素
					$(_this.ele).find('.pid_'+nodeId).each(function(){
						//传递id深度查询
						var id=$(this).data('tag');
						ids.push(id);
						ids=_this.recursiveChild(id,deep,level,ids);
					});	
				}
			}	
			return ids;			
		},
		//获取父级
		recursiveParent:function(nodeId,ids=[]){
			var _this=this;
			var  node=$(_this.ele).find('#nodeid_'+nodeId);
			if(node.length>0){
				var parent_id=_this.getFlag(node.attr('class'));
				//检查如果父级元素存在 
				if($(_this.ele).find('#nodeid_'+parent_id).length>0){
					//继续向上找
					ids.push(parent_id);
					ids=_this.recursiveParent(parent_id,ids);
				}
			}
			return ids;
		},
		//获取标记
		getFlag:function(str){
			var a=str.split('_'); 
			return  a[1];
		},
		//切换开关子级按钮样式
		switchStyle:function(ele){
			ele.toggleClass('turn_off'); 
			var open=ele.hasClass('turn_off')?false:true;
			ele.text(open?this.options.openIcon:this.options.closeIcon);
			return open;
		},
		//加载子级列表
		ajax_get_child_list:function(id){
			//获取子级数据
			var list=this.options.ajaxGetChildList;
			if(list==null || typeof(list)!="function"){
				alert('获取子级数据方法ajaxGetChildList未被定义！');
				this.printDebugInfo('获取子级数据方法ajaxGetChildList未被定义！function ajaxGetChildList:null');
				return false;
			}else{
				//将数据处理后放入页面
				list=list(id);
				if(list.length==0){
					alert('未找到与此栏目相关的子栏目信息！');
					//修改该项的图标
					$(this.ele).find('#nodeid_'+id).find('.switch_icon').html(this.config.noChildIcon);
					return false;
				}
				var parent_leve=$('#nodeid_'+id).data('level');
				//生成树形
				var res=this.createTree(list);
				//将子级栏目加入到列表中
				$(this.ele).find('#nodeid_'+id).after(res);
				this.setStyle();
			}
		},
		/***prototype结束**/
	}
	//暴露
	$.fn.treeView=function(options){
		var tv=new Treeview(this,options);
		tv.initialize();
	}
})(jQuery);