/* Client-side access to MediaFile objects
*/

//constructor
function MediaFile(data)
{ 
	//if data is object: loop for each key, use Object.merge = function (this, data);
	if(isObject(data))
		Object.merge(this, data, true);
	//if data is a string: absolute URL, relative URL or filename
	else if(isString(data))
		this.url=data;
	this.getId();
	this.filename = this.getFilename();

	this.initTags();

	this.selected=false;
}

MediaFile.prototype.get = function(key, default_)
{
	var value=this[key];
	return isMissing(value) ? default_ :  value ;
};

MediaFile.prototype.set = function(key, value)
{
	if(isArray(this[key]))
		this[key].push(value);
	else if(isObject(this[key]))
		this[key][value]=value;
	else
		this[key] = value;
};

MediaFile.prototype.contains = function(key)
{
	return !isMissing(this[key]);
};

//Todo functions
// makeTitle
// image in .ss, image in parent
// actual stored image from image.php

//add to prototype all functions taking 1 mediaFile as argument
// from filenames.js and templates.js

MediaFile.prototype.getId = function()
{
	if(!this.id)
		this.id = MediaFile.getId(this.name); //, this.type);
	return this.id;
};

MediaFile.getId = function(name, type)
{
	var id = name.replace(/[\.#\(\)\{\}' &]/g,"_");
	if(type)	id = type +"_" + id;
	return id;
};

MediaFile.prototype.getFilename = function(ext)
{
	ext = valueOrDefault(ext, 0);
	if(this.exts && this.exts[ext]) 
		ext=this.exts[ext]; 
	return ext ? this.name + "." + ext : this.name;
};

MediaFile.prototype.initTags = function()
{
	// array to object.
	if(isArray(this.tags))
		this.tags=this.tags.toMap();
};

MediaFile.isDir = function(mediaFile)
{
	return mediaFile.type==="DIR" ? 1 : 0;
};

MediaFile.prototype.isDir = function()
{
	return MediaFile.isDir(this);
};

MediaFile.isImage = function(mediaFile)
{
	return MediaFile.hasType(mediaFile, "IMAGE");
};

MediaFile.prototype.isImage = function()
{
	return this.type === "IMAGE"; // MediaFile.hasType(this, "IMAGE");
};

MediaFile.isAudio = function(mediaFile)
{
	return MediaFile.hasType(mediaFile, "AUDIO");
};

MediaFile.prototype.isAudio = function()
{
	return MediaFile.hasType(this, "AUDIO");
};

MediaFile.isVideo = function(mediaFile)
{
	return MediaFile.hasType(mediaFile, "VIDEO");
};

MediaFile.prototype.isVideo = function()
{
	return MediaFile.hasType(this, "VIDEO");
};

MediaFile.isVideoStream = function(mediaFile)
{
	return MediaFile.hasType(mediaFile,"VIDEO", "STREAM");
};

MediaFile.prototype.isVideoStream = function(mediaFile)
{
	return MediaFile.hasType(this, "VIDEO", "STREAM");
};

MediaFile.prototype.hasType = function(type, subType)
{
	return MediaFile.hasType(this, type, subType);
};

MediaFile.hasType = function(mediaFile, type, subType)
{
	var fileTypeExts = config.TYPES[type];
	if(subType)
		fileTypeExts = fileTypeExts[subType];
	if(!fileTypeExts) fileTypeExts= [ type ];

	if(!mediaFile.exts || !fileTypeExts) return false;
	
	for (var i=0;i<mediaFile.exts.length;i++)
		if(fileTypeExts.contains(mediaFile.exts[i].toLowerCase()))
			return mediaFile.exts[i];
	return false;
};

MediaFile.prototype.hasTag = function(tag)
{
	return MediaFile.hasTag(this, tag);
};

MediaFile.hasTag = function(mediaFile, tag)
{
	if(!mediaFile.tags) return false;
	if(isArray(mediaFile.tags))
		return mediaFile.tags.contains(tag);
	return mediaFile.tags.hasOwnProperty(tag);
};

MediaFile.prototype.setTag = function(tag, state)
{
	state=valueOrDefault(state,false);
	var hasTag = this.hasTag(tag);
	if(state == hasTag) return;
	if(isMissing(this.tags))	this.tags={};
	if(state)
		this.tags[tag] = tag;
	else
		delete this.tags[tag];
};

MediaFile.prototype.getTags = function()
{
	return Object.values(this.tags);
};

MediaFile.getTakenDate = function(mediaFile, includeTime)
{
	if(includeTime) return mediaFile.takenDate;
	return mediaFile.takenDate.substringBefore(" ");
};

MediaFile.prototype.getTakenDate = function(includeTime)
{
	return MediaFile.getTakenDate(this, includeTime);
};

MediaFile.getFilePath = function (mediaFile)
{
	return String.combine(album.path, mediaFile.subdir, mediaFile.filename);
};

MediaFile.prototype.getFilePath = function(ext)
{
	return MediaFile.getFilePath(this, ext);
};

MediaFile.getFileUrl = function (mediaFile, ext)
{
	var filename=mediaFile.filename;
	if(isMissing(ext))
		ext="";
	if(isArray(ext))
		ext=ext[0];
	if(!isString(ext) && mediaFile.exts)
		ext=mediaFile.exts[ext];
	if(ext)
		filename=mediaFile.name+"."+ext;
	return String.combine(album.relPath, mediaFile.subdir, filename);
};

MediaFile.prototype.getFileUrl = function (ext)
{
	return MediaFile.getFileUrl(this, ext);
};

MediaFile.getFileDir = function(mediaFile, subdir)
{
	return String.combine(album.relPath, mediaFile.subdir, subdir);
};

MediaFile.prototype.getFileDir = function(subdir)
{
	return MediaFile.getFilePath(this, subdir);
};

MediaFile.getScriptUrl = function(mediaFile, scriptName, params)
{
	scriptName = String.combine(Album.serviceUrl,scriptName);
	var url = "{0}?path={1}&file={2}".format(scriptName, String.combine(album.path, mediaFile.subdir), String.urlEncode(mediaFile.filename));
	if(!params) return url;
	return url + "&" + Object.toQueryString(params);
};

MediaFile.prototype.getScriptUrl = function (scriptName, params)
{
	return MediaFile.getScriptUrl(this, scriptName, params);
};

MediaFile.makePostData = function(mediaFile, params)
{
	if(!params) params={};
	params.path = String.combine(album.path, mediaFile.subdir);
	params.name = mediaFile.name;
//	params.file = mediaFile.filename;
	return params;
};

MediaFile.prototype.makePostData = function (params)
{
	return MediaFile.makePostData(this, params);
};

MediaFile.getThumbnailDir = function(mediaFile, tnIndex)
{
	if(isMissing(tnIndex) || isEmpty(mediaFile.tnsizes) || isMissing(mediaFile.tnsizes[tnIndex]))
		return String.combine(album.relPath, mediaFile.subdir);
	
	return String.combine(album.relPath, mediaFile.subdir, "." + config.thumbnails.dirs[tnIndex]);	
};

MediaFile.prototype.getThumbnailDir = function(tnIndex)
{
	return MediaFile.getThumbnailDir(this, tnIndex);
};

MediaFile.getThumbnailUrl = function(mediaFile, tnIndex, create)
{
	tnIndex=valueOrDefault(tnIndex,0);
//if image smaller than slideshow or animated, use original
	if(mediaFile.type=="IMAGE" && !mediaFile.hasThumbnail(tnIndex))
		return mediaFile.getFileUrl();

	if(!mediaFile.hasThumbnail(tnIndex))
		tnIndex=0;
//if already exists => existing image url
	if(mediaFile.thumbnailExists(tnIndex))
	{
		var filename = mediaFile.filename;
		var ext = config.thumbnails[mediaFile.type].ext;
		if(ext)
			filename = mediaFile.name + "." + ext;
		return String.combine(album.relPath, mediaFile.subdir, "." + config.thumbnails.dirs[tnIndex], filename);
	}

//if not already exists => create script url
	create=valueOrDefault(create,false);
	if(!create) return "";
	var cfg = config.thumbnails[mediaFile.type];
	if(!cfg)	return "";
	return MediaFile.getScriptUrl(mediaFile, cfg.script, {target: tnIndex});
};

MediaFile.prototype.getThumbnailUrl = function(tnIndex,create)
{
	return MediaFile.getThumbnailUrl(this, tnIndex, create);
};

MediaFile.prototype.thumbnailExists = function(tnIndex)
{
	return this.hasThumbnail(tnIndex) && this.tnsizes[tnIndex] > 0;
};

MediaFile.prototype.hasThumbnail = function(tnIndex)
{
	if(isMissing(tnIndex))
		return !isEmpty(this.tnsizes);

	return !isEmpty(this.tnsizes) && tnIndex >= 0 && tnIndex < this.tnsizes.length;
};

MediaFile.prototype.needsThumbnail = function(tnIndex)
{
	return this.hasThumbnail(tnIndex) && this.tnsizes[tnIndex] <= 0
};

MediaFile.thumbnailExists = function(mediaFile, tnIndex)
{
	return mediaFile.thumbnailExists(tnIndex);
};

MediaFile.hasThumbnail = function(mediaFile, tnIndex)
{
	return mediaFile.hasThumbnail(tnIndex);
};

MediaFile.needsThumbnail = function(mediaFile, tnIndex)
{
	return mediaFile.needsThumbnail(tnIndex);
};

MediaFile.prototype.isTransparent = function() 
{
	return this.alpha || this.transparent && this.transparentPixels; // && !this.hasType("DIR");
}

MediaFile.getFileSize = function (mediaFile, tn)
{	
	return mediaFile.getFileSize(tn);
};

MediaFile.prototype.getFileSize = function (tn)
{	
	if(!this.vsizes) return 0;
	tn=valueOrDefault(tn, 0);
	return this.vsizes[tn] || this.vsizes[this.exts[tn]];
};

MediaFile.getTnFileSize = function (mediaFile, tn)
{	
	return mediaFile.getTnFileSize(tn);
};

MediaFile.prototype.getTnFileSize = function (tn)
{	
	tn=valueOrDefault(tn, this.tnIndex);
	if(this.hasThumbnail(tn))
		return this.tnsizes[tn];
	return this.getFileSize();
};

//run image script via ajax request
MediaFile.imageScriptAjax = function (mediaFile, params)
{	
	return MediaFile.scriptAjax(mediaFile, "image.php", params);
};

MediaFile.prototype.imageScriptAjax = function (params)
{	
	return MediaFile.imageScriptAjax(this, params);
};

//get Thubmnail URL for images and videos
//reuse in function getPicUrlAjax(pic)
MediaFile.getThumbnailUrlAjax = function (mediaFile,tnIndex)
{	
	tnIndex=valueOrDefault(tnIndex, 0);
	var imageScriptUrl = mediaFile.getThumbnailUrl(tnIndex, true);
	if(!imageScriptUrl) 
		return false;

	if(!mediaFile.needsThumbnail(tnIndex))
		return imageScriptUrl;

	var result = false;
   	$.ajax({
		url: imageScriptUrl.appendQueryString({format: "ajax"}),
		dataType: "json",
	    contentType: "application/json",
		cache: false,
		async: false,
		success: function(response)
		{ 
			//update mediaFile in response
			result = response.output;
			mediaFile.tnsizes[tnIndex] = response.filesize || 1;
		},
		error:   function(xhr, textStatus, errorThrown)
		{ 
			if(window.UI && UI.setStatus)
                UI.setStatus(textStatus + "\n" + errorThrown);
		}
	});
	return result;
};

MediaFile.prototype.getThumbnailUrlAjax = function (tnIndex)
{	
	return MediaFile.getThumbnailUrlAjax(this, tnIndex);
};

//run image script via ajax request
MediaFile.scriptAjax = function (mediaFile, script, params, async, post, callbacks)
{	
	if(!script || !mediaFile) return false;

	async = valueOrDefault(async, false);
	params = valueOrDefault(params, {});
	params.format="ajax";
	var scriptUrl = mediaFile.getScriptUrl(script); //, params);
	var method= post ? "POST" : "GET";
	var link = $.makeElement("a", { href: scriptUrl.appendQueryString({debug:"true"}), target: "debug"}).html(scriptUrl);

	var result = false;
   	$.ajax({
		url: scriptUrl,
	    type: method,
	    data: params,
		dataType: "json",
	    //contentType: "application/json",
		cache: false,
		async: async,
		success: function(response)
		{ 
			result=response;
			if(callbacks && callbacks.success)
				callbacks.success(response, mediaFile, params);
			if(callbacks && callbacks.next)
				callbacks.next(response, script, params, callbacks);
		},
		error:   function(xhr, textStatus, errorThrown)
		{ 
			result = false;
			UI.setStatus(textStatus + " " + errorThrown);
			UI.addStatus(link.outerHtml());
			UI.addStatus(xhr.responseText);
 			if(callbacks && callbacks.error)
				callbacks.error(xhr, mediaFile);
		}
	});
	return result;
};

MediaFile.prototype.scriptAjax = function (script, params, async, post, callbacks)
{	
	return MediaFile.scriptAjax(this, script, params, async, post, callbacks);
};

MediaFile.imageSuccess = function(response, mediaFile, params)
{  
	if(response.tnIndex && response.filesize)
		mediaFile.tnsizes[response.tnIndex] = response.filesize;
}

MediaFile.imageError = function(xhr, mediaFile)
{  
   UI.addStatus(xhr.responseText);
}

//mark thumbnail as existing
MediaFile.prototype.setTnExists = function(exists, tnIndex)
{
	if(!this.tnsizes) return false;
	var first = valueOrDefault(tnIndex, 0);
	var last  = valueOrDefault(tnIndex, this.tnsizes.length-1);
	first = Math.min(first, this.tnsizes.length-1);
	last = Math.min(last, this.tnsizes.length-1);
	for (var i = first; i <= last; i++)
	{
		if(exists && this.tnsizes[i] <= 0)
			this.tnsizes[i] = 1;
		else if(!exists)
			this.tnsizes[i] = -1;
	}
	return true;
};

MediaFile.alertFileInfo = function (mediaFile)
{
	alert(Object.toText(mediaFile,"\n"));
};

MediaFile.fileInfo = function (mediaFile, sep)
{
	return Object.toText(mediaFile,sep);
};

MediaFile.prototype.fileInfo = function (sep)
{
	return Object.toText(this,sep);
};

//array filtering functions
MediaFile.isSelected = function(element, index, array)
{  
	if(isFunction(Album.filterValue))
		return Album.filterValue(element);

//loop search fields until false		
	if(isObject(Album.filterValue))
	{
		var result=false;
		for(var key in Album.filterValue)
		{
			result=MediaFile.testProperty(element, Album.filterValue[key], key);
			if(!result) break;		
		}
		return result;
	}

	return MediaFile.testProperty(element, Album.filterValue, Album.filterField);
};

MediaFile.testProperty = function(element, value, field)
{  
	//find value in an element of the array
	if(isMissing(element[field])) return isMissing(value);

	if($.isArray(value))
	{
		var result=false;
		for(var n=0;n<value.length;n++)
		{
			result=MediaFile.testProperty(element, value[n], field);			
			if(result) break;		
		}
		return result;
	}

	if(isString(element[field]))	return element[field].containsText(value);
	if(isArray(element[field]))		return element[field].contains(value);
	if(isObject(element[field]))	return element[field].hasOwnProperty(value);
	return (element[field]==value);  
}

MediaFile.isExcluded = function(element, index, array)
{  
	return !MediaFile.isSelected(element, index, array);
};

//date range for a dir
MediaFile.prototype.dateRange = function (sep)
{
	return Object.toText(this,sep);
};

MediaFile.prototype.refreshThumbnail = function()
{	
	this.setTnExists(false);
	var tnUrl = this.getThumbnailUrl(album.tnIndex, true);
    var time = +(new Date());
	tnUrl =	tnUrl.appendQueryString({cache: time});
	return tnUrl;
};

//1 find largest image < max size
MediaFile.selectThumbnailSize = function(imageSize)
{
	if(!config || !config.thumbnails || !config.thumbnails.sizes) return -1;

	//find right size: take last tn smaller than img or first tn larger than img
	var tnIndex=0;

	var sizes=config.thumbnails.sizes;
	for (tn in sizes)
	{
		if(sizes[tn] >= imageSize) break;
		tnIndex++;
	}
	return tnIndex;
};

MediaFile.prototype.selectThumbnailSize = function(imageSize)
{
	if(isEmpty(this.tnsizes)) return -1;
	var tnIndex= MediaFile.selectThumbnailSize(imageSize);
	if(tnIndex>= this.tnsizes.length) return -1;
	return tnIndex;
//	return Math.min(tnIndex, this.tnsizes.length-1);
};

MediaFile.prototype.resizeBeforeUpload = function()
{
	//pre process each file
	//if site has a size limit
	var maxSize = (config.publish && config.publish.image) ? config.publish.image.size : 0;
	if(!maxSize) return this.getFileUrl();
	//select Thumbnail Size , create resized if needed
	this.tnIndex = this.selectThumbnailSize(maxSize);
	this.tnDir = config.thumbnails.dirs[this.tnIndex];
	this.tnUrl = this.getThumbnailUrlAjax(this.tnIndex);
	return this.tnDir;
};

MediaFile.prototype.getDiv = function()
{
	return $("div#" + this.id);
}

MediaFile.getDiv = function(mediaFile)
{
	return $("div#" + mediaFile.id);
}

MediaFile.play = function(el)
{
	mediaFile = album.getByAttribute(el);
	return mediaFile.play();
};

MediaFile.prototype.play = function()
{
	switch(this.type)
	{
		case "IMAGE":
			return UI.slideshow.display(mediaFile);
		case "VIDEO":
			if(!MediaPlayer.video) return false;
			return MediaPlayer.video.loadMediaFile(mediaFile);
		case "AUDIO":
			return MediaPlayer.audio.loadMediaFile(mediaFile);
	}
};