<?php
//============= PATH functions


function pathToArray($str)
{
	return preg_split("_[\\\\/]_", $str);
}


//combine paths
//join str array without empty elements
//handle .. => remove array element + previous
function combine()
{
	$argArray = func_get_args();
	if(!$argArray)	return "";
	//detect path separator: / or \
	$sep="/";
	if(contains($argArray[0],"\\"))
		$sep="\\";
	
	//explode all into elements
	$strArray=array();
	foreach($argArray as $str)
	{
		if(contains($str,"://"))
			$strArray[]=$str;
		else
			$strArray = array_merge($strArray,pathToArray($str));
	}
	//remove empty elements from array
	$strArray=array_filter($strArray);
	$strArray=array_values($strArray);

	$depth=count($strArray);
	if($depth==0)	return "";
	if($depth==1)	return $strArray[0];

//to resolve parent: handle .. elements => remove this element + previous
//except in first element
// ../path/../../to/..
// ../img/RamaduelNewman/../.bg.jpg
	
	for($i=1; $i < count($strArray);)
	{
//		debug($i, $strArray[$i]);
		if($strArray[$i]==".." && $strArray[$i-1]!="..")
		{
			unset($strArray[$i]);
			unset($strArray[$i-1]);
			$strArray=array_values($strArray);
			$i--;
		}
		else
			$i++;
	}
	$strArray=array_filter($strArray);
	return implode($sep,$strArray);
}


//get current relative path
//split into array /
//test if each level exists is_dir
//if not, toggle .

function getPath($path="")
{	
	if(empty($path))
	{
		if(isset($_GET["path"]))
			$path=$_GET["path"];
		//else if(isset($_SERVER["QUERY_STRING"]))
		//	$path=$_SERVER["QUERY_STRING"];
	}
	if(empty($path))	return "";
	$path=urldecode($path);
	return $path;

	//if not, parse level by level
	$defaultRoot=pathToDataRoot();
	$pathArray = pathFragments($path); //remove empty between //
	$path="";
	$sep="";
	foreach ($pathArray as $level)
	{
		$level2=toggleDot($level);
		if(is_dir("$defaultRoot/$path/$level"))
			$path .= $sep . $level;
		else if(is_dir("$defaultRoot/$path/$level2"))
			$path .= $sep . $level2;
		else
			break;		
		$sep="/";
	}	
	return $path;
}

function getRelPath($path)
{	
	$defaultRoot=pathToDataRoot();
	if(empty($path))
		return $defaultRoot;
	return "$defaultRoot/$path";		
}

function getDiskPath($path)
{	
	$mapping = isMappedPath($path);
	$root = getConfig("_mapping._root");

	if($root && !$mapping && !startsWith($path,$root))
	{
		$path = combine($root, $path);
		$mapping = isMappedPath($path);
	}

	if(!$mapping) return getRelPath($path);

	$path2 = substringAfter($path,"/");
	$diskPath = combine($mapping,$path2);
	return $diskPath;		
}

function isMappedPath($path)
{	
	$path1 = substringBefore($path,"/");
	$mapping = getConfig("_mapping.$path1");
debug("_mapping.$path1", $mapping);
	if(!$mapping) return false;
	return $mapping;
}

//disk path to /absPath
//search in mappings for 1st value starting with this path
//find key
function resolveMappedPath($path)
{	
	$mapping = getConfig("_mapping");
	if(!$mapping) return $path;
	debug("_mapping", $mapping);
	$key="";
	foreach ($mapping as $key => $value)
	{
		if((startsWith($path, $value)))
			return "/" . str_replace($value, $key, $path);
	}
	return $path;
}

function pathDepth($path)
{
	return count(pathFragments($path));
}

function pathFragments($path)
{
	return array_filter(explode('/',$path));
}

function trimPath($path)
{
	return trimChar($path,'/');
}

function cleanupPath($path)
{
	return implode('/',pathFragments($path));
}


//make relative path from current script to app root
//detect if script is in subdir or not
function pathToAppRoot()
{
	$file="include/config.php";
	if(file_exists($file)) return "";
	if(file_exists("../$file")) return "..";
	if(file_exists("../../$file")) return "../..";
	return false;
}

//make relative path from current script to site data root
//TODO: if .mp is not inside DATA_ROOT_PATH
//make relative path from current script to site data root
//TODO: if .mp is not inside DATA_ROOT_PATH
function pathToDataRoot()
{	
	return combine(pathToAppRoot(), "..");
}

function getAppRoot()
{
	return combine(currentScriptPath(), pathToAppRoot());
}

function getAppRootDir()
{
	return basename(getAppRoot());
}

function getDataRoot()
{
	return combine(currentScriptPath(), pathToDataRoot());
}

function getParent($path)
{
	return substringBeforeLast($path,'/',false);
}

function getPathTo($path,$dir)
{
	return trimPath(substringBefore($path,$dir,true,true));
}

function getPathBefore($path,$dir)
{
	return trimPath(substringBefore($path,$dir));
}

function getPathFrom($path,$dir)
{
	return trimPath(substringAfter($path,$dir,true,true));
}

function getPathAfter($path,$dir)
{
	return trimPath(substringAfter($path,$dir));
}

//Absolute URL functions

function currentUrl()
{	
	return getProtocol() . getServerRoot() . $_SERVER['REQUEST_URI'];
}

function currentScriptName()
{	
	return basename($_SERVER['PHP_SELF']);
}

function currentScriptPath()
{
	return dirname($_SERVER['PHP_SELF']);
}

function getProtocol()
{
	return (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']=='off') ? "http://" : "https://";
}

function getServerRoot()
{
	return getProtocol() . $_SERVER["HTTP_HOST"];
}

function getAbsoluteAppRoot()
{
	return combine(getServerRoot(),getAppRoot());
}

function getAbsoluteDataRoot()
{
	return combine(getServerRoot(),getDataRoot());
}

function getAbsoluteUrl($path="",$page="",$options="")
{
	if(empty($path))
		$path=getPath();
		
	$url=combine(getAbsoluteAppRoot(), $page);
	if(empty($page))
		$url.="/";
		
	if(!empty($path)) 
		$url .= "?path=" . $path;

	if(!empty($options)) 
		$url .= (empty($path) ? "?" : "&" ) . $options;
		
	return $url;
}
?>