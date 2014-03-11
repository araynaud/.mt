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

	//if data is object: loop for each key, use Object.merge = function (this, data);
	if(isObject(data))
		Object.merge(this, data, true);
	//if data is a string: absolute URL, relative URL or filename
	else if(isString(data))
		this.url=data;

	this.relPath = this.urlAbsPath ? "/" + this.urlAbsPath : String.combine(Album.serviceUrl, this.relPath);
	this.dateRange = this.getDateRange();

	this.loadDisplayOptions();

	//make MediaFile object instances
	if(!this.groupedFiles) return;

	for(var type in this.groupedFiles)
	{
		var files = this.groupedFiles[type];
		var typeFiles={};
		for(var key in files)
		{
			if(!files.hasOwnProperty(key)) continue;
			var mf = new MediaFile(files[key]);
			typeFiles[mf.id] = mf;
		}
		this.groupedFiles[type] = typeFiles;	
	}

	this.musicFiles = Object.values(this.groupedFiles.AUDIO);
	this.otherFiles = Object.values(this.groupedFiles.FILE);
	this.dirs = Object.values(this.groupedFiles.DIR);
	this.mediaFiles = this.dirs;
	this.mediaFiles = this.mediaFiles.concat(Object.values(this.groupedFiles.IMAGE));
	this.mediaFiles = this.mediaFiles.concat(Object.values(this.groupedFiles.VIDEO));

	this.initTags();
}

Album.serviceUrl = ""; 
Album.defaultFilter="type";

// get album data in JSON format
Album.getAlbumAjax = function(instanceName, search, async, callback)
{	
	if(!search) search={};
	search.format="json";
	search.data="album";	
	search.debug="false";
	async=valueOrDefault(async,false);
	//TODO: pass search as data to $.ajax GET
	var serviceUrl = String.combine(Album.serviceUrl, "data.php");
	var startTime = new Date();

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
			albumInstance = new Album(response);
			var endTime = new Date();
			albumInstance.requestTime = endTime - startTime;
			window[instanceName] = albumInstance;
			if(callback) 
				callback();
			else if(Album.onLoad)
			    Album.onLoad(albumInstance);
		},
		error: Album.onError
	});
	return albumInstance; //only valid if async=false
};

// event callbacks
Album.onLoad = function (albumInstance) 
{
};

Album.onError = function(xhr, textStatus, errorThrown)
{ 
	if(window.UI && UI.setStatus)
		UI.setStatus(textStatus +"\n" +errorThrown);
};

Album.getConfig = function(key)
{	
	if(isMissing(key))
		return config;
	return config[key];
}

Album.get = function(key, default_)
{
	var value=album[key];
	return isMissing(value) ? default_ :  value ;
}

Album.prototype.get = function(key, default_)
{
	var value=this[key];
	return isMissing(value) ? default_ :  value ;
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
}


Album.prototype.setTag = function(tag, file, state)
{
	if(!state)
	{
		delete this.tags[tag][file];
		if(isEmpty(this.tags[tag]))
			delete this.tags[tag];
		return;
	}

	if(!this.tags)		this.tags = {};
	if(!this.tags[tag])	this.tags[tag]= {};
	this.tags[tag][file]= file;
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
		mediaFiles=Album.selectFiles(mediaFiles, "VIDEO","type");
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
		return this.groupedFiles[type][id];

	for(type in this.groupedFiles)
		if(this.groupedFiles[type][id])
			return this.groupedFiles[type][id];
	return null;
};

//get mediafile from currently hover div
Album.prototype.getMediaFileByName = function(name)
{  
	var id = MediaFile.getId(name);
	return this.getMediaFileById(id);
};

//Filter by json object {name: "a", type: "image", date: "", description: "" }
Album.searchFiles = function(fileList, search)
{  
	//loop through object and restrict selection
	if(isEmpty(fileList) || isEmpty(search)) return fileList;
	album.searchResults = Album.selectFiles(fileList, search);
	return album.searchResults;
};

Album.selectFiles = function(fileList, filterValue, filterField)
{  
	if(!fileList) return [];
	if(isFunction(filterValue))
		return fileList.filter(filterValue);
		
	if(!isObject(filterValue))
		Album.filterField=filterField || Album.defaultFilter;
	Album.filterValue=filterValue;
	return fileList.filter(MediaFile.isSelected);
};

Album.prototype.selectFiles = function (filterValue, filterField)
{
	return Album.selectFiles(this.mediaFiles, filterValue, filterField);
};

Album.countFiles = function(fileList, filterValue, filterField)
{  
	if(!fileList) return 0;
	fileList=Album.selectFiles(fileList, filterValue, filterField);
	if(!fileList) return 0;
	return fileList.length || 0;
};

Album.prototype.countFiles = function (filterValue, filterField)
{
	var fileList=this.selectFiles(filterValue, filterField);
	if(!fileList) return 0;
	return fileList.length || 0;	
};

Album.prototype.hasFiles = function (filterValue, filterField)
{
	var fileList=this.selectFiles(filterValue, filterField);
	return !isEmpty(fileList);	
};

//return all files except those filtered
Album.excludeFiles = function(fileList, filterValue, filterField)
{  
	if(!fileList) return [];

	if(isFunction(filterValue))
		return fileList.extract( function () { return !filterValue(); } );

	Album.filterField=filterField || Album.defaultFilter;
	Album.filterValue=filterValue;
	return fileList.extract(MediaFile.isExcluded);
};

Album.prototype.excludeFiles = function (filterValue, filterField)
{
	return Album.excludeFiles(this.mediaFiles, filterValue, filterField);
};

//return filtered files and remove them from list
Album.extractFiles = function(fileList, filterValue, filterField)
{
	if(!fileList) return [];

	if(isFunction(filterValue))
		return this.mediaFiles.extract(filterValue);
   
	Album.filterField=filterField || Album.defaultFilter;
	Album.filterValue=filterValue;
	return fileList.extract(MediaFile.isSelected);
};

Album.prototype.extractFiles = function (filterValue, filterField)
{
	return Album.extractFiles(this.mediaFiles, filterValue, filterField);
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
		var dirs = this.extractFiles("DIR");
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
	var selectedFiles = album.selectFiles({selected: true});
	if(allByDefault && isEmpty(selectedFiles)) 
		selectedFiles = album.activeFileList();
	return selectedFiles;
};

Album.prototype.selectSlideshowFiles = function()
{
	var types = config.MediaPlayer && config.MediaPlayer.slide.enabled ? ["IMAGE", "VIDEO"] : "IMAGE";
	return Album.selectFiles(this.activeFileList(), {type: types});
};

//get files for current page
//depending on countPerPage and pageNumber
Album.prototype.selectCurrentPageFiles = function()
{
	this.startFileIndex=this.countPerPage*(this.pageNum-1); //to keep same page and avoid out of range
	if(!this.pageNum || !this.countPerPage) return this.mediaFiles;

	return this.activeFileList().slice(this.startFileIndex,this.startFileIndex+this.countPerPage);
};

Album.getFileIndex = function(index)
{
	return album.startFileIndex+index;
};

Album.prototype.getFileIndex = function(index)
{
	return this.startFileIndex+index;
};

//array filtering functions
Album.prototype.isMediaFileSelected = function(element, index, array)
{  
	if($.isArray(element[this.filterField]))
		return element[this.filterField].contains(this.filterValue);
	return (element[this.filterField]==this.filterValue);  
};

Album.prototype.isMediaFileExcluded = function(element, index, array)
{  
	return !this.isMediaFileSelected(element, index, array);
};

Album.prototype.selectRange = function(from, to, state)
{
	var i = from;
	from=Math.min(from, to);
	to=Math.max(i, to);
	var fileList=this.activeFileList();
	for(i = from; i<=to; i++)
		fileList[i].toggleSelected(state);
};

//get options from config
//set initial state of album and UI
// do NOT fire events
Album.prototype.loadDisplayOptions = function()
{
	config = this.config; //global config
	if(!config.DISPLAY) return;
	config.DISPLAY.size=valueOrDefault(config.DISPLAY.size,0);
	for(var key in config.DISPLAY)
	{
		var value=config.DISPLAY[key];
		this[key]=value;
		$("input#cb_" + key).prop("checked",value);
		$("select#dd_" + key).val(value);
	}
	return config.DISPLAY;
};