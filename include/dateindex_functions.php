<?php
//---------------TAG functions ----------

function getTagFilename($relPath, $subdir, $tag)
{
//	return "$relPath/.tag/$tag.csv";
	splitFilePath($tag,$subdir,$tag);
	return combine($relPath, $subdir, ".tag", "$tag.csv");
}

//get index csv without trying to refresh
function listTagFiles($relPath, $depth=0, $grouped=true)
{
	$search = array("type" => "csv", "depth" => $depth, "subdir" => ".tag");
	$tagFiles = listFiles($relPath, $search);
debug("tagFiles", $tagFiles);
	if($grouped)
		$tagFiles = groupByName("$relPath/.tag", $tagFiles);
debug("tagFiles grouped", $tagFiles, true);
	return $tagFiles;
}

function loadTagFiles($relPath, $depth=0, $fileList=null)
{
	$tagFiles = listTagFiles($relPath, $depth);
	$tagData=array();

	foreach ($tagFiles as $tag => $file)
	{
		$subdir = @$file["subdir"];
		$name = $file["name"];
		$data = loadTagFile($relPath, $subdir, $tag, $fileList);
		if(!$data) continue;

		if(!isset($tagData[$name]))
			$tagData[$name] = $data;
		else
			$tagData[$name] = arrayReplace($tagData[$name], $data);
	}
//	debug("loadTagFiles",$tagData,"print_r");
	return $tagData;
}

function loadTagFile($relPath, $subdir, $tag, $fileList=null)
{
	$filename=getTagFilename($relPath, $subdir, $tag);
debug("loadTagFile", $filename);
	$tagList = readArray($filename, true);

	if($subdir)
	{
		$tags=array();
		foreach ($tagList as $name)
		{			
			$key = combine($subdir, $name);
			$tags[$key]= $name;
		}
		$tagList=$tags;
	}

	if($fileList)
		$tagList = array_intersect_key($tagList, $fileList);
	return $tagList;
}

function saveTagFile($relPath, $tag, $data)
{
	$filename=getTagFilename($relPath, "", $tag);
	if(empty($data))
	{	
		deleteFile($filename);
		return;
	}
	createDir($relPath, ".tag");
	$data = array_values($data);
	return writeCSvFile($filename, $data, false, "\n");
}

function saveFileTag($relPath, $name, $tag, $state)
{
	if(!$name || !$tag) return false;

	//load tag file .tag/$tag, returns array of names
	$tagList = loadTagFile($relPath, "", $tag);
	//if no change, do nothing
	$alreadySet = (isset($tagList[$name]) == $state);
	if($alreadySet) return true;

	if($state)
		$tagList[$name] = $name;
	else
		unset($tagList[$name]);

	saveTagFile($relPath, $tag, $tagList);
	return true;
}

//--------------- dir metadata functions ----------
function getMetadataIndexFilename($relPath, $type="")
{
	if(!$type)	return "$relPath/.tn/.metadata.csv";
	return "$relPath/.tn/.metadata.$type.csv";
}

//get index csv without trying to refresh
function loadMetadataIndex($relPath, $type="")
{
	$indexFilename=getMetadataIndexFilename($relPath, $type);
	return readCsvTableFile($indexFilename, 0, true);
}

// write date index CSV data to file
function saveMetadataIndex($relPath, $data, $type="")
{
	$indexFilename=getMetadataIndexFilename($relPath, $type);
	createDir("$relPath",".tn");
	return writeCsvTableFile($indexFilename, $data, true, "name");
}

function getMetadataIndex($relPath, $type, $fileList=array(), $completeIndex=false)
{
	//TODO use dateIndex.types;IMAGE		
	$index = loadMetadataIndex($relPath, $type);
//debug("loadMetadataIndex", $index, true);
if(!$fileList) $fileList=array();
//debug("fileList", $fileList);
	$subdirFiles=array();
	if($fileList)
	{
		$subdirFiles = array_filter($fileList, "fileHasSubdir"); 
		$fileList = array_filter($fileList, "fileHasNoSubdir"); 
	}

	$addedFiles = 0;
	foreach ($fileList as $name => $file)
	{
		if(isset($index[$name])) continue;
		$filename= getFilename($file["name"], $file["exts"][0], true);
		$filePath = combine($relPath, $file["subdir"], $filename);
debug("getMetadataIndex $name", $filePath);
		if($type=="IMAGE")
			$index[$name] = getImageInfo($filePath, true);
		else if($type=="VIDEO" && getConfig("ENABLE_FFMPEG"))
			$index[$name] = getVideoProperties($filePath);
		$addedFiles++;
	}

	debug("Added Files", $addedFiles);

	//test that all files in index exist: remove deleted file entries
	$deletedFiles=array_diff_key($index, $fileList);
	debug("Deleted Files", count($deletedFiles));
	$filteredIndex=array_diff_key($index, $deletedFiles);
	//write all rows or only the remaining rows
	if($completeIndex)
		$dateIndex=$filteredIndex;
	//if any change: rewrite file	
	if($addedFiles || $deletedFiles && $completeIndex)
		saveMetadataIndex($relPath, $index, $type);

	if($subdirFiles)
		loadSubdirData($index, $relPath, $subdirFiles, $type);

debug();
//debug("getMetadataIndex $type", $index, true);
debug();
	return $index;
}

function loadSubdirData(&$index, $relPath, $subdirFiles, $type="")
{
debug("subdirFiles", count($subdirFiles));
debug("subdirFiles", $subdirFiles);
//for subdir files: load subdir/.dateIndex.csv 
//then asort, as in groupByName
	$prevDir="";
	foreach ($subdirFiles as $key => $file)
	{
		if($file["subdir"] != $prevDir) // if file in different dir: load new date index
		{
			$subdir = combine($relPath, $file["subdir"]);
			debug("loadSubdirData", $subdir);
			if($type)
				$dirIndex = loadMetadataIndex($subdir, $type);
			else
				$dirIndex = loadDateIndex($subdir);
debug("loadSubdirData", $dirIndex);
		}

		if(isset($dirIndex[$file["name"]]))
			$index[$key] = $dirIndex[$file["name"]];
		$prevDir = $file["subdir"];
	}
	return $index;

}


function addToMetadataIndex($relPath, $type, $name, $metadata)
{
	$index=getMetadataIndex($relPath, $type);
	$index[$name] = $metadata;
debug("addToMetadataIndex $name", $metadata);
debug("keys", array_keys($index));
	saveMetadataIndex($relPath, $index, $type);
}

//---------------Date index functions ----------
function getDateIndexFilename($relPath, $type="")
{
	if(!$type)	return "$relPath/.dateIndex.csv";
	return "$relPath/.dateIndex.$type.csv";
}

function getOldestFileDate($relPath)
{
	//read 1st line of CSV
	$indexFilename=getDateIndexFilename($relPath);
	if(!file_exists($indexFilename))
		return;
	$handle = fopen($indexFilename, "r");
	if (!$handle) return;
	$rowData = fgetcsv($handle);
	fclose($handle);
	return $rowData[0];
}

function getNewestFileDate($relPath)
{
	//get index mdate
	return formatFilemtime(getDateIndexFilename($relPath));
}

function getOldestDate($relPath)
{
	return substringBefore(getOldestFileDate($relPath)," ");
}

function getNewestDate($relPath)
{
	return substringBefore(getNewestFileDate($relPath)," ");
}

function getDateRange($relPath)
{
	$o=getOldestDate($relPath);
	$n=getNewestDate($relPath);
	if(!$o && !$n) return formatFilemtime($relPath);
	if(!$o) return $n;
	if($o==$n) return $o;
	return "$o to $n";
}

//get index csv without trying to refresh
function loadDateIndex($relPath)
{
	$indexFilename=getDateIndexFilename($relPath);
	$dateIndex=readCsvFile($indexFilename,1);
	return array_filter($dateIndex);
}

function loadYoutubePlaylist($relPath)
{
	$indexFilename=combine($relPath, "youtube.csv");
	$index=readCsvFile($indexFilename, 0,";");
debug("loadYoutubePlaylist $indexFilename",$index);
	return array_filter($index);
}


function getFilesFromDateIndex($relPath)
{
	return array_keys(loadDateIndex($relPath));
}

//make .dateIndex.csv
//build index entries with $date,$name
function updateIndex($relPath, $files, &$dateIndex=array())
{
	global $relPathG;
	$relPathG=$relPath;

	foreach ($files as $name => $file)
	{
		//take oldest date for this file name
		$key=$file["name"];
		foreach($file["exts"] as $ext)
		{
			$filename = getFilename($file["name"], $ext, true);
			$filedate = getFileDate("$relPath/$filename");
			if(!isset($dateIndex[$key]) || empty($dateIndex[$key]) || $filedate < $dateIndex[$key])
			{
debug("updateIndex $key $name $filename", $filedate);
				$dateIndex[$key]=$filedate;
			}
		}

		if(!$dateIndex[$key])
			unset($dateIndex[$key]);

	}
	return $dateIndex;
}

// write date index CSV data to file
function writeDateIndex($relPath,$dateIndex)
{
	$indexFilename=getDateIndexFilename($relPath);
	if(empty($dateIndex))
	{	
		deleteFile($indexFilename);
		return;
	}
	$maxDate="";
	$fp = @fopen($indexFilename, 'w');
	if(!$fp) return false;

debug("writeDateIndex", count($dateIndex));

	foreach ($dateIndex as $file => $date)
	{	
		fwrite($fp, "$date,$file\n" );
		$maxDate=$date;
	}
	fclose($fp);
	setFileDate($indexFilename, $maxDate);
	return true;
}


function fileHasSubdir($f)
{
	return !empty($f["subdir"]);
}

function fileHasNoSubdir($f)
{
	return empty($f["subdir"]); 
}

//TODO check if index up to date, no file missing, no file deleted: load it
function getRefreshedDateIndex($relPath,$files=array(),$completeIndex=false)
{
	//keep only files in current dir
	$subdirFiles = array_filter($files, "fileHasSubdir");
	$files = array_filter($files, "fileHasNoSubdir"); 
	
	if(empty($files)) return array();
	//load existing index
	$dateIndex=loadDateIndex($relPath);
//debug("files", $files, true);
//debug("loadDateIndex", $dateIndex, true);

	//test that every file is in index: add new file entries if new files
	$addedFiles=array_diff_key($files,$dateIndex);
debug("addedFiles", count($addedFiles));
	if($addedFiles)
	{
		updateIndex($relPath,$addedFiles,$dateIndex);
		if(empty($subdirFiles))
			asort($dateIndex);
	}

	//test that all files in index exist: remove deleted file entries
	$deletedFiles=array_diff_key($dateIndex,$files);
	debug("deletedFiles", count($deletedFiles));
	$filteredIndex=array_diff_key($dateIndex, $deletedFiles);
	//write all rows or only the remaining rows
	if($completeIndex)
		$dateIndex=$filteredIndex;
	
	if($addedFiles || $deletedFiles && $completeIndex)
		writeDateIndex($relPath,$dateIndex);
	
	if($subdirFiles)
		loadSubdirData($filteredIndex, $relPath, $subdirFiles);

	asort($filteredIndex);
	
//debug("getRefreshedDateIndex", $filteredIndex, true);
	return $filteredIndex;
}
?>