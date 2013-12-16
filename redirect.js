var APP_DIR=".mp";
var DATA_ROOT="";

function trimChar(str,ch)
{
	while(str.charAt(0)==ch)
		str=str.substr(1);

	while(str.charAt(str.length-1)==ch)
		str=str.substr(0,str.length-1);
		
	return str;
}

function combine(a,b)
{
	if(!a && !b) return "";
	if(!a || !a.length)	return b;
	if(!b || !b.length)	return a;
	return a + "/" + b;
}

function redirect()
{
	var path=trimChar(window.location.pathname,"/");
	if(DATA_ROOT.length>0)
	{
		path=path.substr(DATA_ROOT.length);
		path=trimChar(path,"/");
	}

	var APP_PATH="/" + combine(DATA_ROOT,APP_DIR);

	if(path.length>0) 
		APP_PATH += "?path=" + path;
	window.location = APP_PATH;
}