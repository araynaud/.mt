<?php

function getConfig($key, $default=NULL)
{
	global $config;
	return arrayGet($config, $key, $default);
}

// make an array from config files
// in ./config, then data root, then subdirs to current path
function LoadConfiguration($path=null, &$configData = array())
{
debug("LoadConfiguration", $path);
	$appRootDir = pathToAppRoot();
//1 default config in .mt/config
	$configFilename = combine($appRootDir, "config", ".config.csv");
	$configData = readConfigFile($configFilename); 

//2a local config in .mt/config (not in repository). should contain directory mappings.
	$configFilename = combine($appRootDir, "config", ".config.local.csv");
	readConfigFile($configFilename, $configData);
	
//2b site root config: should contain directory mappings.
	$configFilename = combine(pathToDataRoot(), ".config.csv");
	readConfigFile($configFilename, $configData);

//3 default config by path depth
	$depth = pathDepth($path);
	$configFilename = combine($appRootDir, "config", ".config.$depth.csv");
	readConfigFile($configFilename, $configData);

	$relPath = getDiskPath($path);
//4 supersede values with folder specific config file in $relPath 
// find in parents and load from root to current dir
//debug("LoadConfiguration", $relPath);	
	if($relPath)
		$configFilenames = findFilesInParent($relPath, ".config.csv");
debug("findFilesInParent", $configFilenames);	
	if($configFilenames) 
	{
		sort($configFilenames);
		foreach($configFilenames as $configFilename)
			readConfigFile($configFilename, $configData);
	}

//5 supersede values with device specific config file in appRoot 
	if($force = arrayGet($configData, "USER_AGENT.FORCE"))
		@$_SERVER["HTTP_USER_AGENT"] .= " $force";

	$configData["USER_AGENT"]["DEVICES"] = $devices = checkUserAgent($configData["USER_AGENT"]["DEVICES"]);
	$configData["USER_AGENT"]["USE_FLASH"] = checkUserAgent($configData["USER_AGENT"]["USE_FLASH"]);
	foreach($devices as $dev)
	{
		$configFilename = combine($appRootDir, "config", ".config.$dev.csv");
		readConfigFile($configFilename, $configData);
	}

//finally add some keys to output
	debug("SPECIAL_FILES", $configData["SPECIAL_FILES"]);
	$configData["ENABLE_FFMPEG"] = isFfmpegEnabled();
	$configData ["SITE_NAME"] = getSiteName();
	$configData["thumbnails"]["dirs"] = array_keys(@$configData["thumbnails"]["sizes"]);

//output config for default site
	if($publish = @$configData["_publish"])
	{
		$site = $publish["default"];
		$configData["publish"] = $publish[$site];
	}
	return $configData;
}

function getExePath($exe="FFMPEG", $key="_FFMPEG", $configData=null)
{
	global $config;
	setIfNull($configData, $config);	
	if(!isset($configData[$key])) return false;
	$exePath=combine(arrayGet($configData, "$key.PATH"), arrayGet($configData, "$key.$exe"));

debug("getExePath($exe, $key)", $exePath);

	if(!file_exists($exePath)) return false;
	return $exePath;
}

function checkUserAgent($devices)
{
	return array_filter(toArray($devices), "clientIs");
}

//config for the current dir only
function getDirConfig($path, $key=null)
{
	$relPath=getDiskPath($path);
//debug("getDirConfig", $relPath);
	$depth=pathDepth($path);

//1 default config by path depth
	$appRootDir=pathToAppRoot();
	$configFilename = combine($appRootDir,"config",".config.$depth.csv");
	$configData = readConfigFile($configFilename);

//2 supersede values with folder specific config file in $relPath 
	$configFilename = combine($relPath, ".config.csv");
	readConfigFile($configFilename, $configData);

//debug("getDirConfig", $configData);
	
	return arrayGet($configData, $key);
}

function getSubdirConfig($relPath, $subdir, $key=null)
{
	$configFilename = combine($relPath, $subdir, ".config.csv");
	$configData = readConfigFile($configFilename);
	return arrayGet($configData, $key);
}

function getSiteName()
{
	return getDirConfig("", "TITLE"); //get root dir title	
}

function readConfigFile($filename, &$csvRows = NULL, $separator="=")
{
	debug("readConfigFile $filename", realpath($filename));
	$lines = readArray($filename);
	if(!$lines)
		return $csvRows;

	if(!$csvRows)
		$csvRows = array();

	$prevKey=null;
	foreach ($lines as $n => $line)
	{
		parseConfigLine($line, $key, $rowData, $separator);
		//key: always a string
		if($key==="")	continue;
		//parse other columns
		foreach($rowData as $i => $column)
			$rowData[$i] = parseValue($rowData[$i]);

		//value = single value or array?
		if(is_array($rowData) && count($rowData)==1)
			$rowData = $rowData[0];
		
		$key = getKeyArray($key, $prevKey);
		setNestedArrayValue($csvRows,$key,$rowData);
		$prevKey=$key;
	}
	return $csvRows;
}

function parseConfigLine($line, &$key, &$rowData=array(), $separator="=")
{
	$rowData = explode(";", $line);
	if(contains($rowData[0], $separator))
	{
		$key = substringBefore($rowData[0], $separator);
		$rowData[0] = substringAfter($rowData[0], $separator);
	}
	else
	{
		$key = $rowData[0]; 
		unset($rowData[0]);
		$rowData=array_values($rowData);
	}
	$key = trim($key);
	return $rowData;
}

?>