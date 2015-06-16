window.UI = window.UI || {};

UI.displayUser = function(div)
{
	if(!div) div=$('#userLabel');

	var role=User.get("role");
	if(role)
		div.html(User.toString());
	else
		div.html("");

	UI.displayUserElements();
};

UI.displayUserElements = function()
{
	$(".loggedin").toggle(User.isLoggedIn());
	$(".notloggedin").toggle(!User.isLoggedIn());
	$(".upload").toggle(User.isUploader());
	$(".notupload").toggle(!User.isUploader());
	$(".admin").toggle(User.isAdmin());
	$(".notadmin").toggle(!User.isAdmin());
};

UI.displayEditEvent = function()
{
	//do that if user.upload or admin
	if(!User.getRole()) return;

	UI.rotateIcons.bindReset("click", UI.rotateImage);
	var fileboxes=$("div.file");
	if(UI.clientIs("mobile"))
	{
		var captions = fileboxes.find(".caption, captionBelow");
		captions.bindReset("click", function() { UI.displayEdit($(this).parent()); });
	}
	else
	{
		fileboxes.bindReset("mouseenter", function() { UI.displayEdit($(this)); });	
		fileboxes.bindReset("mouseleave", function() { if(UI.mode=="index") UI.displayEdit(); });
	}
};

UI.displayEdit = function(filebox)
{
	if(isEmpty(UI.editDiv)) return;

	//UI.addStatus(UI.mode + " edit: " + !!filebox);
	if(!filebox || !User.getRole())
	{
		UI.editDiv.hide().appendTo(UI.body); //to avoid losing it when refreshing index
		return;
	}

	UI.editDiv.appendTo(filebox).show();
	UI.editDiv.toggleClass("translucent", UI.mode=="index");
	UI.currentFile=album.getByAttribute(filebox) || UI.slideshow.currentFile;
	if(!UI.currentFile) return;

	var link=UI.currentFile.getShortUrl();
	UI.fileUrlLink.attr("href", link);

	UI.rotateIcons.toggle(User.isUploader() && UI.currentFile.isImage());
//	UI.editDiv.find("img.notdir").toggle(!UI.currentFile.isDir());
	$("#cb_selected").toggleChecked(UI.currentFile.selected, true);
//	UI.setStatus(UI.currentFile.id + " " + UI.currentFile.selected);

};

//--------- FILE manipulation functions
// move to templates or MediaFile ?

//perform action on file
UI.confirmChoice = function(link,message)
{
	answer = confirm(message + " ?")
	if (answer)		location=link;
	return answer;
};

UI.confirmFileAction = function(action, target, windowName)
{
	var params = {action: action, to: target };
	return UI.fileAction(params, windowName, true);
};

//edit name, description
UI.inputAction = function(params)
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	if(!params|| !mediaFile) return;

	var fieldValue = mediaFile[params.field];

	if(params.choices)
	{
		choices = params.choices;
		if(isObjectNotArray(choices))
		{
			if(isObject(fieldValue))
				choices = Object.keyDiff(choices, fieldValue);
			choices = Object.keys(choices);
		}
		//look how to pass parameters to template?
		UI.renderTemplate("tagTemplate", UI.editChoicesList, choices, null, {action: params.action, multiple: true});
		delete params.choices;
	}
	else
		UI.editChoicesList.html("");

	//show edit field
	UI.editUploadIcons.hide();
	UI.editAdminIcons.hide();
	UI.editFieldDiv.show();
	UI.editFieldLabel.html(params.field);
	UI.editParams = params;

//	UI.editField.val("");
	if(isString(fieldValue))
		$(UI.editField).val(fieldValue);
	UI.editField.focus();
};

UI.okInput = function()
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	var value = UI.editField.val();
	if(!mediaFile || !UI.editParams) return; // || !value) return;

	UI.editParams.to = value;
//	mediaFile.set(UI.editParams.field, UI.editParams.to);
	UI.fileActionAjax(UI.editParams);
	UI.resetInput();
};

UI.resetInput = function()
{
	UI.editFieldLabel.html("");
	UI.editField.val("");
	UI.editFieldDiv.hide();

	UI.displayUserElements();
};

UI.fileActionAjax = function(params)
{
	if(album.getSelection().length <=1)
		return UI.fileAction(params)

	var scriptName=".admin/action.php"; //default action page. TODO: .upload / .admin based on User
	if(params && params.script)
		scriptName = params.script;
	else if(params.multiple)
	{
		params.name=album.getSelectedFileNames();
		return UI.fileAction(params);
	}
	return UI.doSelectedFiles(scriptName, params);
}

UI.fileAction = function(params)
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	if(!mediaFile) return false;

	var answer=true;
	if(params.confirm)
		answer = confirm(params.action + " " + mediaFile.name + " ?");
	if(!answer)		return false;

	var scriptName=".admin/action.php"; //default action page
	if(params && params.script)
	{
		scriptName = params.script;
		delete params.script;
	}

	var windowName;
	if(params && params.script)
	{
		windowName = params.window;
		delete params.window;
	}

	if(isMissing(windowName))
	{
		var callbacks = {success: UI.afterAction };
		mediaFile.scriptAjax(scriptName, params, true, true, callbacks);
	}
	else
		UI.goToPage(scriptName, params, windowName);
	//call admin script with ajax	
	return true;
};

UI.collage = function()
{
// http://localhost/mt/image_collage.php
//?path=2015/January &tag=cat&maxfiles=7
// &columns=3&&margin=10& 
// &top=EUREKAbottom=you%27re%20a%20cat!
// &save=cover.jpg
	var tags = UI.getSelectedTags();
	var types = UI.getSelectedTypes();
	var files = album.getSelectedFileNames(",");
	params = { 
		margin: album.border || album.margin ? 10 : 0, 
		maxfiles: album.countPerPage,
		tranpose: album.tranpose,
		columns: album.columns,
		sort: album.sort,
		caption: album.caption,
		page: album.pageNum,
		save: "collage_" + +(new Date())
	};
	if(!isEmpty(tags))  params.tag = tags[0];
	if(!isEmpty(types)) params.type = types;
	if(!isEmpty(files)) params.files = files;

	var link = MediaFile.getScriptUrl(null, "image_collage.php", params);
	return UI.goToUrl(link, "collage");
};

UI.goToPage = function(scriptName, params, windowName)
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	if(!mediaFile) return false;
	var link=mediaFile.getScriptUrl(scriptName +".php", params);
	return UI.goToUrl(link, windowName);
};

UI.goToUrl = function(link, windowName)
{
	if(windowName)	window.open(link, windowName);
	else	window.location=link;
	return true;
};

UI.appRootUrl = function()
{
	return window.location.href.substringBefore("?");
};

UI.fbShare = function(mediaFile)
{
	if(!config || !config.fb || !config.fb.shareUrl) return false;

	if(!mediaFile)
	{
		mediaFile = UI.currentFile;
		if(UI.mode==="slideshow")  mediaFile = UI.slideshow.currentFile;
		if(UI.mode==="article")  mediaFile = UI.currentArticle;
	}
	if(!mediaFile) mediaFile = album;

	var link = config.fb.shareUrl.appendQueryString({u: mediaFile.getShortUrl()});
	return UI.goToUrl(link, 'fb');
};

UI.ajaxError = function(xhr, textStatus, errorThrown)
{ 
	if(this.setStatus)
		this.setStatus(textStatus +"\n" +errorThrown);
	else if(UI && UI.setStatus)
		UI.setStatus(textStatus +"\n" +errorThrown);
};

//actions for UI after file action ajax is done.
//pass response, use .action and .parameters
UI.afterAction = function(response, mediaFile, params)
{
	if(!mediaFile && !response.files) return false;
	if(!params || !params.action) return false;
	if(!response.result) return false;

	//handle multiple files if params.multiple && response.files
	//response.files: update mediaFiles
	if(response.files)
	{
		var mediaFiles = response.files;
		if(config.debug.ajax)
			UI.addStatus(plural(mediaFiles.length, "file"));
		delete response.files;
		for(var i=0; i < mediaFiles.length; i++)
		{
			var rmf = mediaFiles[i];
			var mf = album.getMediaFileByName(rmf.name, rmf.type);
			UI.afterAction(response, mf, params);
		}

		//refresh search results
		if(params.action=="move" || params.action=="delete") 
			UI.search();

		return;
	}

	if(params.field)
		mediaFile.set(params.field, params.to);

	switch(params.action)
	{
		case "addtag":
		case "removetag":
			mediaFile.setTag(response.parameters.tag, response.parameters.state);
			var tagListChanged = album.setTag(response.parameters.tag, mediaFile.name, response.parameters.state);
			if(tagListChanged)
				UI.displayTags();
		case "description":
			return UI.refreshMediaFile(mediaFile);
		case "background":
			return UI.displayBackground(mediaFile);
		//after move/delete : remove from album
		case "move":
		case "delete":
			return UI.removeMediaFile(mediaFile);
		default:
			return false;
	}
};

UI.removeMediaFile = function(mediaFile)
{
	if(!mediaFile) return false;
//TODO: album.removeFile() from grouped files, mediaFiles and slideshow
	album.mediaFiles.remove(mediaFile.name, "name");
	UI.slideshow.remove(mediaFile.name);
	if(UI.mode==="slideshow")
		UI.slideshow.showImage();

	mediaFile.getDiv().hide("slow", UI.displaySelectedFiles);
	return true;
};

UI.refreshMediaFile = function(mediaFile, refreshPage)
{
	//render template in this file div id
//	UI.addStatus("refreshMediaFile " + mediaFile.id);
	if(UI.mode=="slideshow")
	{
		UI.slideshow.showImage(null,"none");
		return;
	}

	UI.setGroup(mediaFile);

	UI.editDiv.hide().appendTo(UI.body);
	//to avoid losing it when refreshing index

	if(refreshPage)
		return UI.displaySelectedFiles(true);

	//refresh only 1 file template
	var fileDiv = mediaFile.getDiv();
	if(isEmpty(fileDiv)) return;

	UI.renderTemplate("fileboxTemplate", fileDiv, [mediaFile], "after");
	fileDiv.eq(0).remove();
	UI.setupFileEvents(mediaFile);
	return true;
};

UI.displayBackground = function(mediaFile, hidden)
{
	var background = { url: mediaFile.getThumbnailUrl(1, true), hidden: hidden ? " hidden" : "" };
	var imgbg=$("#divbg").backgroundImage(background.url);
	return background;
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
	var filebox=img.parents("div.file");
	var thumbnailImg = filebox.find("img.thumbnail");
	UI.currentFile.setTnExists(false);
	var tnUrl = UI.currentFile.getThumbnailUrl(album.tnIndex, true);
    var time = +(new Date());
	tnUrl =	String.appendQueryString(tnUrl, {cache: time});
	thumbnailImg.attr("src", tnUrl);
	if(config.debug.ajax)
	{
		var imageLink = $.makeElement("a", {href: tnUrl, target: "image"}).html(tnUrl);
		UI.addStatus(imageLink.outerHtml());
	}
};