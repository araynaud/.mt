// MediaThingy.UI.js global functions
// include templates?

// move these functions to UI

window.UI = window.UI || {};

UI.modes=
{
	index: { scrollable: true, show: ["#titleContainer", "#indexContainer", "#optionsContainer", ".fixedRight", ".fixedLeft"], 
		onShow: function() 
		{
			var alt = UI.transition.getNextSlide();
			alt.html("");
			alt.hide();
			UI.displaySelectedFiles();
		},
		onHide: function()
		{
			if(UI.rotatePages)	UI.rotatePages(false);
		}
	},
	article: { scrollable: true, show: ["#titleContainer", "#articleContainer", "#optionsContainer"],
		onShow: function() 
		{
			UI.setDisplayOptions({ columns: 0 });
		}
	 },
	video: { scrollable: true, show: ["#titleContainer", "#videoContainer"],
		onHide: function() { MediaPlayer.video.pause(); }
	 },
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
UI.sizes = {};
UI.sizes.tiny = {"divClasses": "tinyImage", tnIndex: 0, fixedHeight: true};
UI.sizes.small  = {"divClasses": "smallImage", tnIndex: 0};
UI.sizes.medium  = {"divClasses": "mediumImage", tnIndex: 1, fixedHeight: true};
UI.sizes.large  = {"divClasses": "largeImage", tnIndex: 1, fixedHeight: true};
UI.sizes.full  = {"divClasses": "fullImage", tnIndex: 1};

UI.mode="index";
UI.prevMode="index";
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

UI.setPrevMode = function()
{
	return UI.setMode(UI.prevMode);
}

UI.setMode = function(mode)
{
	mode = valueOrDefault(mode, "index");
	if(UI.mode===mode) return;

	UI.prevMode = UI.mode;
	UI.currentMode = UI.modes[UI.mode];
	var newMode = UI.modes[mode];

	UI.changeMode(UI.currentMode, newMode);
	$("body").toggleClass("noscroll", !newMode.scrollable);

	UI.currentMode = newMode;
	UI.mode = mode;
	return UI.mode;
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

//display options

//get options from UI
UI.getDisplayOptions = function(obj)
{
	if(!obj) obj={};
	$("select.dOption, select.sOption").each(function()
	{
		obj[this.id.substringAfter("dd_")]=$(this).val();
//		UI.addStatus(this.id + ": " + this.value + " / " + $(this).val());
	});
	
	$("input.dOption, input.sOption").each(function()
	{
		obj[this.id.substringAfter("cb_")]=$(this).is(":checked");
	});
	obj.tnIndex = 0;
	if(obj.isMultiColumn && !obj.isMultiColumn()) 
	{
		obj.tnIndex = obj.maxTnIndex;
		var uiSize = UI.sizes[obj.size];
		if(uiSize)
			obj.tnIndex = Math.min(obj.maxTnIndex, uiSize.tnIndex);
	}
	return obj;
};

UI.setDisplayOptions = function(obj)
{
	if(isMissing(obj) || !isObject(obj)) return;
	UI.noRefresh=true;
	for(key in obj)
		UI.setOption(key, obj[key]);
	UI.noRefresh=false;
};

UI.setOption = function(option, value, noEvent)
{
//	UI.setStatus("UI.setOption " + option + " = " + value);
	var dd = $("#dd_" + option);
	if(!isEmpty(dd))
		return dd.selectOption(value, noEvent);
	return UI.toggleOption(option, value, noEvent);
}

UI.toggleOption = function(option, state, noEvent)
{
	album[option] = !isMissing(state) ? state : !album[option];
	if(UI.mode=="slideshow")
		UI.slideshow.toggleOption();
	$("#cb_" + option).toggleChecked(state, noEvent);
}

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

UI.makeBackgroundGradients = function()
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

UI.setStatus = function(text)
{
	text=text || "";
	if(isObject(text))
		text=JSON.stringify(text);
	UI.statusBar.html(text);
};

UI.addStatus = function(text)
{
	if(!text) return;
	if(isObject(text))
		text=JSON.stringify(text);
	if(UI.statusBar.html())
		UI.statusBar.append("\n");
	UI.statusBar.append(text);
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
	if($.browser)
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

UI.resetImageSize = function(img)
{
	if(!img)return;
	img=$(img);
	img.width("");
	img.height("");
};

//return rotate, shadow, border, scale
UI.divStyles = function(angle)
{
	angle = valueOrDefault(angle, 0);
	var style="";
	if(album.rotate) 
	{
		if(!angle)	angle = $.randomBetween(-10, 10);
		style+=" transform: rotate({0}deg); -webkit-transform: rotate({0}deg); -ms-transform: rotate({0}deg);".format(angle);
	}
	return style;
};

UI.divClasses = function(mediaFile)
{
	var classes = mediaFile.type ? mediaFile.type.toLowerCase() : "";
//	if(UI.mode=="index")
		classes+=" floater";

	if(mediaFile.selected)
		classes+=" selected";

	if(album.isMultiColumn())
		classes += " stretchW";
	else
	{
		classes += " shrinkW90";
		classes += " " + UI.sizes[album.size].divClasses;
	}

	if(album.margin)
	{
	 	classes += " margin";
		if(album.isMultiColumn()) classes+="V";
	}

	if(album.cropRatio)
		classes+=" square";

	if(album.border && !mediaFile.isTransparent())
		classes+=" photoBorder";

	//not for transparent images
	if(album.shadow && !mediaFile.isTransparent()) 
		classes+=" shadow";

	return classes;
};

UI.imgClasses = function(mediaFile)
{
	var classes="loading thumbnail";
	if(mediaFile.isVideoStream() && config.MediaPlayer.slide.enabled)
		classes += " playLink";

	if(album.fadeIn && !album.cropRatio) classes+=" hidden";
 	if(album.isMultiColumn())
		classes+=" stretchW";
	else
		classes+=" shrinkW";

	return classes;
};

UI.dirImgClasses = function()
{
	var classes="loading";
	classes+=" stretchW";
	if(album.fadeIn) classes+=" hidden";
	return classes;
};

UI.captionClasses = function(mediaFile)
{
	var classes= "caption";
	if(album.fadeIn && !album.cropRatio && !mediaFile.isDir())	 classes+=" hidden";
	if(album.tnIndex == 0 || album.isMultiColumn())
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

	if(album.isMultiColumn() && album.border) classes+=" paddingH";
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

UI.displayFileCounts = function (fileList,divId,times)
{
	$(divId).html("");
	if(times)
		$(divId).append("{0}s / {1}s / {2}s".format(Math.roundDigits(album.buildTime,2), Math.roundDigits(album.requestTime/1000,2), Math.roundDigits(album.albumTime/1000,2)));

	var counts=Object.toArray(fileList.countBy("type"));
	UI.renderTemplate("typeSelectTemplate", divId, counts);

	$("#slideshowIcon").toggle(album.hasFilesOfType("IMAGE"));
	$("#playIcon").toggle(album.hasFilesOfType("VIDEO"));
};

UI.displayPageFileCounts = function (fileList)
{
	var albumCounts = album.countByType;
	var pageCounts = fileList.countBy("type");
	for(var k in albumCounts)
	{
		var val = pageCounts[k] || 0;
		if(val != albumCounts[k]) 
			val += " / " + albumCounts[k];
		$("#type_count_"+k).html(val);
	}
}

UI.styleCheckboxes = function(container, cssClass, labelClass)
{	
	var selector = "input:checkbox";
	if(container)
		selector= container + " " + selector;
	if(cssClass)
		selector += "." + cssClass;

	labelClass = valueOrDefault(labelClass,"checkboxLabel");

	$(selector).each(function()
	{
		var cb=$(this);
		var label = $("label[for={0}]".format(this.id));
		if(isEmpty(label))
		{
			label = $("<label id='label_{0}' for='{0}'/>".format(this.id));
			var icon = cb.attr("icon"); //take attr from CB
			var text = cb.attr("label");
			if(icon)
				text = $.makeElement("img", {src: icon, alt: text});
			label.append(text);
		}
		label.attr("title", cb.attr("title")); //tooltip
		label.addClass(labelClass);
		cb.after(label);
		if(!$.isOldIE(8) && !UI.clientIs("ipad")) 
			cb.hide();
	});
};
