//JSRENDER TEMPLATE FUNCTIONS
UI.linkTemplate = function(templateId, targetId, data)
{
	$.templates(templateId, "#" + templateId);
	if(data && targetId)
		$.link[templateId](data, "#" + targetId);
};

UI.setupTemplates = function()
{
	UI.linkTemplate("tnTemplate");
	UI.linkTemplate("extLinkTemplate");
	UI.linkTemplate("versionLinkTemplate");
	UI.linkTemplate("tagTemplate");
	UI.linkTemplate("descriptionTagTemplate");
//	$.views.activeViews = false;

if($.views)
	$.views.helpers({
		getVar: getVar,
		config: Album.getConfig,
		albumVar: Album.get,
		fileIndex: Album.getFileIndex,
		combine: String.combine,
		thumbnailUrl: MediaFile.getThumbnailUrl,
		filePath: MediaFile.getFilePath,
		fileUrl: MediaFile.getFileUrl,
		scriptUrl: MediaFile.getScriptUrl,
		isVideoStream: MediaFile.isVideoStream,
		makeTitle: String.makeTitle,
		getGroup: UI.getGroup,
		setGroup: UI.setGroup,
		dateFormat: dateFormat,
		dateRange: Date.formatDateRange,
		formatTime: formatTime,
		formatSize: formatSize,
		divClasses: UI.divClasses,
		divStyles: UI.divStyles,
		imgClasses: UI.imgClasses,
		dirImgClasses: UI.dirImgClasses,
		captionClasses: UI.captionClasses,
		subtitleClasses: UI.subtitleClasses
	});
};

UI.renderTemplate = function(templateId, targetId, data, funct, params)
{
	var target = $(targetId);
	if(!target.length && isString(targetId))
		target = $("#"+targetId);
	if(!target.length)		return;
	funct= valueOrDefault(funct,"html");
	var html="";
	if(data)
		html = $("#"+templateId).render(data,params);
	if(isFunction(target[funct]))
		target[funct](html);
	else
		target.html(html);	
};

UI.getGroupTitle = function(mediaFile)
{
	if(isMissing(mediaFile)) return false;
	var value =  mediaFile[album.sort];
	if(isMissing(value)) return false;
	if(isNumber(value)) return value;
	if(album.sort=="takenDate")
	{
		if(!dateFormat)	return value;

		if(album.reverse)
			return Date.formatTimeSince(value);

		var fmt= ["month","year"].contains(album.dateRange.unit) ? "monthYear" : "longDate";
		return dateFormat(value,fmt,true);
	}
	if(album.sort=="type")
		return value+"S";
	if(album.sort=="subdir")
		return value.makeTitle();
//	if(album.sort=="name" || album.sort=="filename" || album.sort=="title")
	return value.substring(0,1).toUpperCase();

//	return false;
};

UI.setGroup = function(mediaFile)
{
	UI.prevGroup = UI.getGroupTitle(mediaFile);
};

UI.getGroup = function(mediaFile)
{
	if(!album.group && !album.group.length) return false;
	if(album.columns>1 && !album.transpose) return false;
	
	var group=UI.getGroupTitle(mediaFile);
	if(UI.prevGroup != group)
		return group;
	return false;
};
	
// TO TEST
function getTemplateAjax(file, templateId, targetDivId)
{
//	var file = 'views/my_template_file.html';
	$.ajax({
		url: file,
		//async: false,
		dataType: 'text',
		success: function(contents) {
			$.templates(templateId, contents);
			if(targetDivId)
				$("#"+targetDivId).html( $.render.my_template()	);
		}
	});
}

var my = my || {};
my.getPath = function(name)
{
	return '../templates/_' + name + '.tmpl.html';
};

my.renderExtTemplate = function(item)
{
	var file = my.getPath( item.name );
	$.when($.get(file))
	 .done(function(tmplData) {
		 $.templates({ tmpl: tmplData });
		 $(item.selector).html($.render.tmpl(item.data));
	 });    
};
