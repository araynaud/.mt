//----------------- FB functions
var fbRoot, fbContainer, fbLike;
var fbHeight=0;
var rowHeight=0;

function initFacebookComments()
{
	if(!FB) return;
	FB.init({appId: '159679107375436', status: true, cookie: true, xfbml: true});
	fbContainer=$("#fbContainer");
	fbRoot=$("#fb-root");
	fbLike=$("#fbLike");
	setFacebookCommentsHeight();
}

function toggleFacebookComments()
{
	if(!fbRoot) return;
	fbContainer.toggle("fast");
	setFacebookCommentsHeight();
}

function setFacebookCommentsHeight()
{	
	var f=$(".file");
	rowHeight=f.outerHeight();
	fbLikeHeight=fbLike.outerHeight();
	var rowMarginHeight=pxToInt(f.css("margin-top")) + pxToInt(f.css("margin-bottom"));
	rowHeight+=rowMarginHeight;

	fbHeight = fbLikeHeight + fbRoot.children().height();
	fbHeight=Math.roundMultiple(fbHeight,rowHeight)-rowMarginHeight;
	if(!isNaN(fbHeight))
		fbContainer.height(fbHeight);
}
