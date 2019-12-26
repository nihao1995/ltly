		(function(){
			var file = {
				upload: function(e){
					var file = e.target.files[0];
					var type = file.type.split('/')[0];
					if (type !='image') {
						alert('请上传图片');
						return;
					}
					var size = Math.floor(file.size / 1024 /1024);
					if (size > 300) {
						alert('图片大小不得超过3M');
						return;
					};
					var reader = new FileReader();
					console.log(file);
					console.log(reader);
					// reader.readAsBinaryString(file);
					// reader.readAsArrayBuffer(file);
					// reader.abort(file);
					// reader.readAsText(file);
					reader.readAsDataURL(file);
					reader.onloadstart = function(){
						console.log('start');
					};
					reader.onprogress = function(e){
						var p = '已完成：' + Math.round(e.loaded / e.total * 100) + '%' ;
						$(".file_upload").find(".progress").html(p);
						// console.log(e);
						console.log('uploading');
					};
					reader.onabort = function(){
						console.log('abort');
					};
					reader.onerror = function(){
						console.log('error');
					};
					reader.onload = function(){
						console.log('load complete');
					};
					reader.onloadend = function (e) {
						// console.log(e);
				        var dataURL = reader.result;
						alert(dataURL);
				        var image = '<img src="'+dataURL+'"/>';
				        var text = '<span>"'+dataURL+'"</span>';
				        // console.log(dataURL);
				        //file里面存放有文件的名字(name)、格式(type)、大小(size)、上传时间(time)等等
				        var name = file.name,size = Math.round(file.size / 1024),time = new Date(file.lastModified);
				        time = time.getFullYear() + '年' + Math.floor(time.getMonth() + 1 )+ '月'+ time.getDate() + '日';
				        var div = '<p>Name: '+name+'</p><p>Size: '+size+'kb</p>';
				        var imglist = '<div class="img_box"><span class="delete">X</span>'+image + div+'</div>';
				        $(".img_holder").html(imglist);
						//异步提交数据
				        $(".upload_bt").click(function(){
				        	if ($("#upload").val() =='') {
				        		alert('请上传图片');
				        		return;
				        	};
					        var para = {
					        	//name: name,
					        	url: dataURL
					        };
					        $.ajax({
					        	url:'http://localhost/cscsup/cs.php',
					        	type:'post',
					        	data: para,
					        	success: function(data){
									alert(data)
					        		if (data) {
					        			alert('success');
					        		}else{
					        			alert('failure');
					        		}
					        	},
					        	err: function(){
					        		alert('error');
					        	}
					        })
				        });        
			    	};
				},
				m_upload: function(e){
					var m_file = e.target.files;
					//file里面存放有文件的名字(name)、格式(type)、大小(size)、上传时间(time)等等
					var name='', div='',image='';
					var beforethumb=$('.m_img_holder').children('.img_box').length;
					var totalthumb = beforethumb+m_file.length;
					if(totalthumb>5){
						var nowthumb=5;
						alert("最多只能上传5张图片");
					}else{
						for(var i=0;i<m_file.length;i++){
							var z = m_file[i];
							var type = z.type.split('/')[0];
							if (type !='image') {
								alert('请上传图片');
								return;
							}

							var reader = new FileReader();//这里可能会有坑
							reader.readAsDataURL(z); 
							// console.log(z.name);
							reader.onloadend = (function(i){
								return function(){
									console.log();
									console.log(i);
									//div = '<p>Name: '+i.name+'</p><p>Size: '+Math.round(i.size / 1024)+'kb</p>';
									div = '';
									image = '<div class="text-center dptable bg-000-5"><span class="dptavle-cell vamiddle w-100 h-100"><img src="'+ this.result +'"/></span></div>';
									var imglist = '<div class="img_box"><span class="delete"></span><div class="loading"></div>'+image + div+'</div>';
									$(".m_img_holder").append(imglist);
								};
							})(z);

						}
			    	};
				},
				event: function(){
					$("#upload").change(function(e){
						$(".progress").removeClass("none");
						file.upload(e);
					});
					$("#m_upload").change(function(e){
						file.m_upload(e);
					});
					//删除文件
					$(".file_upload").delegate(".delete","click",function(){
						var o = $(this);
						o.parents(".img_box").remove();//并没有清空input里面的值
						$(".progress").addClass("none");
						$("#upload,#m_upload").val('');//这下就删除了
					})
				},
				init: function(){
					this.event();
				}
			}
			file.init();
		}());
