<?php
//---------------TAG functions ----------

function getTagFilename($relPath, $tag)
{
	return "$relPath/.tag/$tag.csv";
}

//get index csv without trying to refresh
function listTagFiles($relPath)
{
	$search = array("type" => "csv");
	$tagFiles = listFiles("$relPath/.tag", $search);
debug("tagFiles", $tagFiles);
	$tagFiles = groupByName($tagFiles);
debug("tagFiles grouped", $tagFiles);
	return $tagFiles;
}

function loadTagFiles($relPath, $fileList=null)
{
	$tagFiles = listTagFiles($relPath);
	$tagData=array();

	foreach ($tagFiles as $tag => $file)
	{
		$data = loadTagFile($relPath, $tag, $fileList);
		if($data)
			$tagData[$tag] = $data;
	}
	return $tagData;
}

function loadTagFile($relPath, $tag, $fileList=null)
{
	$filename=getTagFilename($relPath, $tag);
	$tagList = readArray($filename, true);
	if($fileList)
		$tagList = array_intersect_key($tagList, $fileList);
	return $tagList;
}

function saveTagFile($relPath, $tag, $data)
{
	$filename=getTagFilename($relPath, $tag);
	createDir($relPath, ".tag");
	$data = array_values($data);
	return writeCSvFile($filename, $data, false, "\n");
}

function saveFileTag($relPath, $name, $tag, $state)
{
	if(!$name || !$tag) return false;

	//load tag file .tag/$tag, returns array of names
	$tagList = loadTagFile($relPath, $tag);
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
			$filedate=getFileDate("$relPath/$filename");
debug("updateIndex $key $name $filename", $filedate);
			if(!isset($dateIndex[$key]) || empty($dateIndex[$key]) || $filedate < $dateIndex[$key])
			{
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
	global $config;
	$indexFilename=getDateIndexFilename($relPath);

	if(empty($dateIndex))
	{	
		@unlink($indexFilename);
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
	if($maxDate)
		touch($indexFilename,strtotime($maxDate));
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
debug("files", $files, true);
//debug("loadDateIndex", $dateIndex, true);

	//test that every file is in index: add new file entries if new files
	$addedFiles=array_diff_key($files,$dateIndex);
debug("addedFiles", $addedFiles, true);
	if($addedFiles)
	{
		updateIndex($relPath,$addedFiles,$dateIndex);
		if(empty($subdirFiles))
			asort($dateIndex);
	}

	//test that all files in index exist: remove deleted file entries
	$deletedFiles=array_diff_key($dateIndex,$files);
	debug("deletedFiles", $deletedFiles, true);
	//if($deletedFiles)
	$filteredIndex=array_diff_key($dateIndex, $deletedFiles);
	//write all rows or only the remaining rows
	if($completeIndex)
		$dateIndex=$filteredIndex;
	
	if($addedFiles || $deletedFiles && $completeIndex)
		writeDateIndex($relPath,$dateIndex);
	
	if(!$subdirFiles)
		return $filteredIndex;
	
debug("subdirFiles", count($subdirFiles));

	
//for subdir files: load subdir/.dateIndex.csv 
//then asort, as in groupByName
	$prevDir="";
	foreach ($subdirFiles as $key => $file)
	{
		//split subdir/file
		//splitFilePath($file,$subdir,$filename);
		//splitFilename($filename, $file["name"], $exts);
		if($file["subdir"] != $prevDir) // if file in different dir: load new date index
			$dateIndex = loadDateIndex(combine($relPath, $file["subdir"]));

		if(isset($dateIndex[$file["name"]]))
		{
			$filteredIndex[$key] = $dateIndex[$file["name"]];
			debug($key, $filteredIndex[$key]);		
		}
		$prevDir = $file["subdir"];
	}
		
	asort($filteredIndex);
	
	return $filteredIndex;
}
?>