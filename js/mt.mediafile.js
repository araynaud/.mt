/* Client-side access to MediaFile objects
*/

//constructor
function MediaFile(data, type, key)
{ 
	//if data is object: loop for each key, use Object.merge = function (this, data);
	if (data instanceof Album)
	{
		this._parent = data;
		var data = this._parent.getMediaFileById(key, type);
		var metadata = this._parent.getMetadata(type, key);
		Object.merge(this, data, true);
		Object.merge(this, metadata, true);
	}
	else
		Object.merge(this, data, true);

	this.getId();
	this.getTitle();
	this.getRatio();
	this.getVersions();	
	this.getThumbnails();
	this.filename = this.getFilename();
	this.isVideoStream();	
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

//get value from parent album
MediaFile.prototype.getAlbum = function(key, default_)
{
	var value = (key && this._parent) ? this._parent[key] : this._parent;
	return isMissing(value) ? default_ : value;
};

MediaFile.prototype.getConfig = function(key, default_)
{
	return this.getAlbum("config", {})[key];
};

//Todo functions
// makeTitle
// image in .ss, image in parent
// actual stored image from image.php

//add to prototype all functions taking 1 mediaFile as argument
// from filenames.js and templates.js

MediaFile.prototype.getId = function()
{
	if(!this.id)	this.id = MediaFile.getId(String.combine(this.subdir,this.name)); //, this.type);
	if(!this.name) 	this.name=this.id;
	return this.id;
};

MediaFile.prototype.getTitle = function()
{
	if(!this.title)	
		this.title = this.name.makeTitle();
	return this.title;
};

MediaFile.prototype.getRatio = function()
{
	if(!this.ratio && this.height)
		this.ratio = this.width / this.height;
	return this.ratio;
};

MediaFile.prototype.getVersions = function()
{
	if(!isEmpty(this.versions)) return this.versions;
	if(isEmpty(this.exts)) return [];
	this.versions = [];
	for(var i=0; i<this.exts.length; i++)
	{
		var ext = this.exts[i];
		var v = { ext: ext, size: this.size ? this.size[i] : null, date: this.date ? this.date[i] : null };
		this.versions.push(v);
		this.versions[ext] = v;
	}
	return this.versions;
};

MediaFile.prototype.getMaxDimension = function()
{
	return Math.max(this.width || 0, this.height || 0);
}

//1280

//[   1,    1,    0]
//[ 225, 1000, 1920]

MediaFile.prototype.getMaxTnIndex = function()
{
	this.maxtn = this.tnsizes ? this.tnsizes.length-1 : -1;
	var cfg = config.thumbnails;
	var size = this.getMaxDimension();	
	var mf=this;
	//image: last tn smaller than size
	var maxtn = this.tnsizes.findLastIndex(function(el, i)
	{
		var tndir = cfg.dirs[i];
		var tnIsSmaller = !size || (cfg.sizes[tndir] < size);
		if(mf.isImage())
			return tnIsSmaller;
		return tnIsSmaller && (el>0 || config.ENABLE_FFMPEG);
	});

	//video / anim: first tn larger than size
	if(this.isAnimated())
		if(this.thumbnailExists(maxtn+1) || config.ENABLE_FFMPEG)
			maxtn++;

	this.maxtn = maxtn;
	return this.maxtn;
}

MediaFile.prototype.getThumbnails = function()
{
	if(this.isDir() && isEmpty(this.thumbnails))
		return;
	if(this.isDir())
		return this.tncolumns = this.thumbnails.divideInto(2);
	if(isEmpty(this.tnsizes) || isEmpty(config.thumbnails)) 
		return this.tnsizes;

	this.getMaxTnIndex();
	this.tnsizes = this.tnsizes.slice(0, this.maxtn+1);
	return this.tnsizes;
}

MediaFile.prototype.loadThumbnails = function (count)
{	
	if(!this.isDir()) return;

	if(isMissing(this.thumbnails))
		this.thumbnails = this.tncolumns = [];

	count = valueOrDefault(count, 6);
	var params = { data: "thumbnails", count: count, empty: true, depth: 2 };
	var mf=this;
	var callbacks = { 
		success: function (response){
			if(!isArray(response)) return;
			mf.thumbnails = response;
			mf.tncolumns = mf.thumbnails.divideInto(2);
			UI.refreshMediaFile(mf);
		}
		//error: function() { mf.thumbnails = []; }
	};
	this.scriptAjax("data.php", params, true, false, callbacks);
	return this.thumbnails;
}

MediaFile.getId = function(name, type)
{
	var id = name.replace(/[\.#\/\?!\(\)\{\}' ,&\[\]~]/g,"_");
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

MediaFile.prototype.searchString = function()
{
	var exts = this.exts ? this.exts.join(" ") : "";
	var tags = this.Tags().makeTitle();
	return "{0} {1} {2} {3} {4} {5} {6}".format(this.name, exts, this.getTitle(), tags, this.description || "", this.getFormattedDate());
}

MediaFile.prototype.initTags = function()
{
	// array to object.
	if(isEmpty(this.tags))
		this.tags = {};

	if(isArray(this.tags))
		this.tags=this.tags.toMap();
	return this.tags;
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
	return mediaFile.isVideo();
};

MediaFile.prototype.isVideo = function()
{
	return this.type=="VIDEO";
//	return MediaFile.hasType(this, "VIDEO");
};

MediaFile.isVideoStream = function(mediaFile)
{
	return mediaFile.isVideoStream();
};

MediaFile.prototype.isVideoStream = function()
{
	if(isMissing(this.stream) && !isEmpty(this.exts))
		this.stream = this.exts.intersect(config.TYPES.VIDEO_STREAM, "toLowerCase");

	//$this->stream = array_values(array_intersect($streamTypes, $this->exts));

	if(this.type!="VIDEO" || isEmpty(this.stream)) return false;
	return isArray(this.stream) ? this.stream[0] : this.stream;
};

MediaFile.isAnimated = function(mediaFile)
{
	return mediaFile.isAnimated();
};

MediaFile.prototype.isAnimated = function()
{
	return this.animated || this.isVideo();
}

MediaFile.isLocalVideoStream = function(mediaFile)
{
	return mediaFile.isLocalVideoStream();
};

MediaFile.prototype.isLocalVideoStream = function()
{
	var stream = this.isVideoStream();
	return stream && stream!="youtube";
};

MediaFile.isExternalVideoStream = function(mediaFile)
{
	return mediaFile.isExternalVideoStream();
};

MediaFile.prototype.isExternalVideoStream = function()
{
	var stream = this.isVideoStream();
	return stream=="youtube" || stream=="vimeo";
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
	return Object.values(this.tags).sort();
};

MediaFile.prototype.Tags = function()
{
	if(isEmpty(this.tags)) return "";
	return Object.values(this.tags).sort().join(" ");
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

MediaFile.prototype.getFormattedDate = function(includeTime)
{
	if(!this.takenDate) return "";
	return new Date(this.getTakenDate()).toDateString();
}

MediaFile.prototype.getFormattedDateTime = function(includeTime)
{
	if(!this.takenDate) return "";
	return new Date(this.getTakenDate(true).replace(" ", "T")).toLocaleFormat();
}


MediaFile.getPath = function (mediaFile)
{
	return mediaFile.getPath();
};

MediaFile.prototype.getPath = function(ext)
{
	return String.combine(this._parent.path, this.subdir);
};

MediaFile.getFilePath = function (mediaFile, ext)
{
	return mediaFile.getFilePath();
};

MediaFile.prototype.getFilePath = function(ext)
{
	return String.combine(this._parent.path, this.subdir, this.getFilename(ext));
};

MediaFile.getFileUrl = function (mediaFile, ext)
{
	if(mediaFile.stream=="youtube")
		return config.youtube.videoUrl.format(mediaFile.id);

	var filename=mediaFile.filename;
	if(isMissing(ext))
		ext="";
	if(isArray(ext))
		ext=ext[0];
	if(!isString(ext) && mediaFile.exts)
		ext=mediaFile.exts[ext];
	if(ext)
		filename=mediaFile.name+"."+ext;
	if(mediaFile.isDir() && mediaFile.urlAbsPath)
		return mediaFile.urlAbsPath;
	var baseUrl = mediaFile.urlAbsPath || mediaFile._parent.relPath;
	return String.combine(baseUrl, mediaFile.subdir, filename);
};

MediaFile.prototype.getFileUrl = function (ext)
{
	return MediaFile.getFileUrl(this, ext);
};

MediaFile.getShortPath = function (mediaFile)
{
	return mediaFile.getShortPath();
};

MediaFile.prototype.getShortPath = function ()
{
	return String.combine(this._parent.path, this.subdir, this.name);
};

MediaFile.prototype.getStartPath = function ()
{
	if(this.isDir()) return this.getShortPath();
	return String.combine(this._parent.path, this.subdir) + ":" + this.name;
};

MediaFile.prototype.getHashPath = function ()
{
	if(this.isDir()) return this.getShortPath();
	return String.combine(this._parent.path, this.subdir) + "#" + this.name;
};

MediaFile.prototype.getShortUrl = function ()
{
	return UI.appRootUrl() + "?" + this.getStartPath();
};

MediaFile.getFileDir = function(mediaFile, subdir)
{
	var baseUrl = mediaFile.urlAbsPath || mediaFile._parent.relPath;
	return String.combine(baseUrl, mediaFile.subdir, subdir);
};

MediaFile.prototype.getFileDir = function(subdir)
{
	return MediaFile.getFilePath(this, subdir);
};

MediaFile.getScriptUrl = function(mediaFile, scriptName, params)
{
	scriptName = String.combine(Album.serviceUrl,scriptName);
	if(!params) params = {};
	if(mediaFile)
	{
		params.path = mediaFile.getPath();
		params.file =  String.urlEncode(mediaFile.filename);
	}
	else if(window.album)
		params.path = album.path;
	return scriptName + "?" + Object.toQueryString(params);
};

MediaFile.prototype.getScriptUrl = function (scriptName, params)
{
	return MediaFile.getScriptUrl(this, scriptName, params);
};

MediaFile.makePostData = function(mediaFile, params)
{
	if(!params) params={};
	params.path = mediaFile.getPath();
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
	var baseUrl = mediaFile.urlAbsPath || mediaFile._parent.relPath;
	if(isMissing(tnIndex) || isEmpty(mediaFile.tnsizes) || isMissing(mediaFile.tnsizes[tnIndex]))
		return String.combine(baseUrl, mediaFile.subdir);
	
	return String.combine(baseUrl, mediaFile.subdir, "." + config.thumbnails.dirs[tnIndex]);	
};

MediaFile.prototype.getThumbnailDir = function(tnIndex)
{
	return MediaFile.getThumbnailDir(this, tnIndex);
};

MediaFile.getThumbnailUrl = function(mediaFile, tnIndex, create)
{
	tnIndex=valueOrDefault(tnIndex,0);
//if image smaller than slideshow or animated, use original
	if(mediaFile.stream=="youtube")
		return config.youtube.imageUrl.format(mediaFile.id);

	if(mediaFile.isImage() && !mediaFile.hasThumbnail(tnIndex))
		return mediaFile.getFileUrl();

	if(!mediaFile.hasThumbnail(tnIndex))
		tnIndex=0;
	if(mediaFile.isVideo() && !config.ENABLE_FFMPEG && !mediaFile.thumbnailExists(tnIndex))
	{
		tnIndex=0;
	 	create=false;
	}
//if already exists => existing image url
	if(mediaFile.thumbnailExists(tnIndex))
	{
		var filename = mediaFile.filename;
		var ext = config.thumbnails[mediaFile.type].ext;
		if(ext)
			filename = mediaFile.name + "." + ext;
		var baseUrl = mediaFile.urlAbsPath || album.relPath;
		return String.combine(baseUrl, mediaFile.subdir, "." + config.thumbnails.dirs[tnIndex], filename);
	}

//if not already exists => create script url
	create=valueOrDefault(create, false);
	var cfg = config.thumbnails[mediaFile.type];
	if(!cfg)	return "";
	if(!create || !cfg.script) return cfg["default"];
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

MediaFile.getUploadFileSize = function (mediaFile)
{	
	return mediaFile.getUploadFileSize();
};

MediaFile.prototype.getUploadFileSize = function()
{	
	var stream = this.isVideoStream() 
	if(stream)	return this.getFileSize(stream);

//	if(this.isImage())	return this.tnsizes[this.tnsizes.length-1];

	return this.getFileSize();
}

MediaFile.getFileSize = function (mediaFile, tn)
{	
	return mediaFile.getFileSize(tn);
};

MediaFile.prototype.getFileSize = function (tn)
{	
	tn=valueOrDefault(tn, 0);
	if(!this.versions || !this.versions[tn]) return 0;
	return this.versions[tn].size || this.versions[this.exts[tn]].size;
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
	if(mediaFile.isVideo() && !config.ENABLE_FFMPEG && !mediaFile.thumbnailExists(tnIndex))
		tnIndex=0;

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
			if(result)
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

MediaFile.prototype.loadSubtitles = function ()
{	
	//reload file each time or not ?
	this.subtitles=[];
	var params = { data: "tableFile", file: this.name +".sub", empty: true };
	var callbacks = {};
	callbacks.success = function(response, mf) 
	{
		mf.subtitles=[];
		for (i=0; i < response.length; i++)
		{
			var sub = response[i];
			mf.subtitles.push({time: sub.start, text: sub.text.replace("\\","\n")});
			mf.subtitles.push({time: sub.end, text: "" });
		}
		return mf.subtitles;
	};

	this.scriptAjax("data.php", params, true, false, callbacks);
};

//run image script via ajax request
MediaFile.scriptAjax = function (mediaFile, script, params, async, post, callbacks)
{	
//	if(!script || !mediaFile) return false;

	async = valueOrDefault(async, false);
	params = valueOrDefault(params, {});
	if(isMissing(params.path))
		if(mediaFile) 
			params.path = mediaFile.getPath();
		else if(window.album)
			params.path = album.getPath();

	if(mediaFile && isMissing(params.file)) params.file = mediaFile.getFilename(); 
	// Album.prototype.getScriptUrl = function (scriptName, params)
	var scriptUrl = Album.getScriptUrl(script);
	var method = post ? "POST" : "GET";

	if(config.debug.ajax)
	{
		params.debug=true;
		var link = $.makeElement("a", { href: scriptUrl.appendQueryString(params), target: "debug"})
		delete params.debug;
		link.html(scriptUrl.appendQueryString(params));
		if(window.UI) UI.addStatus(link.outerHtml());
	}

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
			var response = Album.parseErrorResponse(xhr.responseText);
			result=response;

			//result = false;
			if(window.UI && config.debug.ajax)
			{
				if(response.jsonError)
					UI.addStatus(response.jsonError);
				UI.addStatus(response.serverError);
				if(config.debug.ajax && link)
					UI.addStatus(link.outerHtml());
			}
 			if(callbacks && callbacks.error)
				callbacks.error(xhr, mediaFile);
			if(callbacks && callbacks.success)
				callbacks.success(response, mediaFile, params);
			if(callbacks && callbacks.next)
				callbacks.next(response, script, params, callbacks);
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
	if(window.UI)
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
			result=MediaFile.testProperty(element, key, Album.filterValue[key]);
			if(!result) break;		
		}
		return result;
	}

	return true;
};

MediaFile.testProperty = function(element, key, value)
{  
	//find value in an element of the array
	var field = element[key];
	if(isMissing(field)) return isMissing(value);

	if(isFunction(field)) //call method
		field = element[key]();

	if(isArray(value))
	{
		var result=false;
		for(var n=0; n<value.length; n++)
		{
			result = MediaFile.testProperty(element, key, value[n]);			
			if(xor(value.matchAll, result)) break;		
		}
		return result;
	}

	if(isEmpty(field))  return isEmpty(value);
	if(isString(field)) return field.containsText(value);
	if(isArray(field))  return field.contains(value);
	if(isObject(field)) return field.hasOwnProperty(value);
	return (field == value);  
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

MediaFile.prototype.getFileIndex = function(index)
{
	this.index = album.getFileIndex(index);
	return this.index;
};


MediaFile.prototype.toggleSelected = function(state)
{
	this.selected = valueOrDefault(state, this.selected);
	this.getDiv().toggleClass("selected", this.selected);
};

MediaFile.play = function(el)
{
	mediaFile = album.getByAttribute(el);
	return mediaFile.play();
};

MediaFile.prototype.play = function()
{
	switch(this.type)
	{
		case "VIDEO":
			if(this.isExternalVideoStream() && (config.youtube.mode!="iframe" || !MediaPlayer.YouTubeReady))
				return UI.goToUrl(this.getFileUrl(), "YouTube");
				//TODO open youtube url or app in youtube tab
			if(!window.MediaPlayer || !this.isVideoStream())
				return false;
			if(config.display.playVideo=="video")
				return MediaPlayer.loadPlaylist("video", album.mediaFiles, this);
		case "IMAGE":
			if(window.UI)
				return UI.slideshow.display(this);
		case "AUDIO":
			if(window.MediaPlayer && MediaPlayer.audio)
				return MediaPlayer.audio.loadMediaFile(this);
			break;
		case "TEXT":
			UI.displayArticle(this);
	}
	return false;
};

MediaFile.getDuration = function(mediaFile)
{
	return mediaFile.getDuration();	
};

MediaFile.prototype.getDuration = function()
{
	var defaultInterval = (window.UI && UI.slideshow && UI.slideshow.interval) ? UI.slideshow.interval : 0;
	return this.duration || defaultInterval / 1000;	
};

MediaFile.getFileUrls = function(mediaFiles)
{
	var urls=[];
	mediaFiles.forEach(function(n) { urls.push(MediaFile.getFileUrl(n)); });
	return urls;
}