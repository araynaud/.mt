function multiUploader(config)
{  
	this.config = config;
	this.items = "";
	this.all = []
	var self = this;
	
	multiUploader.prototype._init = function()
	{
		if (!(window.File && window.FileReader && window.FileList && window.Blob))
		{
			console.log("Browser supports failed");
			return;
		}
		var inputId = $("#"+this.config.form).find("input[type='file']").eq(0).attr("id");
		document.getElementById(inputId).addEventListener("change", this._read, false);
		document.getElementById(this.config.dragArea).addEventListener("dragover", function(e){ e.stopPropagation(); e.preventDefault(); }, false);
		document.getElementById(this.config.dragArea).addEventListener("drop", this._dropFiles, false);
		document.getElementById(this.config.form).addEventListener("submit", this._submit, false);
	};
	
	multiUploader.prototype._submit = function(e)
	{
		e.stopPropagation(); e.preventDefault();
		self._startUpload();
	};
	
	multiUploader.prototype._preview = function(data)
	{
		this.items = data;
		if(this.items.length > 0)
		{
			var html = "";		
			var uId = "";
 			for(var i = 0; i<this.items.length; i++)
			{
				uId = this.items[i].name._unique();
				var sampleIcon = '<img src="../icons/image.png" />';
				var errorClass = "";
				if(typeof this.items[i] != undefined)
				{
					if(!self._validate(this.items[i].type)) 
					{
						sampleIcon = '<img src="../icons/unknown.png" />';
						errorClass =" invalid";
					} 
					html += '<div class="dfiles'+errorClass+'" rel="'+uId+'"><h5>'+sampleIcon+this.items[i].name+'</h5><div id="'+uId+'" class="progress" style="display:none;"><img src="../icons/ajax-loader.gif" /></div></div>';
				}
			}
			$("#dragAndDropFiles").append(html);
		}
	}

	multiUploader.prototype._read = function(evt)
	{
		if(evt.target.files)
		{
			self._preview(evt.target.files);
			self.all.push(evt.target.files);
		} else 
			console.log("Failed file reading");
	};
	
	multiUploader.prototype._validate = function(format)
	{
		var arr = this.config.support.split(",");
		return arr.indexOf(format) != -1;
	};
	
	multiUploader.prototype._dropFiles = function(e)
	{
		e.stopPropagation(); e.preventDefault();
		self._preview(e.dataTransfer.files);
		self.all.push(e.dataTransfer.files);
	};
	
	multiUploader.prototype._uploader = function(file,f)
	{
		if(typeof file[f] == undefined || !self._validate(file[f].type))
		{
			$(".status").html("Invalid file format - "+file[f].name);
			return;
		}
		
		var data = new FormData();
		var ids = file[f].name._unique();
		var path = $("input#path").val();
		data.append('file',file[f]);
		data.append('path',path);
		data.append('index',ids);
		var fileDiv=$(".dfiles[rel='"+ids+"']")
		fileDiv.find(".progress").show();
		$.ajax(
		{
			type:"POST",
			url:this.config.uploadUrl,
			data:data,
			cache: false,
			contentType: false,
//			contentType: "application/json",
			processData: false,
			success:function(response)
			{
				var jsr = JSON.parse(response);
				$(".status").html(response);
				$("#"+ids).hide();
				if(jsr.moved && fileDiv.attr("rel") == jsr.index)
				{
					//fileDiv.append(" " + jsr.index + " uploaded.");
					fileDiv.find(".progress").html("uploaded");
					//fle.slideUp("normal", function(){ $(this).remove(); });
				}

				if (f+1 < file.length)
					self._uploader(file,f+1);
			}
		});
	};
	
	multiUploader.prototype._startUpload = function()
	{
		if(this.all.length > 0)
			for(var k=0; k<this.all.length; k++)
			{
				var file = this.all[k];
				this._uploader(file,0);
			}
	};
	
	String.prototype._unique = function()
	{
		return this.replace(/[a-zA-Z]/g, function(c){
     	   return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
    	});
	};

	this._init();
}

function initMultiUploader(config)
{
	new multiUploader(config);
}