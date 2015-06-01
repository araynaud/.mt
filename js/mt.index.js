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

	UI.slideshow.pics = album.selectSlideshowFiles();
	if(refreshDisplay)
		return UI.displaySelectedFiles();
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
UI.displaySelectedFiles = function(transition, append)
{
	var selectedFiles = album.selectCurrentPageFiles();
	UI.displayFiles(selectedFiles, transition, append);
};
	
//refresh method
UI.displayFiles = function(selectedFiles, transition, append)
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
	UI.displayPageFileCounts(album.activeFileList());
	
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
		UI.renderTemplate("fileboxTemplate", UI.pageDiv, selectedFiles, append, {action: 'removetag', multiple: 'true'});

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
	var div = mediaFile instanceof MediaFile ? mediaFile.getDiv() : valueOrDefault(mediaFile, UI.pageDiv); 

	var imgThumbs = div.find("img.thumbnail");
	imgThumbs.load(UI.imageOnLoad);
	imgThumbs.error(UI.imageOnError);

	imgThumbs = div.find("img.playLink, img.thumbnail");
	imgThumbs.bindReset("click", function()
	{
		MediaFile.play($(this));
	});
};


UI.loadDirThumbnails = function(mediaFiles,count)
{
	UI.selectedFileList = album.selectFiles({type:"DIR", thumbnails: null});
	if(isEmpty(UI.selectedFileList)) return;

	count = valueOrDefault(count, 6);
	var callbacks = {};
	callbacks.success = function(response, mediaFile, params)
	{
			if(!isArray(response)) return;
			mediaFile.thumbnails = response;
			mediaFile.tncolumns = mediaFile.thumbnails.divideInto(2);
			UI.refreshMediaFile(mediaFile);
	};
	var params = { data: "thumbnails", count: count, empty: true, depth: 2 };
	UI.multipleAjaxAsync("data.php", params, callbacks);
};

//call when initial display, and when tag list changes: new tag word created or removed.
UI.displayTags = function()
{
	UI.renderTemplate("articleLinkTemplate", UI.textListDiv, album.articleFiles);

	if(isEmpty(album.tags)) return;
	UI.renderTemplate("tagSelectTemplate", UI.tagListDiv, Object.keys(album.tags)); 

	$("input.tagOption, input.operator").bindReset("click", UI.search);		
	UI.styleCheckboxes("", "tagOption", "tagLabel");

	if(!album.searchFilters || isEmpty(album.searchFilters.tags)) return;

	for(var i=0; i < album.searchFilters.tags.length; i++)
	{
		var tag=album.searchFilters.tags[i];
		$("#cb_tag_" + tag).toggleChecked();
	}
};

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

   	UI._allPagesDisplayed = !more;
// 	$("img#loadMoreIcon").before(" "+next + ":" + more);
//	$("img#loadMoreIcon").toggle(more);
	return more;
};

UI.isPageVisible = function(pageNum)
{
	return UI.renderedPages[pageNum];
};

UI.allPagesVisible = function()
{
	return UI._allPagesDisplayed;
};

UI.imageOnError = function()
{
	var img=$(this);
	var filebox=img.parents("div.file");
	var mediaFile = album.getByAttribute(filebox);
	if(!mediaFile) return;

	mediaFile.tnIndex = img.attr("tn") || album.tnIndex;
	mediaFile.setTnExists(false, mediaFile.tnIndex);

	img.removeClass("loading");
	img.addClass("error");
	var src= img.attr("src");
	if(config.debug.ajax)
	{
		var imageLink = $.makeElement("a", {href: src.appendQueryString({debug: true}), target: "image"}).html(src);
		UI.addStatus(imageLink.outerHtml());
	}
	img.unbind("error"); //to avoid infinite loop
	if(mediaFile.isVideo())
		img.attr("src","icons/media-play.png").show();
	var caption=filebox.children(".caption, .captionBelow");	
	caption.show();
	filebox.show();
};
	
UI.imageOnLoad = function()
{
	var img=$(this);
	var filebox=img.parents("div.file");
	var mediaFile = album.getByAttribute(filebox);
	if(!mediaFile) return;
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
	var maxStretch = valueOrDefault(config.thumbnails.maxStretch, 1);
	var imageSize = Math.max(imageWidth,imageHeight) / maxStretch;
	if(!config || !config.thumbnails || !config.thumbnails.sizes) return;
	//find right size: take last tn smaller than img or first tn larger than img

	var src=img.attr("src");
	var tnIndex=mediaFile.selectThumbnailSize(imageSize);
	var src2 = mediaFile.getThumbnailUrl(tnIndex, true);
	if(!src2 || src2 == src || tnIndex == mediaFile.tnIndex) return;
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
	return UI.getJQueryValues(jq, "outerHeight", true);
};

UI.getJQueryValues = function(jq, method, arg)
{
	jq=$(jq);
	var values=[];
	jq.each(function()
	{
		var h = $(this)[method](arg);
		values.push(h);
	});
	return values;
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
	var nbPages = album.getNumberOfPages();

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
		var aClass = (i!=album.pageNum) ? "small spaceLeft" : "";
		UI.pagers.append("<a class='bold {1}'>{0}</a>".format(i, aClass));
		if(i==album.pageNum-1)
			UI.pagers.append("<img class='spaceLeft icontr' src='icons/arrow-back.png' title='previous page' onclick='UI.selectNextPage(-1)'/>");		
	}
	if(album.pageNum<album.nbPages)
		UI.pagers.append("<img class='spaceLeft icontr' src='icons/arrow-last.png' title='last page' onclick='UI.selectPage({0})'/>".format(album.nbPages));		
	
	$("a.small", UI.pagers).click(function()
	{ 
		num = parseInt($(this).text());
		UI.selectPage(num);
	});
	return album.nbPages;
};

// ---- perform search
UI.clearSearch = function()
{
	$("#searchOptions input:checkbox").toggleChecked(false);
	$("#searchOptions input:text").val("");
	$("#searchOptions select").val("");

	UI.search();
};

UI.search = function()
{
	//make search object from UI, search existing album.mediaFiles
	var search = UI.getSearchOptions();
	album.searchFiles(album.mediaFiles, search);
	//or make new Album AJAX request
//	UI.setStatus("search: {0} / results:{1}.".format(Object.toText(search, " "), album.searchResults.length));
	album.setPageNumber(1);

	UI.slideshow.pics =  album.selectSlideshowFiles();
	UI.setMode();
	return UI.displaySelectedFiles();
};

UI.getSelectedTags = function()
{
	var tags=[];
	$("input.tagOption:checked").each(function()
	{
		tags.push(this.id.substringAfter("cb_tag_")); 
	});

	//tag checkboxes: AND / OR : All/any ?
	if(tags.length==1)
		tags=tags[0];
	else if(tags.length>1)
		tags.matchAll = $("input#cb_all_tags").is(":checked");

	return tags;
};

UI.getSelectedTypes = function()
{
	var type=[];
	$("input.typeOption:checked").each(function()
	{
		type.push(this.id.substringAfter("cb_search_type_")); 
	});
	if(type.length==1)
		type=type[0];
	return type;
}

//index display methods
UI.getSearchOptions = function()
{
	obj={};
	obj.searchString=$("#search_name").val();
//	obj.depth=$("#dd_search_depth").val();
	var t = UI.getSelectedTypes();
	if(!isEmpty(t))
		obj.type = t;

	t = UI.getSelectedTags();
	if(isString(t) || !isEmpty(t))
		obj.tags = t;

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
	UI.userDiv=$('#userLabel');
	UI.visitImg=$('#visitImg');
	UI.downloadFileDiv = $("div#downloadFileList");
	UI.textListDiv = $("#textList");
	UI.tagListDiv = $("#tagList");
	UI.pagers = $(".pager");
	UI.progressBar = new ProgressBar({displayMax: true, displayValue: "percent"});
	UI.articleContainer = $("#articleContainer");
	UI.statusBar = $(UI.statusBar);

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
	UI.fileUrlLink      = $("a#fileUrlLink", UI.editDiv);
	UI.ajaxLoader       = $("#ajaxLoader");
	UI.setupTemplates();
};

UI.setupEvents = function()
{
	$(".sOption").change(UI.sortFiles);	
	$("#dd_page").change(UI.selectCountPerPage);
	$(".lOption").change(UI.toggleLayoutOption);
	$(".dOption").change(function()
	{
		if(UI.mode == "article") 
			UI.displayArticle();
		else if(!UI.noRefresh)
			UI.displaySelectedFiles(true);
	} );

	$("div#searchOptions input:checkbox, input.typeOption, div#searchOptions select").change(UI.search);
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
		if(UI.mode != "index") return;
		UI.appendNextPage();
	});

	$("#cb_select_all").bindReset("change", function() 
	{
		var state=$(this).isChecked();
		UI.prevSelectedFile=null;
		album.selectAll(state);
	});
	
	$("#cb_selected").bindReset("change", function() 
	{
		UI.addStatus(UI.currentFile.index + " / " + UI.currentFile.id);
		var state = $(this).isChecked();
		var pp = UI.prevSelectedFile;

		if(isMissing(UI.prevSelectedFile))
		{
			UI.currentFile.toggleSelected(state);
			UI.prevSelectedFile = UI.currentFile.index;
		}
		else // select multiple
		{
			album.selectRange(UI.prevSelectedFile, UI.currentFile.index, state);
			UI.prevSelectedFile = null;
		}
		UI.displaySelection();
		UI.addStatus("Selection: {0} - {1}".format(pp, UI.currentFile.index));
	});

	UI.setupPlayers();
};

UI.setupPlayers = function()
{
	if(!window.MediaPlayer) return;

	$("#playMusicIcon").toggle(album.musicFiles);	
	if(config.MediaPlayer.audio.enabled)
		MediaPlayer.loadPlaylist("audio", album.musicFiles);
};

UI.playAllVideos = function()
{ 
	if(!config.MediaPlayer.video.enabled)
		return;

	MediaPlayer.loadPlaylist("video", album.mediaFiles);
};

UI.scrollPages = function(page)
{	
	page = valueOrDefault(page, album.pageNum);
//is next page visible?
	var nextPage = album.getPageNumber(page+1);
	if(!UI.isPageVisible(nextPage))
		more = UI.appendNextPage();

	UI.scrollPageDiv = $("div.file[page={0}]".format(nextPage)).eq(0);
	var top = UI.scrollPageDiv.offset().top;
	var options = {duration: UI.slideshow.interval };
	$("html,body").animate({scrollTop: top}, options);
};

// page rotator
UI.rotatePages = function(state)
{	
	state = valueOrDefault(state, !UI.rotateInterval);
	if(state)
	{
		var opts = {page:3, columns:2, fit:"height", displayOptions:false, searchOptions:false, titleContainer:false, downloadFileList:false};
		if(UI.clientIs("mobile")) opts.columns=0;

		UI.setDisplayOptions(opts);
		UI.selectNextPage(0);
		if(album.nbPages<=1) return;
		UI.rotateInterval = setInterval(UI.selectNextPage, UI.slideshow.interval);
	}
	else
	{
		clearInterval(UI.rotateInterval);
		UI.rotateInterval=null;
	}

	var icon = state ? "pause64.png" : "play64.png";
	$("#rotatorIcon").attr("src", String.combine(Album.serviceUrl ,"icons", "media-" + icon));

};

