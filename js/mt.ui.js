// MediaThingy.UI.js global functions
// include templates?

// move these functions to UI

window.UI = window.UI || {};

UI.modes=
{
	index: { scrollable: true, show: ["#titleContainer", "#indexContainer", "#optionsContainer"], 
		onShow: function () 
		{
			var alt = UI.transition.getNextSlide();
			alt.html("");
			alt.hide();
		}
	},
	video: { scrollable: true, show: ["#titleContainer", "#videoContainer"] },
	slideshow: { scrollable: false, show: ["#slideshowContainer"], 
		onHide: function()
		{
			UI.slideshow.togglePlay(false);
			UI.slideshow.transition.hideElements(); 
			UI.setStatus();
		} 
	}
};

//make arrays of classes for each size
/* define in config?
-1: "tinyImage"
1: "mediumImage"
2: "largeImage"
*/
UI.sizes = [];
UI.sizes[-1] = {"divClasses": "tinyImage", tnIndex: 0, fixedHeight: true};
UI.sizes[0]  = {"divClasses": "smallImage", tnIndex: 0};
UI.sizes[1]  = {"divClasses": "mediumImage", tnIndex: 0, fixedHeight: true};
UI.sizes[2]  = {"divClasses": "largeImage", tnIndex: 1, fixedHeight: true};
UI.sizes[3]  = {"divClasses": "fullImage", tnIndex: 1};

UI.mode="index";
UI.statusBar=".status";

UI.initSizes = function()
{
	for(i in UI.sizes)
	{
		if(!UI.sizes[i] || !isObject(UI.sizes[i])) continue;	

		var divSelector = ".floater file " + UI.sizes[i].divClasses;
		var h = pxToInt($.getStyle(divSelector,"height"));
		if(h && h!="none")
			UI.sizes[i].fixedHeight=h;
		h = pxToInt($.getStyle(divSelector,"width"));
		if(h && h!="none")
			UI.sizes[i].fixedWidth=h;
		h = pxToInt($.getStyle(divSelector,"max-height"));
		if(h && h!="none")
			UI.sizes[i].shrinkH=h;
		h = pxToInt($.getStyle(divSelector,"max-width"));
		if(h && h!="none")
			UI.sizes[i].shrinkW=h;
	}
	return UI.sizes;
};
UI.initSizes();

UI.setMode = function(mode)
{
	if(!mode) mode="index";
	if(UI.mode===mode) return;
	
	UI.currentMode=UI.modes[UI.mode];
	var newMode=UI.modes[mode];

	UI.changeMode(UI.currentMode, newMode);
	$("body").toggleClass("noscroll", !newMode.scrollable);

	UI.currentMode=newMode;
	UI.mode=mode;
};

UI.setColumns = function(keyCode)
{
	var nbCols=keyCode%48;
	var ddl=$("#dd_columns");
	ddl.val(nbCols);
	var nbSelect=ddl.val();
	ddl.change();
	return nbSelect;
};

UI.toggleOption = function(option,state)
{
	if(isMissing(state))
		album[option] = !album[option];
	else 
		album[option] = state;
	UI.slideshow.showImage(null,"crossFade");
	$("#cb_" + option).toggleChecked(state);
}

UI.playAllVideos = function()
{ 
	if(!MediaPlayer.video.hasPlaylist())
		MediaPlayer.video.loadVideoPlaylist(album.mediaFiles); 
	if(MediaPlayer.video.hasPlaylist())
		UI.setMode("video");
};

//---------- switch between index / slideshow / video player

UI.changeMode = function(oldMode, newMode, fade)
{
	fade = valueOrDefault(fade, UI.transition ? UI.transition.changeMode : false);
		
	if(oldMode.onHide)
		oldMode.onHide();

	var elComm = Array.intersect(oldMode.show,newMode.show).join(",");
	elIn = Array.diff(newMode.show, oldMode.show).join(",");
	elOut = Array.diff(oldMode.show,newMode.show).join(",");

	//fade out, then fade in
	if(fade=="queue")
	{
		$(elOut).fadeOut(UI.transition.duration / 2, function()
		{
			if(newMode.onShow) 	newMode.onShow();
			$(elIn).fadeIn(UI.transition.duration / 2);
		});
		return;
	}

	//fade out and fade in at the same time
	if(fade)
	{
		$(elOut).fadeOut(UI.transition.duration);
		$(elIn).fadeIn(UI.transition.duration);
	}
	else //switch without transition
	{
		$(elOut).hide();
		$(elIn).show();
	}
	if(newMode.onShow) 	newMode.onShow();
};

UI.makeBackgroundGradients = function(step)
{
	var divbg=$('div#divbg');
	if(isEmpty(divbg)) return;

	var	bgColorStr=$('body').css("background-color");
	var defaultBgColorStr = $.getStyle(".defaultBg","background-color");

	var gradientClasses= "gradient text";
	if($.isOldIE(9))	gradientClasses+=" oldIE";

	var topG=$("<div class='topGradient {0}'/>".format(gradientClasses));
	var bottomG=$("<div class='bottomGradient {0}'/>".format(gradientClasses));
	divbg.append(bottomG);
	divbg.append(topG);

	//replace gradient colors depending on css bg color
	if(bgColorStr == defaultBgColorStr)
		return;
	var bgColor = new Color(bgColorStr);
	var defaultBgColor = new Color(defaultBgColorStr);

	if(defaultBgColor.equals(bgColor))
		return;

//replace gradient colors: make transparent
	var bgColorTrans = new Color(bgColor).setByte("A", 0);
	var defaultBgColorTrans = new Color(defaultBgColor).setByte("A", 0);

//TODO function replaceGradientColor
	var gradient = topG.css("background-image");
	if(gradient && gradient!="none")
	{
		gradient = gradient.replace(defaultBgColorStr, bgColorStr);
		gradient = gradient.replace(defaultBgColor.toRGBA(true), bgColor.toRGBA(true));
		gradient = gradient.replace(defaultBgColorTrans.toRGBA(), bgColorTrans.toRGBA());
		topG.css("background-image", gradient);
		
		gradient = bottomG.css("background-image");
		gradient = gradient.replace(defaultBgColorStr, bgColorStr);
		gradient = gradient.replace(defaultBgColor.toRGBA(true), bgColor.toRGBA(true));
		gradient = gradient.replace(defaultBgColorTrans.toRGBA(), bgColorTrans.toRGBA());
		bottomG.css("background-image", gradient);
//		bottomG.append(bottomG.css("background-image") + "\n");
	}
	else
	{
		gradient = topG.css("filter");
		gradient = gradient.replace(defaultBgColorStr, bgColorStr);
		gradient = gradient.replace(defaultBgColor.toHexARGB(), bgColor.toHexARGB());
		gradient = gradient.replace(defaultBgColorTrans.toHexARGB(), bgColorTrans.toHexARGB());
		topG.css("filter", gradient);

		var gradient = bottomG.css("filter");
		gradient = gradient.replace(defaultBgColorStr, bgColorStr);
		gradient = gradient.replace(defaultBgColor.toHexARGB(), bgColor.toHexARGB());
		gradient = gradient.replace(defaultBgColorTrans.toHexARGB(), bgColorTrans.toHexARGB());
		bottomG.css("filter", gradient);
//		bottomG.append(bottomG.css("filter") + "\n");
	}
};

UI.displayBackground = function(mediaFile, hidden)
{
	var background = { url: mediaFile.getThumbnailUrl(1), hidden: hidden ? " hidden" : "" };
	var imgbg=$("#imgbg");
	if(!imgbg.length)
	{
		UI.renderTemplate("backgroundTemplate","body", background, "prepend");
		UI.makeBackgroundGradients();
	}
	else
		imgbg.attr("src", background.url);

	return background;
};

UI.setStatus = function(text)
{
	text=text || "";
	if(isObject(text))
		text=JSON.stringify(text);
	$(UI.statusBar).html(text);
};

UI.addStatus = function(text)
{
	if(!text) return;
	if(isObject(text))
		text=JSON.stringify(text);
	$(UI.statusBar).append("\n" + text);
};

UI.displayBrowserInfo =  function()
{
	var browserInfo = {	userAgent: navigator.userAgent };

	if(document.documentMode)
	browserInfo.documentMode=document.documentMode;
	if(document.compatMode)
		browserInfo.compatMode=document.compatMode;
	browserInfo.oldIE9=$.isOldIE(9);
	browserInfo.oldIE8=$.isOldIE();
	browserInfo.oldIE6=$.isOldIE(6);
	
	Object.merge (browserInfo, $.browser, true);
	browserInfo.config = config.USER_AGENT;
	alert(Object.toText(browserInfo,"\n"));
	return browserInfo;
};

UI.clientIs = function(keyword)
{
	if(!config.USER_AGENT || !config.USER_AGENT.DEVICES)
		return false;
	return config.USER_AGENT.DEVICES.contains(keyword);
}

//--------- FILE manipulation functions
// move to templates or MediaFile ?

//perform action on file
UI.confirmChoice = function(link,message)
{
	answer = confirm(message + " ?")
	if (answer)		location=link;
	return answer;
};

UI.confirmFileAction = function(action,target) //,path,filename)
{
	//path=valueOrDefault(path, album.path);
	//if(!filename && this.currentFile)	filename=this.currentFile.filename;
	var mediaFile=this.currentFile;
	if(UI.mode==="slideshow")
		mediaFile=UI.slideshow.currentFile;

//	UI.setStatus(mediaFile.name);
	var answer = confirm(action + " " + mediaFile.name + " ?");
	if(!answer)		return false;
	if(!action)		action="move";
	//TODO use MediaFile.getScriptUrl(".admin/action.php") or pass data to ajax
	var link=".admin/action.php?";
	if(album.path)
		link += "&path=" + album.path;
	if(mediaFile.filename)
		link += "&file=" + mediaFile.filename;
	else 
		return false;
	if(action)
		link += "&action=" + action;
	if(target)
		link += "&to=" + target;
	
	//call admin script with ajax	
	UI.setStatus(link);
   	$.ajax({	
		url: link,
		dataType: "json",
		contentType: "application/json",
		cache: false,	
		success: function(response) 
		{ 
			UI.addStatus(response);
//			UI.addStatus(response.message);
			UI.afterAction(action, response, mediaFile);
		},
		error: UI.ajaxError
	});				
	return answer;
};

UI.ajaxError = function(xhr, textStatus, errorThrown)
{ 
	if(this.setStatus)
		this.setStatus(textStatus +"\n" +errorThrown);
	else if(UI && UI.setStatus)
		UI.setStatus(textStatus +"\n" +errorThrown);
};

//TODO: make separate functions 1 per action
//pass response, use .action and .parameters
UI.afterAction = function(action, response, mediaFile)
{
	if(!mediaFile) mediaFile=this.currentFile;
	if(action==="addtag" || action==="removetag")
	{		
		mediaFile.setTag(response.parameters.tag, response.parameters.state);
		return;
	}

	if(action==="background") 
	{
		UI.displayBackground(mediaFile);
		return false;
	}

	//after move/delete : remove from album
	if(action==="move" || action==="delete")
	{
		album.mediaFiles.remove(mediaFile.name, "name");
		UI.slideshow.remove(mediaFile.name);
		if(UI.mode==="slideshow")
			UI.slideshow.showImage();

		var fileDiv=$("div#"+mediaFile.id);
		fileDiv.hide("slow");
		return fileDiv.length>0;
	}
};

UI.resetImageSize = function(img)
{
	if(!img)return;
	img=$(img);
	img.width("");
	img.height("");
};

//return rotate, shadow, border, scale
UI.divStyles = function(mediaFile)
{
	var style="";
	var angle=0;
	if(album.rotate) 
	{
		angle=$.randomBetween(-10,10);
		style+=" transform: rotate({0}deg); -webkit-transform: rotate({0}deg); -ms-transform: rotate({0}deg);".format(angle);
	}
	return style;
};

UI.divClasses = function(mediaFile)
{
	var classes = mediaFile.type.toLowerCase();
	classes+=" floater";

	if(mediaFile.selected)
		classes+=" selected";

	if(album.columns>1)
		classes += " stretchW";
	else
	{
		classes += " shrinkW90";
		classes += " " + UI.sizes[album.size].divClasses;
	}

	if(album.margin)
	{
	 	classes += " margin";
		if(album.columns>1) classes+="V";
	}

	if(album.border && !mediaFile.isTransparent())
		classes+=" photoBorder";

	//not for transparent images
	if(album.shadow && !mediaFile.isTransparent()) 
		classes+=" shadow";

	return classes;
};

UI.imgClasses = function(mediaFile)
{
	var classes="loading";
	if(album.fadeIn) classes+=" hidden";
	//classes+=" shrinkW";+
 	if(album.columns>1)
		classes+=" stretchW";
	else if(UI.sizes[album.size].fixedHeight)
		classes+=" stretchH";
	else
		classes+=" shrinkW";

	return classes;
};

UI.dirImgClasses = function(mediaFile)
{
	var classes="loading";
	if(album.fadeIn) classes+=" hidden";
	if(album.columns!=-1) classes+=" stretchW";
	return classes;
};

UI.captionClasses = function(mediaFile)
{
	var classes= "caption";
	//if(album.columns<1 && mediaFile.type!="DIR") classes+= "Below";
	if(album.fadeIn && mediaFile.tnsizes) classes+=" hidden";
	if(mediaFile.type!="DIR" && (album.size<=1 || album.columns>1))
		classes+=" small";

	return classes;
};

UI.subtitleClasses = function()
{
	var classes="subtitle";
	if(!album.margin) classes+=" marginV";
	return classes;
};

UI.columnClasses = function(mediaFile)
{
	var classes="column";

	if(album.margin) classes+=" marginH";

	if(album.columns>1 && album.border) classes+=" paddingH";
	return classes;
};

UI.maxWidthPercent = function()
{
	var maxWidth = 90;
	if(album.margin || album.border)
		maxWidth -= album.columns;
	return maxWidth;
};

UI.getContentHeight = function(el)
{
	el = $(el);
	return $(window).height() - el.offset().top - el.borderMarginHeight(); 
};

UI.setContentHeight = function(el,reset)
{
	el = el || UI.fileContainer;
	var h = reset ? "" : UI.getContentHeight(el);
	el.height(h);
};

UI.getContentWidth = function(el)
{
	el = $(el);
	return UI.body.width() - UI.downloadFileDiv.outerWidth() - el.borderMarginWidth();
};

UI.setContentWidth = function(el)
{
	el = el || UI.fileContainer;
	var w = ""; 
	if(UI.downloadFileDiv.is(":visible"))
		w = UI.getContentWidth(el); 
	el.width(w);
};


UI.goToActionPage = function(scriptName, params, windowName, showConfirm)
{
	var mediaFile=UI.currentFile;
	var link=mediaFile.getScriptUrl(scriptName +".php", params);
	
	answer=true;
	if(showConfirm)	answer = confirm(mediaFile.name + " ?");
	if(answer && windowName)	window.open(link, windowName);
	else if(answer)		location=link;
	return answer;
};

UI.displayFileCounts = function (fileList,divId,clear)
{
	if(clear)	
		$(divId).html("");
	$(divId).append("{0}s / {1}s".format(Math.roundDigits(album.buildTime,2), Math.roundDigits(album.requestTime/1000,2)));
	var counts=fileList.countBy("type");
	for(var k in counts)
		$(divId).append(" " + plural(counts[k],k.toLowerCase()));
};

UI.styleCheckboxes = function(cssClass)
{	
	$("input:checkbox").each(function()
	{
		var cb=$(this);
		var label = $("label[for={0}]".format(this.id));
		if(isEmpty(label))
		{
			label = $("<label for='{0}'/>".format(this.id));
			var icon = cb.attr("icon"); //take attr from CB
			var text = cb.attr("label");
			if(icon)
				text = $.makeElement("img", {src: icon, alt: text});
			label.append(text);
		}
		label.attr("title", cb.attr("title")); //tooltip
		label.addClass("checkboxLabel");
		cb.after(label);
		if(!$.isOldIE(8) && !UI.clientIs("ipad")) 
			cb.hide();
	});
};

UI.getFileIdFromElement = function(el)
{
	if(!el.is(".file"))
		el = el.parents("div.file");
	return el.attr("id");
}

UI.getFilenameFromElement = function(img)
{
	if(!img.is(".thumbnail") || img.is(".error"))
	{
		var filebox = img.parents("div.file");
		return filebox.attr("id");
	}
	var src = img.attr("src");
	var filename=new Querystring(src).get("file");
	if(filename) return filename;
	return src.getFilename();
};

UI.rotateImage = function()
{
	var img=$(this);

	var id = img.attr("id").substringAfter("_");
	var transforms = {left:-90, right:90};
	if(!transforms[id]) return;

	var params={transform: transforms[id]};
	var result = UI.currentFile.imageScriptAjax(params);

	UI.refreshThumbnail(img);
};


UI.refreshThumbnail = function(img)
{	
	if(!img) img=this;
	img = $(img);
	UI.setStatus(img.selector());
	var filebox=img.parents("div.file");
	var thumbnailImg = filebox.find("img.thumbnail");
	UI.currentFile.setTnExists(false);
	var tnUrl = UI.currentFile.getThumbnailUrl(album.tnIndex, true);
    var time = +(new Date());
//	var sec = Math.round(time/1000 % 1000000);

	tnUrl =	String.appendQueryString(tnUrl, {cache: time});
	thumbnailImg.attr("src", tnUrl);
//	filebox.append(tnUrl);
	UI.addStatus(tnUrl);
};