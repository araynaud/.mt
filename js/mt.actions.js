UI.displayUser = function(div)
{
	if(!div) div=$('#userLabel');

	var role=User.get("role");
	if(role)
		div.html(User.toString());
	else
		div.html("");
		
	$(".upload").toggle(User.isUploader());
	$(".notupload").toggle(!User.isUploader());
	$(".admin").toggle(User.isAdmin());
	$(".notadmin").toggle(!User.isAdmin());

	UI.displayUserElements();
};

UI.displayUserElements = function()
{
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
		fileboxes.bindReset("mouseleave", function() { UI.displayEdit(); });
	}
};

UI.displayEdit = function(filebox)
{
	if(!filebox || !User.getRole())
	{
		UI.editDiv.hide().appendTo(UI.body); //to avoid losing it when refreshing index
		return;
	}

	UI.editDiv.appendTo(filebox).show();

	UI.currentFile=album.getByAttribute(filebox);
	UI.rotateIcons.toggle(UI.currentFile.isImage());
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
		UI.renderTemplate("tagTemplate", UI.editChoicesList, choices, null, {action: params.action});
		delete params.choices;
	}
	else
		UI.editChoicesList.html("");

	//show edit field
	UI.editUploadIcons.hide();
	UI.editAdminIcons.hide();
	UI.editFieldDiv.show();
	UI.editFieldLabel.html(params.field);
	UI.editParams=params;

//	UI.editField.val("");
	if(isString(fieldValue))
		$(UI.editField).val(fieldValue);
	UI.editField.focus();
};

UI.okInput = function()
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	var value = UI.editField.val();
	if(!mediaFile || !UI.editParams || !value) return;

	mediaFile.set(UI.editParams.field, value);
	UI.editParams.to = value;
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

UI.fileActionAjax = function(params, showConfirm)
{
	if(album.getSelection().length <=1)
		return UI.fileAction(params, null, showConfirm)

	var scriptName=".admin/action.php"; //default action page. TODO: .upload / .admin based on User
	if(params && params.script)
		scriptName = params.script;

	return UI.doSelectedFiles(scriptName, params);
}

UI.fileAction = function(params, windowName, showConfirm)
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	if(!mediaFile) return false;

	var answer=true;
	if(showConfirm)
		answer = confirm(params.action + " " + mediaFile.name + " ?");
	if(!answer)		return false;

	var scriptName=".admin/action"; //default action page
	if(params && params.script)
	{
		scriptName = params.script;
		delete params.script;
	}
	if(isMissing(windowName))
	{
		var callbacks = {success: UI.afterAction };
		mediaFile.scriptAjax(scriptName + ".php", params, false, true, callbacks);
	}
	else
		UI.goToPage(scriptName, params, windowName);
	//call admin script with ajax	
	return true;
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
	else	location=link;
	return true;
};

UI.appRootUrl = function()
{
	return window.location.href.substringBefore("?");
};

UI.fbShare = function()
{
	var mediaFile = (UI.mode==="slideshow") ? UI.slideshow.currentFile : UI.currentFile;
	if(!mediaFile) return false;

	var link="https://www.facebook.com/sharer/sharer.php".appendQueryString({u: mediaFile.getShortUrl()});
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
	if(!mediaFile) return false;
	if(!params || !params.action) return false;

	switch(params.action)
	{
		case "addtag":
		case "removetag":
			mediaFile.setTag(response.parameters.tag, response.parameters.state);
			album.setTag(response.parameters.tag, mediaFile.name, params.action=="addtag");
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

	UI.editDiv.hide().appendTo(UI.body);
	//to avoid losing it when refreshing index

	if(refreshPage)
		return UI.displaySelectedFiles(true);

	//refresh only 1 file template
	var fileDiv = mediaFile.getDiv();
	UI.renderTemplate("fileboxTemplate", fileDiv, mediaFile, "after");
	fileDiv.eq(0).remove();
	UI.setupFileEvents(mediaFile);
	return true;
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