window.UI = window.UI || {};

// FILE SELECTION / SORTING / PAGING 
UI.sortFiles = function(refreshDisplay)
{  
	refreshDisplay=valueOrDefault(refreshDisplay,true);
	//get sort options from UI
	UI.getDisplayOptions(album);
	//call album sort
	album.sortFiles();

	UI.transition.setType("slideHorizontal");

	//album.onSort callback
	//for slideshow
	UI.slideshow.pics=album.selectFiles("IMAGE", "type");
	if(refreshDisplay)
		return UI.displaySelectedFiles();
};

//display options

//get options from UI
UI.getDisplayOptions = function(obj)
{
	if(!obj) obj={};
	$("select.dOption, select.sOption").each(function()
	{
		obj[this.id.substringAfter("dd_")]=$(this).val();
	});
	
	$("input.dOption, input.sOption").each(function()
	{
		obj[this.id.substringAfter("cb_")]=$(this).is(":checked");
	});

	obj.tnIndex = obj.columns>1 ? 0 : Math.min(obj.maxTnIndex, UI.sizes[obj.size].tnIndex);
	return obj;
};

//Zoom in or out
UI.zoom = function(increment)
{
	increment=valueOrDefault(increment,1);
	if(album.columns<=1)
		$('#dd_size').selectNextOption(increment);
	else if (album.fit=="width")
		$('#dd_columns').selectNextOption(-increment);
	else 
		$('#dd_columns').selectNextOption(increment);
};


//refresh method
UI.displaySelectedFiles = function(transition,append)
{
	UI.selectedFiles=album.selectCurrentPageFiles();
	UI.displayFiles(UI.selectedFiles, transition, append);
};

UI.displaySearchResults = function()
{
	UI.displayFiles(album.searchResults);
};
	
//refresh method
UI.displayFiles = function(selectedFiles,transition,append)
{
	UI.setGroup();

	UI.displayEdit();
	if(isEmpty(selectedFiles)) return;
	if(append)	
		transition = false;
	else
	{
		UI.renderedPages = [];
		$(window).scrollTop(0);
	}

	UI.mediaFileDiv = transition ? UI.transition.getNextSlide() : UI.transition.getCurrentSlide();
	UI.setStatus();
	UI.getDisplayOptions(album);
	
	UI.displayPageLinks(album);
	if(album.nbPages>1)
		UI.displayFileCounts(selectedFiles,"#pageCounts",true);
	else 
		$("#pageCounts").html("");
	
	total=0;

	$("#columnOptions").toggle(album.columns>1);
	$("#rowOptions").toggle(album.columns<=1);

	UI.mediaFileDiv.toggleClass("row", album.columns<1);
	if(!append)
		UI.mediaFileDiv.html("");

	UI.pageDiv = UI.mediaFileDiv;
	if(album.columns>=1)
		UI.displayColumns(selectedFiles);
	else
		UI.renderTemplate("fileboxTemplate", UI.pageDiv, selectedFiles, append);

	if(!isEmpty(album.otherFiles))
		UI.renderTemplate("downloadFileTemplate", UI.downloadFileDiv, album.otherFiles);
	else
	{
		UI.downloadFileDiv.remove();
		$("#cb_downloadFileList").remove();
	}
	UI.contentFooter.appendTo(UI.mediaFileDiv);
	$("img#loadMoreIcon").toggle(album.nbPages>1);

	UI.setContentHeight();
	UI.setContentWidth();

	if(transition)
		UI.transition.execute();

//after rendering
	UI.renderedPages[album.pageNum] = true; // prevent rendering it again

	UI.transition.setType("crossFade"); // reset for next time.

	UI.setupFileEvents();
		
	return selectedFiles;
};

//if mediafile: setup events after refreshing single mediaFile
//else: setup events for current page
UI.setupFileEvents = function(mediaFile)
{
	UI.displayEditEvent();
	var div = mediaFile ? mediaFile.getDiv() : UI.pageDiv;

	var imgThumbs = div.find("img.thumbnail");
	imgThumbs.load(UI.imageOnLoad);
	imgThumbs.error(UI.imageOnError);

	imgThumbs = div.find("img.playLink, img.thumbnail");
	imgThumbs.bindReset("click", function()
	{
		MediaFile.play($(this));
	});
};

//call when initial display, and when tag list changes: new tag word created or removed.
UI.displayTags = function()
{
	if(isEmpty(album.tags)) return;
	UI.renderTemplate("tagSelectTemplate", UI.tagListDiv, Object.keys(album.tags));
	$("input.tagOption").bindReset("click", UI.search);		
	UI.styleCheckboxes("", "tagOption", "tagLabel");
}

//use mediafile.width and height
//for videos: get image size from thumbnail
//TODO if tn does not exist: create it, return image info / dimensions in json

/*for each column: height = sum(ratio) * width + nbimages * (margin+border)
column sum ratio
ratio for dirs? fixed size ?
*/

UI.displayColumns = function(selectedFiles)
{
	UI.pageDiv=$("<div class='page' id='mediaFilePage_{0}'/>".format(album.pageNum)).appendTo(UI.mediaFileDiv);
	if(isMissing(UI.dMargin))
		UI.dMargin = pxToInt($.getStyle(".margin", "margin-top")) + pxToInt($.getStyle(".margin", "margin-bottom"));
	if(isMissing(UI.dBorder))
		UI.dBorder = pxToInt($.getStyle(".photoBorder", "border-top-width")) + pxToInt($.getStyle(".photoBorder", "border-bottom-width"));

	var columnFiles=selectedFiles.divideInto(album.columns, album.transpose);

	UI.columnDimensions = UI.getColumnDimensions(columnFiles);

	for(var i=0; i<columnFiles.length; i++)
	{
		var columnDiv=$("<div class='{0}' id='mediaFileColumn_{2}_{1}' page='{2}'/>".format(UI.columnClasses(), i, album.pageNum));
		UI.pageDiv.append(columnDiv);
		UI.renderTemplate("fileboxTemplate", columnDiv, columnFiles[i]);
	}
	UI.setColumnWidths();

//	UI.setStatus(columnDimensions);
//	UI.addStatus(columnDimensions.average);
//	UI.addStatus("aw:{0} ah:{1} bw:{2}".format(avgWidth, avgHeight, bodyWidth));
}

UI.defaultRatio = 1.5;

UI.getColumnDimensions = function(columnFiles)
{
	var dimensions = [];
	var avg={ fixed: 0, columnRatio: 0, rowRatio: 0, width: 0 };

	for(var i=0; i<columnFiles.length; i++)
	{
		var dim={ fixed: 0, columnRatio: 0, rowRatio: 0 };
		for(var j=0; j<columnFiles[i].length; j++)
		{
			var mf=columnFiles[i][j];
			if(album.margin) dim.fixed += UI.dMargin;
			if(album.border && !mf.isTransparent()) dim.fixed += UI.dBorder;
			if(!mf.ratio) mf.ratio=UI.defaultRatio;

			dim.columnRatio += 1 / mf.ratio;
			dim.rowRatio += mf.ratio;
		}
		dimensions.push(dim);
		avg.fixed += dim.fixed;
		avg.columnRatio += dim.columnRatio;
		avg.rowRatio += dim.rowRatio;
	}

	if(dimensions.length>1)
	{	avg.fixed /= dimensions.length;
		avg.columnRatio /= dimensions.length;
		avg.rowRatio /= dimensions.length;
	}
	dimensions.average = avg;
	return dimensions;
};

UI.setColumnWidths = function()
{
	var columns = UI.pageDiv ? UI.pageDiv.children("div.column") : [];
	if(isEmpty(columns)) return;

	var bodyWidth=UI.body.width();
	var avgWidth = bodyWidth * .9 / UI.columnDimensions.length;
	if(album.margin) avgWidth -= UI.dMargin;
	if(album.border) avgWidth -= UI.dBorder;
	var avgHeight = UI.columnDimensions.average.fixed + avgWidth * UI.columnDimensions.average.columnRatio;

	if(album.fit=="height") // && avgHeight > $(window).height())
		avgHeight = UI.getContentHeight(UI.fileContainer);

	var totalWidth=0;
	for(var i=0; i < UI.columnDimensions.length; i++)
	{
		UI.columnDimensions[i].width = (avgHeight - UI.columnDimensions[i].fixed) / UI.columnDimensions[i].columnRatio;
		totalWidth += UI.columnDimensions[i].width;
	}
	var maxWidth=avgWidth * columns.length;
	var i=0;
	columns.each(function()
	{
		var dim = UI.columnDimensions[i++];
		if(totalWidth >= maxWidth)	dim.width = dim.width * maxWidth / totalWidth;
		dim.width = album.percent ? Math.roundDigits(dim.width * 100 / bodyWidth, 2) + "%" : Math.round(dim.width);
		$(this).width(dim.width);
	});

	$(".file.dir").each(function(){UI.setDivRatio(this); })
};


UI.setDivRatio = function(div, ratio)
{
	ratio = valueOrDefault(ratio, UI.defaultRatio);
	div=$(div);
	var w=div.width();
	div.height(w / ratio);
};

UI.appendNextPage = function()
{
	var next = album.getNextPageNumber();
   	//stop when all pages already rendered
   	// UI.renderedPages: bool[]. reset when !append
	if(!UI.renderedPages[next]) 
	{
		album.nextPage();
	   	UI.displaySelectedFiles(null,"append");
		next = album.getNextPageNumber();
	}

   	var more = !UI.renderedPages[next];
//   	$("img#loadMoreIcon").before(" "+next + ":" + more);
	$("img#loadMoreIcon").toggle(more);
};

UI.imageOnError = function()
{
	var img=$(this);
	var filebox=img.parents("div.file");
	var mediaFile = album.getByAttribute(filebox);
	mediaFile.tnIndex = img.attr("tn") || album.tnIndex;
	mediaFile.setTnExists(false, mediaFile.tnIndex);

	img.removeClass("loading");
	img.addClass("error");
	var src= img.attr("src");
	var imageLink = $.makeElement("a", {href: src.appendQueryString({debug: true}), target: "image"}).html(src);
	UI.addStatus(imageLink.outerHtml());
	img.attr("src","icons/delete128.png").show();
	var caption=filebox.children(".caption, .captionBelow");	
	caption.show();
	filebox.show();
};
	
UI.imageOnLoad = function()
{
	var img=$(this);
	var filebox=img.parents("div.file");
	var mediaFile = album.getByAttribute(filebox);
	mediaFile.tnIndex = img.attr("tn") || album.tnIndex;
	mediaFile.setTnExists(true, mediaFile.tnIndex);

	if(!img.is(".loading")) return;

	var caption=filebox.children(".caption, .captionBelow");	
	//caption.append(" " + img.attr("src"));

	var isTransparent = mediaFile.isTransparent();
	
	img.removeClass("loading");
	if(img.is(":hidden"))
		img.fadeIn("slow");
		
	var imageWidth = img.width();
	var bodyWidth=$("body").width();
	if(imageWidth > bodyWidth)
		img.parent().removeClass("mediumImage largeImage");

	var imageHeight = img.height();
	imageWidth = img.width();

	var captionHeight = caption.height();

	if(mediaFile.isDir() && album.columns > 1)
		UI.setDivRatio(filebox);

	//small image: caption below image
	if(!mediaFile.isDir() && album.columns <= 1)
	{
		if(imageHeight < 2 * captionHeight) // && album.size<=0)
		{
			caption.removeClass("caption");
			caption.addClass("captionBelow");
			UI.captionImgStyle(img, filebox, caption, isTransparent, "shadow");
			UI.captionImgStyle(img, filebox, null, isTransparent, "border", "photoBorder");
		}
	}

	if(caption.is(":hidden"))
		caption.fadeIn("slow");

	if(!mediaFile.isDir())
		UI.selectThumbnailSize(img, mediaFile, caption);
};

//option: shadow or border
UI.captionImgStyle = function(img, filebox, caption, isTransparent, option, cssClass)
{
	if(!album[option]) return;
	cssClass = valueOrDefault(cssClass,option);
	filebox.removeClass(cssClass);
	if(caption) caption.addClass(cssClass);
	if(!isTransparent)
		img.addClass(cssClass);
};

//replace thumbnails with higher quality if stretched
UI.selectThumbnailSize = function(img, mediaFile, caption)
{
	var imageHeight = img.height();
	var imageWidth = img.width();
	var maxStretch = 1.5; //in config
	var imageSize = Math.max(imageWidth,imageHeight) / maxStretch;
	if(!config || !config.thumbnails || !config.thumbnails.sizes) return;
	//find right size: take last tn smaller than img or first tn larger than img

	var src=img.attr("src");
	var tnIndex=MediaFile.selectThumbnailSize(imageSize);
	var src2 = mediaFile.getThumbnailUrl(tnIndex, true);
	if(src2 == src || tnIndex == mediaFile.tnIndex) return;
	mediaFile.tnIndex=tnIndex;
	img.attr("src", src2);
	img.attr("tn", tnIndex);
	if(!caption) return;
//	caption.append(" " + imageSize);
//	caption.append(" " + src2);
	caption.removeClass("small");
};

UI.getHeights = function(jq)
{
	var heights=[];

	jq.each(function()
	{
		var col=$(this);
		//calculate total border and margin height of images
		var h  = col.outerHeight(true);
		//var nbImages=col.children().length;
		//h -= nbImages * 30;
		heights.push(h);
	});
	//UI.setStatus(heights);

	return heights;
};

//set page from number or current link text
UI.selectPage = function(num)
{	
	UI.transition.setType("slideHorizontal");
	UI.transition.increment= num > album.pageNum ? 1 : -1;
	album.setPageNumber(num);
	return UI.displaySelectedFiles(true);
};

UI.selectNextPage = function(num)
{	
	if(album.nbPages<=1) return;
	album.nextPage(num);
	UI.transition.setType("slideHorizontal");
	UI.transition.increment=  num || 1;
	return UI.displaySelectedFiles(true);
};

UI.selectPreviousPage = function(num)
{	
	if(album.nbPages<=1) return;
	album.previousPage(num);
	UI.transition.setType("slideHorizontal");
	UI.transition.increment = - (num || 1);
	return UI.displaySelectedFiles(true);
};

UI.selectCountPerPage = function()
{
	countPerPage=parseInt($("#dd_page").val());
	album.setCountPerPage(countPerPage);
};

UI.displayPageLinks = function()
{
	album.nbPages = 1;
	if(album.countPerPage > 0)
		album.nbPages = parseInt((album.activeFileList().length + album.countPerPage - 1) / album.countPerPage);

	UI.pagers.html("");
	$("#pagesBottom").toggle(album.fit == "width" || album.columns<=1);
	if(album.nbPages<=1)
		return album.nbPages;
//add first,last, previous, next icons
	UI.pagers.html("Page: "); 
	if(album.pageNum>1)
		UI.pagers.append("<img class='icontr' src='icons/arrow-first.png' title='first page' onclick='UI.selectPage(1)'/>");

	for(var i=1;i<=album.nbPages;i++)
	{	
		if(i==album.pageNum+1)
			UI.pagers.append("<img class='icontr' src='icons/arrow-forward.png' title='next page' onclick='UI.selectNextPage()'/>");		
		var aClass = (i!=album.pageNum) ? "small icon" : "";
		UI.pagers.append("<a class='bold {1}'>{0}</a>".format(i, aClass));
		if(i==album.pageNum-1)
			UI.pagers.append("<img class='icon icontr' src='icons/arrow-back.png' title='previous page' onclick='UI.selectNextPage(-1)'/>");		
	}
	if(album.pageNum<album.nbPages)
		UI.pagers.append("<img class='icon icontr' src='icons/arrow-last.png' title='last page' onclick='UI.selectPage({0})'/>".format(album.nbPages));		
	
	$("a.small", UI.pagers).click(function()
	{ 
		num = parseInt($(this).text());
		UI.selectPage(num);
	});
	return album.nbPages;
};

//perform search
UI.clearSearch = function()
{
	$("#searchOptions input:checkbox").toggleChecked(false);
	$("#searchOptions input:text").val("");
	$("#searchOptions select").val("");
}

UI.search = function()
{
	//make search object from UI, search existing album.mediaFiles
	var search = UI.getSearchOptions();
	Album.searchFiles(album.mediaFiles, search);
	//or make new Album AJAX request
	UI.setStatus("search: {0} / results:{1}.".format(Object.toText(search, " "), album.searchResults.length));
	album.setPageNumber(1);
	return UI.displaySelectedFiles();
};

//index display methods
UI.getSearchOptions = function()
{
	obj={};
	obj.name=$("#search_name").val();
//	obj.depth=$("#dd_search_depth").val();
	$("input.tOption").each(function()
	{
		if(!$(this).is(":checked")) return;
		if(!obj["type"]) obj.type=[];
		obj.type.push(this.id.substringAfter("cb_search_type_")); 
	});
	if(obj["type"] && obj.type.length==1)
		obj.type=obj.type[0];

//add tag checkboxes
//if several: AND / OR : All/any ?
	$("input.tagOption").each(function()
	{
		if(!$(this).is(":checked")) return;
		if(!obj["tags"]) obj.tags=[];
		obj.tags.push(this.id.substringAfter("cb_tag_")); 
	});

	return obj;
};

//UI interaction

UI.toggleLayoutOption = function()
{
	var id = this.id.substringAfter("_");
	$('#'+id).toggleEffect($(this).isChecked());
};

//get main elements on the page
UI.setupElements = function()
{
	UI.body=$("body");
	UI.contentFooter=$("#contentFooter");
	UI.fileContainer = $("div#files");
	UI.downloadFileDiv = $("div#downloadFileList");
	UI.tagListDiv = $("div#tagList");
	UI.pagers = $(".pager");
	UI.progressBar = new ProgressBar({displayMax: true, displayValue: "percent"});

//edit div elements
	UI.editDiv=$("div#editDiv");
	UI.rotateIcons 		= $("img.rotateIcon", UI.editDiv);
	UI.editUploadIcons 	= $("#uploadIcons", UI.editDiv);
	UI.editAdminIcons 	= $("#adminIcons", UI.editDiv);
	UI.editFieldDiv 	= $("#editFieldDiv", UI.editDiv);
	UI.editFieldLabel 	= $("#editFieldLabel", UI.editDiv);
	UI.editField 		= $("#tb_editField", UI.editDiv);
	UI.editChoicesList	= $("#choicesList", UI.editDiv);
	UI.editOKButton		= $("#btn_OK", UI.editDiv);
	UI.editCancelButton = $("#btn_Cancel", UI.editDiv);
	
	UI.setupTemplates();
};

UI.setupEvents = function()
{
	$(".sOption").change(UI.sortFiles);	
	$("#dd_page").change(UI.selectCountPerPage);
	$(".dOption").change(function() { UI.displaySelectedFiles(true); } );
	$(".lOption").change(UI.toggleLayoutOption);

	$("div#searchOptions input:checkbox, div#searchOptions select").change(UI.search);
	$("div#searchOptions input:text").change(UI.search);
//	$("div#searchOptions #searchIcon").click(UI.search);
	$("div#searchOptions #clearSearchIcon").click(UI.clearSearch);

	$("img#downloadAllIcon").click(UI.downloadMultipleFiles);
	$("img#uploadAllIcon").click(UI.uploadSelectedFiles);

	//UI.editOKButton.click(UI.okInput);
	//UI.editCancelButton.click(UI.resetInput);


	if(window.UI && UI.setupKeyboard && config.keyboard)
		UI.setupKeyboard();

	$(window).scroll(function()
	{
		if($(window).scrollTop() + $(window).height() < $(document).height() - 1) return;
		UI.appendNextPage();
	});

	$("#playMusicIcon").toggle(album.musicFiles);	
	if(config.MediaPlayer.audio.enabled)
	{
		new MediaPlayer("audio");
		MediaPlayer.audio.loadMusicPlaylist(album.musicFiles);
	}

	if(config.MediaPlayer.video.enabled)
		new MediaPlayer("video");
};
