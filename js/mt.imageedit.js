var selectZone;
var img;
var dstImg;

function imageClick(e)
{
	if(imageParams.tool=="select")
	{
		changeSelectMode();
		imageSelect(e);
	}
	else
		refreshImage();
}

function changeSelectMode(mode)
{
	if(mode==undefined || mode==null)
		imageParams.selectMode=(imageParams.selectMode+1)%3;
	else
		imageParams.selectMode=mode;
	return imageParams.selectMode;
}

function getImageCoord(e)
{
//	if(!img)		img=$("img#image");
	var off=img.offset();
	var mx = e.pageX - off.left - img.borderWidth()/2;
	var my = e.pageY - off.top - img.borderHeight()/2;
	return {x:mx,y:my};
}

function imageSelect(e)
{
	var coord=getImageCoord(e);
	if(imageParams.selectMode==0)
	{
		imageParams.x = coord.x;
		imageParams.y = coord.y;
	}
	else if(imageParams.selectMode==1)
	{
		imageParams.x1 = coord.x;
		imageParams.y1 = coord.y;
	}
	else if(imageParams.selectMode==2)
	{
		imageParams.x2 = coord.x;
		imageParams.y2 = coord.y;
		displaySelectZone();
	}
	displayOption();
}

function resetSelection()
{
	imageParams.x1 = 0;
	imageParams.y1 = 0;
	imageParams.x2 = 0;
	imageParams.y2 = 0;
	if(!selectZone)		selectZone=$("#selectZone");
	selectZone.hide();
	imageParams.selectMode=0;
}

function displaySelectZone()
{
	if(!selectZone)		selectZone=$("#selectZone");
//	if(!img)		img=$("img#image");

	selectZone.show();
	var left = img.offset().left + Math.min(imageParams.x2,imageParams.x1);
	var top = img.offset().top + Math.min(imageParams.y2,imageParams.y1);
	var width = Math.abs(imageParams.x2 - imageParams.x1);
	var height = Math.abs(imageParams.y2 - imageParams.y1);
	selectZone.offset({left: left, top: top }).width(width).height(height);

	left = selectZone.offset().left - img.offset().left;
	top = selectZone.offset().top - img.offset().top;
	width = selectZone.width()
	height = selectZone.height();
	var ratio = width ? toFraction(width, height) : "";
	selectZone.html("{0},{1} {2}x{3} ({4})".format(left, top, width, height, ratio));
}

function displayOption(paramName)
{
	if(!paramName)
		$('#status').html(JSON.stringify(imageParams).replace(/"/g," "));
	else
		$('#status').html(paramName + ":" + imageParams[paramName] );
}

function refreshImage()
{
	var qs = "?" + Object.toQueryString(imageParams);
	var imageUrl = imageScript + qs;
//	if(!img)		img=$("img#image");
	dstImg.attr("src", imageUrl);
	$("#imageLink").attr("href", imageUrl);
	$("#editLink").attr("href", qs);
	$("#editLink").html(qs);
	
	resetSelection();
}

function getFieldValue()
{
	var field=$(this);
	var paramName=this.id.substr(3);	
	imageParams[paramName] = field.is(":checkbox") ? field.is(":checked") : field.val();
	displayOption(paramName);
}

//tool button select
function selectTool()
{
	var paramValue=this.id.substr(3);	
	imageParams.tool = paramValue;
	displayOption("tool");
	var field=$(this);
	if(field.is(".immediate")) 
		refreshImage();

	if(imageParams.tool=="select")
		changeSelectMode(1);
}
