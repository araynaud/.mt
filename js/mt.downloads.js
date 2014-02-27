window.UI = window.UI || {};

UI.downloadScript= "download.php";

UI.downloadMultipleFiles = function()
{
	UI.selectedFileList = album.getSelection();
	UI.fileIndex = 0;
	UI.downloadFile();
	UI.interval = setInterval(UI.downloadFile, config.downloads.interval);
}

UI.downloadFile = function()
{
	if(UI.fileIndex >= UI.selectedFileList.length)
	{
		clearInterval(UI.interval);
		return;
	}

	UI.getIframe();	
	var mediaFile = UI.selectedFileList[UI.fileIndex];
	var downloadScriptUrl = UI.downloadScript ? mediaFile.getScriptUrl(UI.downloadScript) : mediaFile.getFileUrl(); //getThumbnailUrl(1);
// use image.php ?
	//use UI.downloadIframe.load() event?
	UI.downloadIframe.attr("src", downloadScriptUrl);
//	UI.addStatus("{0}:{1}".format(UI.fileIndex, downloadScriptUrl));
	UI.fileIndex++;
}

UI.getIframe = function()
{
	if(!isEmpty(UI.downloadIframe)) return UI.downloadIframe;
	UI.downloadIframe=$('iframe#downloadIframe');
	if(!isEmpty(UI.downloadIframe)) return UI.downloadIframe;
	//if it does not exist, make download iframe	
	$("body").prepend('<iframe class="hidden" src="" id="downloadIframe" height="100"/>');
	return UI.getIframe();
}

//------------- Multiple upload --------------

//call ajax script for all selected files
UI.uploadSelectedFiles = function()
{
	UI.selectedFileList = album.getSelection();

	UI.progressBar.displayFunction = null;
	UI.progressBar.setMax(UI.selectedFileList.length);
	UI.progressBar.reset();
	UI.progressBar.show();

	var callbacks = {success: MediaFile.imageSuccess, error: MediaFile.imageError, next: UI.uploadNextFile};
	var params = {target: album.path };
	UI.multipleAjaxAsync(".upload/curlPostMediaFile.php", params, callbacks);
};

UI.doSelectedFiles = function(script, params)
{
	UI.selectedFileList = album.getSelection();
	UI.multipleAjaxAsync(script, params);
};

UI.displaySelection = function(fileList)
{
	if(isEmpty(fileList))
		fileList = album.getSelection();
	if(isEmpty(fileList))
		return UI.setStatus();

	var totalSize = fileList.sum(MediaFile.getUploadFileSize);
	var str = "{0}, {1}".format(plural(fileList.length,"file"), formatSize(totalSize));
	UI.setStatus(str);
}

UI.multipleAjaxSync = function(script, params)
{
	if(isEmpty(UI.selectedFileList)) return false;	
	UI.progressBar.displayFunction = formatSize;
	UI.progressBar.setMax(totalSize);
	UI.progressBar.reset();
	UI.progressBar.show();

	//simple loop, call script 1 after the other
	for(var k=0; k < UI.selectedFileList.length; k++)
	{	
		var startTime = new Date();
		var response = UI.selectedFileList[k].scriptAjax(script, params, true);
		var endTime = new Date();
		var responseTime = ProgressBar.formatSeconds(endTime - startTime);
//		UI.addStatus("{0}/{1} {2} {3}:".format(k, UI.selectedFileList.length, responseTime, UI.selectedFileList[k].name));
//		UI.addStatus(response);
	}
	UI.addStatus("Finished.");
}

UI.multipleAjaxAsync = function(script, params, callbacks)
{
	if(isEmpty(UI.selectedFileList)) return false;	
	if(!callbacks) callbacks = { next: UI.doNextFile };

	UI.displaySelection();
	var totalSize = UI.selectedFileList.sum(MediaFile.getUploadFileSize);
	UI.progressBar.setMax(totalSize);
//	UI.progressBar.setMax(UI.selectedFileList.length);
	UI.progressBar.reset();
	UI.progressBar.show();

	//async: ajax call for each file. when response, call for next.
	UI.fileIndex=0;
	chunkIndex=1;
	//upload file
	UI.selectedFileList[UI.fileIndex].scriptAjax(script, params, true, true, callbacks);
};

UI.uploadNextFile = function(response, script, params, callbacks)
{
//	UI.addStatus("{0}/{1} {2}:".format(UI.fileIndex, UI.selectedFileList.length, UI.selectedFileList[UI.fileIndex].name));
//	UI.addStatus(response);
	//same file, next chunk 	//response.file.filesize;
	
//	UI.progressBar.setProgress(UI.fileIndex);
	if(response.nbChunks>1 && response.chunk < response.nbChunks)
	{
		UI.selectedFileList[UI.fileIndex].nbChunks = response.nbChunks;
		params.chunk=response.chunk;
		params.nbChunks=response.nbChunks;
//		UI.progressBar.addProgress(params.chunk / params.nbChunks);
		UI.progressBar.addProgress(response.file.filesize);
		UI.selectedFileList[UI.fileIndex].scriptAjax(script, params, true, true, callbacks);
		return;
	}

	var filesize=MediaFile.getFileSize(UI.selectedFileList[UI.fileIndex]);
	UI.progressBar.addProgress(filesize);
	++UI.fileIndex;
//	UI.progressBar.addProgress(response.file.filesize);
//	UI.progressBar.addProgress(1);
	//finished
	if(UI.fileIndex == UI.selectedFileList.length)
	{
		var totalTime = UI.progressBar.totalTime(true);
		UI.addStatus("Finished in {0}.".format(totalTime));
		UI.progressBar.toggle(false);
		return;
	}

	//or next file
	if(params)
	{
		delete params.nbChunks;
		delete params.chunk;
	}
	UI.selectedFileList[UI.fileIndex].scriptAjax(script, params, true, true, callbacks);
};


UI.doNextFile = function(response, script, params, callbacks)
{
	UI.progressBar.addProgress(1); //response.file.filesize);
	if(++UI.fileIndex < UI.selectedFileList.length)
	{
		params.tn = UI.selectedFileList[UI.fileIndex].resizeBeforeUpload();
		UI.selectedFileList[UI.fileIndex].scriptAjax(script, params, true, false, callbacks);
	}
	else
	{
		var totalTime = UI.progressBar.totalTime(true);
		UI.addStatus("Finished in {0}.".format(totalTime));
		UI.progressBar.toggle(false);
	}
};
