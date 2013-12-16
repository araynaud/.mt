window.UI = window.UI || {};

UI.downloadScript= "download.php";

UI.downloadMultipleFiles = function()
{
	UI.downloadFileList = album.selectFiles(true, "selected");
	if(isEmpty(UI.downloadFileList)) 
		UI.downloadFileList = album.mediaFiles;
	UI.fileIndex = 0;
	UI.downloadFile();
	UI.interval = setInterval(UI.downloadFile, config.downloads.interval);
}

UI.downloadFile = function()
{
	if(UI.fileIndex >= UI.downloadFileList.length)
	{
		clearInterval(UI.interval);
		return;
	}

	UI.getIframe();	
	var mediaFile = UI.downloadFileList[UI.fileIndex];
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
	album.selectedFiles = album.selectFiles(true, "selected");
	if(isEmpty(album.selectedFiles)) 
		album.selectedFiles = album.mediaFiles;

	UI.progressBar.displayFunction = null;
	UI.progressBar.setMax(album.selectedFiles.length);
	UI.progressBar.reset();
	UI.progressBar.show();

	var callbacks = {success: MediaFile.imageSuccess, error: MediaFile.imageError, next: UI.uploadNextFile};
	var params = {target: album.path };
	UI.multipleAjaxAsync(".upload/curlpostfile.php", params, callbacks);
};

UI.doSelectedFiles = function(script, params)
{
	album.selectedFiles = album.selectFiles(true, "selected");
	if(isEmpty(album.selectedFiles)) 
		album.selectedFiles = album.mediaFiles;
	UI.multipleAjaxAsync(script, params);
};

UI.multipleAjaxSync = function(script, params)
{
	if(isEmpty(album.selectedFiles)) return false;	
	var totalSize = album.selectedFiles.sum(MediaFile.getFileSize);
	UI.setStatus("{0}, {1}".format(plural(album.selectedFiles.length,"file"), formatSize(totalSize)));
	UI.progressBar.displayFunction = formatSize;
	UI.progressBar.setMax(totalSize);
	UI.progressBar.reset();
	UI.progressBar.show();

	//simple loop, call script 1 after the other
	for(var k=0; k < album.selectedFiles.length; k++)
	{	
		var startTime = new Date();
		var response = album.selectedFiles[k].scriptAjax(script, params, true);
		var endTime = new Date();
		var responseTime = ProgressBar.formatSeconds(endTime - startTime);
//		UI.addStatus("{0}/{1} {2} {3}:".format(k, album.selectedFiles.length, responseTime, album.selectedFiles[k].name));
//		UI.addStatus(response);
	}
	UI.addStatus("Finished.");
}

UI.multipleAjaxAsync = function(script, params, callbacks)
{
	if(isEmpty(album.selectedFiles)) return false;	
	if(!callbacks) callbacks = { next: UI.doNextFile };

	var totalSize = album.selectedFiles.sum(MediaFile.getFileSize);
	UI.setStatus("{0}, {1}".format(plural(album.selectedFiles.length,"file"), formatSize(totalSize)));
//	UI.progressBar.setMax(totalSize);
	UI.progressBar.setMax(album.selectedFiles.length);
	UI.progressBar.reset();
	UI.progressBar.show();

	//async: ajax call for each file. when response, call for next.
	UI.fileIndex=0;
	chunkIndex=1;
	//upload file
	album.selectedFiles[UI.fileIndex].scriptAjax(script, params, true, callbacks);
};

UI.uploadNextFile = function(response, script, params, callbacks)
{
//	UI.addStatus("{0}/{1} {2}:".format(UI.fileIndex, album.selectedFiles.length, album.selectedFiles[UI.fileIndex].name));
//	UI.addStatus(response);
	//same file, next chunk 	//response.file.filesize;
	
	UI.progressBar.setProgress(UI.fileIndex);
	if(response.nbChunks>1 && response.chunk < response.nbChunks)
	{
		album.selectedFiles[UI.fileIndex].nbChunks = response.nbChunks;
		params.chunk=response.chunk;
		params.nbChunks=response.nbChunks;
		UI.progressBar.addProgress(params.chunk / params.nbChunks);
		album.selectedFiles[UI.fileIndex].scriptAjax(script, params, true, callbacks);
		return;
	}

	++UI.fileIndex;
	UI.progressBar.addProgress(1);
	//finished
	if(UI.fileIndex == album.selectedFiles.length)
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
	album.selectedFiles[UI.fileIndex].scriptAjax(script, params, true, callbacks);
};


UI.doNextFile = function(response, script, params, callbacks)
{
	UI.progressBar.addProgress(1); //response.file.filesize);
	if(++UI.fileIndex < album.selectedFiles.length)
	{
		params.tn = album.selectedFiles[UI.fileIndex].resizeBeforeUpload();
		album.selectedFiles[UI.fileIndex].scriptAjax(script, params, true, callbacks);
	}
	else
	{
		var totalTime = UI.progressBar.totalTime(true);
		UI.addStatus("Finished in {0}.".format(totalTime));
		UI.progressBar.toggle(false);
	}
};
