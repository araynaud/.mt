// Client-side access to Album object

//constructor
function Album(data)
{ 
	this.type=Object.getType(this);
	//paging variables
	this.countPerPage=0;
	this.pageNum=1;
	this.startFileIndex=0;
	this.nbPages=1;
	this.tags = {};

	//if data is object: loop for each key, use Object.merge = function (this, data);
	if(isObject(data))
		Object.merge(this, data, true);
	//if data is a string: absolute URL, relative URL or filename
	else if(isString(data))
		this.url=data;

	if(!this.path) this.path = "";
	this.relPath = this.urlAbsPath ? this.urlAbsPath : String.combine(Album.serviceUrl, this.relPath);
	this.getTitle();
	this.dateRange = this.getDateRange();

	this.loadDisplayOptions();

	//make MediaFile object instances
	if(!this.groupedFiles) return;

	for(var type in this.groupedFiles)
	{
		if(!this.groupedFiles.hasOwnProperty(type)) continue;
		var files = this.groupedFiles[type];
		//var metadata = this.metadata ? this.metadata[type] : null;
		var typeFiles={};
		for(var key in files)
		{
			if(!files.hasOwnProperty(key)) continue;
			var mf = new MediaFile(this, type, key);
			typeFiles[mf.id] = mf;
		}
		this.groupedFiles[type] = typeFiles;	
	}

	this.loadYouTube();
	this.otherFiles = this.getFilesByType("FILE");
	this.articleFiles = this.getFilesByType("TEXT");
	this.musicFiles = this.getFilesByType("AUDIO");
	this.mediaFiles = this.getFilesByType(["DIR", "IMAGE", "VIDEO"]);

	this.countByType = this.mediaFiles.countBy("type");

	this.loadTags();
	this.initTags();
}

Album.serviceUrl = ""; 
Album.defaultFilter="type";

Album.prototype.loadYouTube = function ()
{
	if(!this.youtube || !window.MediaPlayer || !MediaPlayer.YouTubeReady) return;

	this.tags.YouTube=[];
	if(!this.groupedFiles.VIDEO)
		this.groupedFiles.VIDEO={};
	for(var key in this.youtube)
	{
		var mf= {id: key, name: key, title: this.youtube[key], type:"VIDEO", stream:"youtube", _parent: this};
		mf = new MediaFile(mf);
		this.tags.YouTube.push(key);
		this.groupedFiles.VIDEO[key]=mf;
	}
}

Album.prototype.getScriptUrl = function (scriptName, params)
{
	if(!params)	params = {};
	if(!params.path) params.path = this.path;
	return Album.getScriptUrl(scriptName, params);
};

Album.getScriptUrl = function(scriptName, params)
{
	scriptName = String.combine(Album.serviceUrl, scriptName);
	var qs = Object.toQueryString(params);
	if(!params || !qs) return scriptName;
	return scriptName + "?" + qs;
};

// get album data in JSON format
Album.getAlbumAjax = function(instanceName, search, async, callback)
{	
	if(!search) search={};
	search.data = "album";	
	search.debug = "false";
	search.config = valueOrDefault(search.config, true);
	search.details = valueOrDefault(search.details, 3);
	async=valueOrDefault(async,false);
	//TODO: pass search as data to $.ajax GET

    Album.isExternal = Album.serviceUrl.indexOf("//") >= 0;
    if(!Album.isExternal) Album.proxy = null;
    var serviceUrl = String.combine(Album.proxy, Album.serviceUrl, "data.php");
	var startTime = new Date();

	if(isEmpty(Album.ajaxLoader)) Album.ajaxLoader = $("#ajaxLoader");

	Album.ajaxLoader.show();

	var albumInstance;
	$.ajax({
		url: serviceUrl,
		data: search,
		dataType: "json",
		contentType: "application/json",
		cache: false,
		async: async,
		success: function(response)
		{ 
			response.serviceUrl = Album.serviceUrl;
			response.startTime = startTime;
			if(Album.isExternal)
				response.urlAbsPath = Album.serviceUrl + "/" + response.urlAbsPath;

			Album.ajaxLoader.hide();
			Album.createInstance(response, instanceName, callback);
		},
		error: function(xhr, textStatus, errorThrown)
		{ 
			var response = Album.parseErrorResponse(xhr.responseText);
			response.startTime = startTime;
			Album.ajaxLoader.hide();
			if(!response.jsonError)
			{
				Album.createInstance(response, instanceName, callback);
			}
			if(window.UI && UI.setStatus)
			{
				UI.setStatus(response.jsonError);
				UI.addStatus(response.serverError);
			}
		}

	});
	return albumInstance; //only valid if async=false
};

//provide base url and array of filenames
Album.getExternalUrls = function()
{	
	var instanceName = "album";
	var album = {title: "external urls"};
	var listUrl="http://localhost/mt/data.php?data=tablefile&path=web/GTA&file=cars.csv";
	$.getJSON(listUrl).then(function(response)
	{
		album.groupedFiles = { IMAGE: response };
		Album.createInstance(album, instanceName);
	});
}

//json from external site: use proxy.php
Album.getExternalFileList = function()
{
	var instanceName = "album";
	var listUrl="http://localhost/mt/data.php?data=tablefile&path=web/GTA&file=cars_imgurl_files.csv&header=false";
	var listUrl="http://localhost/pictures/web/GTA/racer2.csv";
	$.get(listUrl).then(function(response, status)
	{
		response = String.parseCsv(response);
		album.urlAbsPath = response.shift();
		album.groupedFiles = { IMAGE: response };
		Album.createInstance(album, instanceName);
	});
}

Album.createInstance = function (response, instanceName, callback) 
{
	response.requestTime = new Date() - response.startTime;
	albumInstance = new Album(response);
	if(instanceName)
		window[instanceName] = albumInstance;
	if(callback) 
		callback();
	else if(Album.onLoad)
	    Album.onLoad(albumInstance);
	return albumInstance;
};


// event callbacks
Album.onLoad = function (albumInstance) 
{
};

Album.onError = function (albumInstance) 
{
};

//JSON parse error:
//take response text
//extract substring before <br/> or [ or { : display error message UI.setStatus
//parse the rest of response as JSON
Album.parseErrorResponse = function(responseText)
{
	var sep='"';
	if(responseText.endsWith("}"))
		sep = "{";
	else if(responseText.endsWith("]"))
		sep = "[";

	var json = responseText.substringAfter(sep, false, false, true);
	var obj = {};
	try{
		obj = JSON.parse(json);
	}
	catch(err)
	{
		obj.jsonError = err.message;
	}
	obj.serverError = responseText.substringBefore(sep);
	return obj;
}

Album.getConfig = function(key)
{
	var cfg = Album.config;
	if(!cfg) cfg = window.config;
	return valueIfDefined(key, cfg);
};

Album.prototype.getConfig = function(key)
{	
	if(!key) return this.config;
	return valueIfDefined(key, this.config);
};

Album.prototype.get = function(key, default_)
{
	if(!key) return this;
	var value = valueIfDefined(key, this);
	return isMissing(value) ? default_ : value;
};

Album.prototype.setOptions = function(options)
{
	if(isObject(options))
		Object.merge(this, options, true);
};

Album.prototype.initTags = function()
{
	if(!this.tags) return;
	for(tag in this.tags)
	{
		var tagList=this.tags[tag];
		if(!isArray(tagList)) continue;
		this.tags[tag] = tagList.toMap();
	}
};

Album.prototype.setTag = function(tag, file, state)
{
	var listChanged=false;
	if(!state)
	{
		delete this.tags[tag][file];
		if(isEmpty(this.tags[tag]))
		{
			listChanged=true;
			delete this.tags[tag];
		}
		return listChanged;
	}

	if(!this.tags)		this.tags = {};
	if(!this.tags[tag])
	{
		listChanged=true;
		this.tags[tag] = {};
	}
	this.tags[tag][file] = file;
	return listChanged;
};

Album.prototype.loadTags = function()
{
	//for each tag: assign tag to mediaFiles
	if(!this.tags) return;

	for(tag in this.tags)
	{
		var tagList=this.tags[tag];
		if(!isArray(tagList)) continue;

		for(var i=0; i<tagList.length;i++)
		{
			var mf = this.getMediaFileByName(tagList[i]);
			if(mf)	mf.setTag(tag, true);
		}
	}
};

Album.prototype.contains = function(key)
{
	return !isMissing(this[key]);
};

//	"oldestDate": "2012-03-17 19:08:24",
//	"newestDate": "2013-05-02 13:24:52",

Album.prototype.getTitle = function()
{
	if(!this.title)	
		this.title = this.path.substringAfter("/", true, true).makeTitle();
	return this.title;
};


Album.prototype.getDateRange = function(dateOnly)
{
	if(Date.dateRange)
		return Date.dateRange(this.oldestDate, this.newestDate, dateOnly);
};


Album.prototype.formatDateRange = function(dateOnly)
{
	return Date.formatDateRange(this.oldestDate, this.newestDate, dateOnly);
};


//TODO: add to prototype all functions taking 1 Album as argument
// from filenames.js and templates.js


//after window.load, create thumbnails 1 by 1 
Album.prototype.createMissingThumbnails = function(tnIndex, type)
{
	tnIndex=valueOrDefault(tnIndex,0);
	var mediaFiles = this.mediaFiles.filter(MediaFile.needsThumbnail);
	if(type)
		mediaFiles=Album.selectFiles(mediaFiles, {type: "VIDEO"});
	$("#description").html("");
	for(var k=0;k<mediaFiles.length;k++)
	{	
		var url = mediaFiles[k].getThumbnailUrlAjax(tnIndex);
		if(!url) continue;
		$("#description").append("\n{0}: <img class='tinyThumb' src='{1}'/>".format(mediaFiles[k].name, url));
		$("#"+mediaFiles[k].id).prepend("<img alt='{0}' src='{1}'/>".format(mediaFiles[k].name, url));
	}
};

//getMediaFileBy HTML element id
Album.prototype.getByAttribute = function(el)
{
	if(el instanceof MediaFile)	return el;

	if(isString(el))	return this.getMediaFileById(el);

	if(!el.is(".file"))
		el = el.parents("div.file");

	var id = el.attr("id");
	var type = this.getMediaFileType(el);
	var mediaFile = this.getMediaFileById(id, type);
	return mediaFile;
};

Album.prototype.getMediaFileType = function(el)
{
	for(type in this.groupedFiles)
		if(el.hasClass(type.toLowerCase()))
			return type;
	return "";
}

Album.prototype.getMediaFileById = function(id, type)
{
	if(type && this.groupedFiles[type][id])
	{
		var mf = this.groupedFiles[type][id];
		if(isString(mf))
			return {url: this.urlAbsPath + mf, name: mf.getFilenameNoExt(), filename: mf, exts: [mf.getExt()] };
		return mf;
	}
	
	for(type in this.groupedFiles)
		if(this.groupedFiles[type][id])
			return this.groupedFiles[type][id];
	return null;
};

//get mediafile from currently hover div
Album.prototype.getMediaFileByName = function(name, type)
{  
	var id = MediaFile.getId(name);
	return this.getMediaFileById(id, type);
};

Album.prototype.getMetadata = function(type, key)
{  
	if(!this.metadata || !this.metadata[type]) return null;
	var meta = this.metadata[type];
	return key ? meta[key] : meta;
};


//Filter by json object {name: "a", type: "image", date: "", description: "" }
Album.searchFiles = function(fileList, search)
{  
	//loop through object and restrict selection
	if(isEmpty(fileList) || isEmpty(search)) return fileList;
	return Album.selectFiles(fileList, search);
};

Album.prototype.searchFiles = function(fileList, search)
{  
	//loop through object and restrict selection
	this.searchFilters = search;
	if(isEmpty(fileList) || isEmpty(search)) return fileList;
	this.searchResults = Album.selectFiles(fileList, search);
	return this.searchResults;
};


Album.selectFiles = function(fileList, filterValue)
{  
	if(!fileList) return [];
	if(isFunction(filterValue))
		return fileList.filter(filterValue);
		
	Album.filterValue=filterValue;
	return fileList.filter(MediaFile.isSelected);
};

Album.prototype.selectFiles = function (filterValue)
{
	return Album.selectFiles(this.mediaFiles, filterValue);
};

Album.countFiles = function(fileList, filterValue)
{  
	if(!fileList) return 0;
	fileList=Album.selectFiles(fileList, filterValue);
	if(!isEmpty(fileList)) return 0;
	return fileList.length;
};

Album.prototype.countFiles = function (filterValue)
{
	var fileList=this.selectFiles(filterValue);
	if(!isEmpty(fileList)) return 0;
	return fileList.length;
};

Album.hasFiles = function(fileList, filterValue)
{  
	if(!fileList) return 0;
	fileList=Album.selectFiles(fileList, filterValue);
	return !isEmpty(fileList);	
};

Album.prototype.hasFilesOfType = function(type)
{  
	if(!type)
		return !!album.groupedFiles;
	return album.groupedFiles && !!album.groupedFiles[type.toUpperCase()];
};

Album.prototype.hasFiles = function (filterValue)
{
	var fileList=this.selectFiles(filterValue);
	return !isEmpty(fileList);	
};

//return all files except those filtered
Album.excludeFiles = function(fileList, filterValue)
{  
	if(!fileList) return [];

	if(isFunction(filterValue))
		return fileList.extract( function (mf) { return !filterValue(mf); } );

	Album.filterValue=filterValue;
	return fileList.extract(MediaFile.isExcluded);
};

Album.prototype.excludeFiles = function (filterValue)
{
	return Album.excludeFiles(this.mediaFiles, filterValue);
};

//return filtered files and remove them from list
Album.extractFiles = function(fileList, filterValue)
{
	if(!fileList) return [];
	if(isFunction(filterValue))
		return fileList.extract(filterValue);
   
	Album.filterValue=filterValue;
	return fileList.extract(MediaFile.isSelected);
};

Album.prototype.extractFiles = function (filterValue)
{
	return Album.extractFiles(this.mediaFiles, filterValue);
};

Album.prototype.sortFiles = function(sortOptions)
{  
	if(!sortOptions)
		 sortOptions=this;
	if(sortOptions.sort=="random")
	{
		this.mediaFiles.shuffle();
		this.otherFiles.shuffle();
		if(!isEmpty(this.searchResults))
			this.searchResults.shuffle();
	}
	else
	{
		this.mediaFiles.sortObjectsBy(sortOptions.sort, sortOptions.reverse);
		if(!isEmpty(this.searchResults))
			this.searchResults.sortObjectsBy(sortOptions.sort, sortOptions.reverse);
		if(!isEmpty(this.otherFiles))
			this.otherFiles.sortObjectsBy(sortOptions.sort, sortOptions.reverse);
		if(!isEmpty(this.musicFiles))
			this.musicFiles.sortObjectsBy(sortOptions.sort, sortOptions.reverse);
	}
	
	//extract dirs, put them first
	if(sortOptions.dirsFirst)
	{
		var dirs = this.extractFiles({type:"DIR"});
		this.mediaFiles = dirs.concat(this.mediaFiles); 
	}	
//		this.mediaFiles.sortObjectsBy(MediaFile.isDir,true); //put dirs first
	this.pageNum=1;
 	return this.mediaFiles;
};

// paging methods
Album.prototype.setCountPerPage = function(countPerPage)
{	
	this.countPerPage=countPerPage;
	if(isNaN(this.countPerPage)) this.countPerPage=0;
	this.pageNum = this.countPerPage==0 ? 0 :
		parseInt(this.startFileIndex / this.countPerPage)+1;

	this.getNumberOfPages();
	return this.countPerPage;
};

Album.prototype.getNumberOfPages = function()
{
	this.nbPages = (this.countPerPage == 0) ? 1 :
		parseInt((this.activeFileList().length + this.countPerPage - 1) / this.countPerPage);
	return this.nbPages;
};


//get valid page number
Album.prototype.getPageNumber = function(pageNum)
{
	pageNum = valueOrDefault(pageNum,0);
	return (pageNum + this.nbPages - 1) % this.nbPages + 1;
}

Album.prototype.getNextPageNumber = function(pageNum)
{	
	pageNum = valueOrDefault(pageNum,1);
	return this.getPageNumber(this.pageNum + pageNum);
};


//go to absolute page number
Album.prototype.setPageNumber = function(pageNum)
{
	this.pageNum = this.getPageNumber(pageNum);
//	this.pageNum = (pageNum + this.nbPages - 1) % this.nbPages + 1;
	this.startFileIndex=this.countPerPage*(this.pageNum-1); //to keep same page and avoid out of range
	return this.pageNum;
};

//relative page number
Album.prototype.nextPage = function(pageNum)
{	
	pageNum = valueOrDefault(pageNum,1);
	return this.setPageNumber(this.pageNum + pageNum);
};

Album.prototype.previousPage = function(pageNum)
{	
	pageNum = valueOrDefault(pageNum,1);
	return this.setPageNumber(this.pageNum - pageNum);
};

//return mediaFiles or searchResults if a search is active.
Album.prototype.activeFileList = function()
{
	if(!isEmpty(this.searchResults))
		return this.searchResults;

	if(!isEmpty(this.mediaFiles))
		return this.mediaFiles;

	return [];
}

Album.prototype.getSelection = function(allByDefault)
{
	var selectedFiles = this.selectFiles({selected: true});
	if(allByDefault && isEmpty(selectedFiles)) 
		selectedFiles = this.activeFileList();
	return selectedFiles;
};

Album.prototype.getSelectedFileNamesArray = function()
{
	var selectedFiles = this.getSelection();
	var names=[];
	for(var i=0;i<selectedFiles.length;i++)
		names.push(selectedFiles[i].name);
	return names;
};

Album.prototype.getSelectedFileNames = function(separator)
{
	separator = valueOrDefault(separator,"|");
	var names = this.getSelectedFileNamesArray();
	return names.join(separator);
};

Album.prototype.selectSlideshowFiles = function()
{
	var types = isDefined("MediaPlayer") && this.getConfig("MediaPlayer.slide.enabled") ? ["IMAGE", "VIDEO"] : "IMAGE";
	var files = Album.selectFiles(this.activeFileList(), {type: types});
	if(!valueIfDefined("MediaPlayer.YouTubeReady") || this.getConfig("youtube.mode") !="iframe") //remove youtube files if disabled
		files = Album.excludeFiles(files, MediaFile.isExternalVideoStream);
	return files;
};


Album.prototype.getFilesByType = function(type)
{
	if(isString(type))
		return Object.values(this.groupedFiles[type]);
	
	var files=[];
	if(isArray(type))
		for(var i=0; i<type.length; i++)		
			files = files.concat(this.getFilesByType(type[i]));

	return files;
};

Album.prototype.getFileNamesByType = function(type)
{
	var tfiles=this.groupedFiles[type];
	if(!tfiles) return [];
	return Object.keys(tfiles);
}

//get files for current page
//depending on countPerPage and pageNumber
Album.prototype.selectCurrentPageFiles = function()
{
	this.startFileIndex=this.countPerPage*(this.pageNum-1); //to keep same page and avoid out of range
	if(!this.pageNum || !this.countPerPage) return this.activeFileList();

	return this.activeFileList().slice(this.startFileIndex, this.startFileIndex + this.countPerPage);
};

Album.getFileIndex = function(index)
{
	return album.getFileIndex(index);
};

Album.prototype.getFileIndex = function(index)
{
	return this.startFileIndex + (index || 0);
};

Album.prototype.selectAll = function(state)
{
	return this.selectRange(null, null, state)
};

Album.prototype.selectRange = function(from, to, state)
{
	var fileList=this.activeFileList();
	from = valueOrDefault(from, 0);
	to = valueOrDefault(to, fileList.length-1);
	var i = from;
	from=Math.min(from, to);
	to=Math.max(i, to);
	for(i = from; i<=to; i++)
		fileList[i].toggleSelected(state);
};

//get options from config
//set initial state of album and UI
// do NOT fire events
Album.prototype.loadDisplayOptions = function()
{
	if(!this.config) return;
	
	Album.config = this.config;
	var displayConfig = this.config.DISPLAY || this.config.display;
	if(!displayConfig) return;
	displayConfig.size=valueOrDefault(displayConfig.size,0);
	for(var key in displayConfig)
	{
		var value=displayConfig[key];
		this[key]=value;
		$("input#cb_" + key).prop("checked",value);
		$("select#dd_" + key).val(value);
	}
	return displayConfig;
};

Album.prototype.isMultiColumn = function()
{
	return !album.cropRatio && album.columns > 1 ;
}

Album.getShortPath = function ()
{
	return album.path;
};

Album.prototype.getShortPath = function ()
{
	return this.path;
};

Album.prototype.getShortUrl = function ()
{
	return UI.appRootUrl() + "?" + this.getShortPath();
};
