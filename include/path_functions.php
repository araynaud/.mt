<?php
//============= PATH functions


function pathToArray($str)
{
	return preg_split("_[\\\\/]_", $str);
}


//combine paths
//join str array without empty elements
//handle .. => remove array element + previous

function isDomainRoot($str)
{
	$domain = substringAfter($str,"://");
	$isdomain = substringAfter($domain,"/");
	return $isdomain == "";
}

function combine()
{
	$argArray = func_get_args();
	if(!$argArray)	return "";
	//detect path separator: / or \
	$sep="/";
	if(contains($argArray[0],"\\"))
		$sep="\\";
	
	$isRoot = startsWith($argArray[0], $sep) ? $sep : "";

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
	if($depth==1)	return $isRoot . $strArray[0];

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
	return $isRoot . implode($sep, $strArray);
}


//return filters from request: path, name, tag, search, file?
function requestFilters($addFilters=true, $selectOneFile = false)
{	
	$qs = urldecode($_SERVER["QUERY_STRING"]);
	$hasParams = contains($qs, "=");
	debug("requestFilters qs", $qs);
	if(!$hasParams)
	{
		$params = parseQueryString($qs);
	}
	else
	{
		$params = arrayCopyMultiple($_REQUEST, "name,type,tag,search,count");
		$params["path"] = urldecode(reqParam("path"));
		$params["file"] = urldecode(reqParam("file"));
		$params["depth"] = reqParam("depth", 0);
	}
	debug("requestFilters", $params);

	$filePath = combine(@$params["path"], @$params["file"]);
	$realPath = getDiskPath($filePath);
	$filetype = getFileType($realPath, true);
	debug("filetype", $filetype);
	if($filetype=="DIR" && !$selectOneFile)
	{
		$params["path"] = $filePath;
		$params["file"] = "";
	}
	else
		splitFilePath($filePath, $params["path"], $params["file"]);

//	debug("requestFilters", $params);
	if(!@$params["file"]) 
		return $params;

	$realDir = getDiskPath($params["path"]);
	$name = getFilename($params["file"]);

	//is the file parameter a file name, a partial name, a tag ?
	//only use in getSearchParameters
	$fileExists = countFilesByName($realDir, $name);
	debug("countFilesByName $realDir $name", $fileExists);

	if(count($fileExists) == 1)
	{
		$name = reset($fileExists);
		if($addFilters)
			$params["name"] = $name;
		else
			$params["file"] = $name;

		if($addFilters && $filetype)
			$params["type"] = $filetype;
	}
	else
	{
		$params["search"] = $name;
		$params["file"] = "";
	}
//	debug("requestFilters", $params);
	return $params;
}

function getSearchParameters($filters=null)
{
	if(!$filters) 
		$filters = requestFilters(false);
	$search = arrayCopyMultiple($filters, "path,file,depth,count");	
	if($type = $search["type"] = arrayGet($filters, "type"))
		$search["exts"] = getExtensionsForTypes($type);

	$search["name"] = arrayGetCoalesce($filters, "name", "search");
	$search["tag"]  = arrayGetCoalesce($filters, "tag", "search");

	$search["sort"] = arrayGet($filters, "sort");
	$search["depth"]  = arrayGet($filters, "depth");
	setIfEmpty($search["depth"], 0);
	$search["subdir"] = arrayGet($filters, "subdir");
	$search["subpath"] = "";
	$count = arrayGet($filters, "count");
	if($count)
		$search["count"] = $count;
	$search["config"] = reqParamBoolean("config", true);
	$search["nested"] = reqParamBoolean("nested");
	parseWildcards($search);

	debug("getSearchParameters", $search);
	return $search;
}


//query string: path:depth:search:start
//example: 2014/december/cookies:1:best|amy:DSC_0764
function parseQueryString($qs, 	&$result = array())
{	
	setIfNull($qs, urldecode($_SERVER["QUERY_STRING"]));

	$params = explode(":", $qs);
//2014/december/cookies 	1	best|amy	DSC_0764

//	2014/december/cookies/DSC_0764
	$result["path"] = $params[0];

	if(count($params) == 2)
		$result["start"] = $params[1];
	else if(count($params) == 3)
	{
		if(is_numeric($params[1]) || $params[1]=="*")
			$result["depth"] = $params[1];
		else
			$result["search"]   = $params[1];

		$result["start"] = $params[2];
	}
	else if(count($params) >= 4)
	{
		$result["depth"] = $params[1];
		$result["search"]   = $params[2];
		$result["start"]  = $params[3];
	}
	return $result;
}

function postPath()
{	
	return getPath(@$_POST["path"]);
}

function reqPath()
{	
	return getPath(@$_REQUEST["path"]);
}

function getPath($path="")
{	
	if(!$path)	$path = @$_GET["path"];
	if(!$path)	return "";
	$path = urldecode($path);
	return $path;

	//if not, parse level by level
	$defaultRoot = pathToDataRoot();
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

	if($root && !$mapping && !startsWith($path, $root))
	{
		$path = combine($root, $path);
		$mapping = isMappedPath($path);
	}

	if(!$mapping) return getRelPath($path);

	$path2 = substringAfter($path, "/");
	$diskPath = combine($mapping, $path2);
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

function getMappedRoot($dir)
{	
	$mappings = getConfig("_mapping");
	if(!$mappings) return "";

	foreach ($mappings as $key => $value) 
		if(startsWith($dir, $value))
			return $value;
	return "";
}


//disk path to /absPath
//search in mappings for 1st value starting with this path
//find key
function diskPathToUrl($path)
{	
	$mapping = getConfig("_mapping");
	if(!$mapping) return $path;
	$key="";
	foreach ($mapping as $key => $value)
	{
		if((startsWith($path, $value)))
		{
debug("mapping $key", $value);
			$path = "/" . str_replace($value, $key, $path);
			//$path = substringAfter($path, "/_root/", true);
			return $path;
		}
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
	return getServerRoot() . $_SERVER['REQUEST_URI'];
}

function currentUrlDir()
{	
	return getServerRoot() . currentScriptPath();
}

function currentScriptUrl()
{	
	return getServerRoot() . $_SERVER['PHP_SELF'];
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

function getAbsoluteUrl($path="", $page="", $options=array())
{
	$url=combine(getAbsoluteAppRoot(), $page);
	if(empty($page))	$url.="/";

	if(!empty($path))	$options["path"] = $path;

 	$qs = http_build_query($options);
	if(!empty($options)) $url .= "?$qs";		
	return $url;
}

function getAbsoluteFileUrl($path="",$file="")
{
	$relPath = getDiskPath($path);
	debug($path,$relPath);
	$absPath = diskPathToUrl($relPath);
	$absPath = substringAfter($absPath, "../", true);
debug ("getAbsoluteFileUrl", $absPath);
	 return combine(getServerRoot(), $absPath, $file);
}

function toAbsoluteUrl($url)
{
	if(startsWith($url, "//"))
		return getProtocol() . substringAfter($url,"//");

	if(contains($url, "://"))
		return $url;

	if(startsWith($url, "/"))
		return getServerRoot() . $url;

	if(startsWith($url, "./"))
		$url = substringAfter($url,"./");

	if(startsWith($url, ".."))
	{
		$url = substringAfter($url,"../");
		return getParent(currentUrlDir()) . "/$url";
	}

	return currentUrlDir() . "/$url";
}

?>