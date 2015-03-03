<?php 
function listFilesRecursive($dir, $search=array())
{
	$subdirs = true;
debug("listFilesRecursive $dir", $search);

	$files = listFilesDir($dir, $search, $subdirs);
	$root = getMappedRoot($dir);
	debug("mapped root $dir", $root);

	if(!@$search["depth"]) return $files;

	//loop for dirs with depth-1, subpath
	if(@$search["depth"] > 0 && $subdirs)
	{
		$search["depth"]--;
		$subpath = @$search["subpath"];
		foreach ($subdirs as $subdir)
		{
			$subdirPath = combine($dir, $subdir);
			$search["subpath"] = combine($subpath, $subdir);
	debug("subpath", $search["subpath"]);
			$subdirFiles = listFilesRecursive($subdirPath, $search);
			if($subdirFiles && @$search["nested"]) //nested subdirs or flat array
				$files[$subdir] = $subdirFiles; 
			else if ($subdirFiles)
				$files = array_merge($files, $subdirFiles);
		}
	}
	//recursion in parent dir
	else if(@$search["depth"] < 0 && $dir != $root)
	{
		$search["depth"]++;
		$search["subpath"] = combine(@$search["subpath"], "..");
		$parentDir = getParent($dir);
		$parentFiles = listFilesRecursive($parentDir, $search);
		if($parentFiles)
			$files=array_merge($files, $parentFiles);
	}

	return $files;
}

function listFilesDir($dir, $search=array(), &$subdirs=false)
{
	$files = array();
	$tndir = arrayGetCoalesce($search, "tnDir", "subdir");
	$tndir = combine($dir, $tndir);
	if(!is_dir($tndir))
	{
		debug("scandir $tndir", "not a dir");
		$subdirs = false;
		return $files;
	}

	//search for 1 exact file
	if(@$search["file"])
	{
		$filePath = combine($tndir, $search["file"]);
		if(file_exists($filePath))
		{
			$files[]=combine(@$search["subpath"], $search["file"]);
			return $files;
		}
	}

	$allFiles = scandir($tndir);
	if(!$allFiles)
	{
		debug("scandir $tndir", "no files");
		$subdirs = false;
		return $files;
	}

	$files = $allFiles;
	$ignoreList = loadIgnoreList($dir);
	if(@$search["tag"])
		$search["tagfiles"] = searchTagFiles($dir, 0, $search["tag"]);

	$specialFiles = getConfig("SPECIAL_FILES");
	$specialTypes = getConfig("TYPES.SPECIAL");

	//filter allFiles
	$files = array_diff($files, $ignoreList);
	$files = array_diff($files, $specialFiles);

debug("listFilesDir subdirs", $subdirs);	
	if($subdirs!==false)
		$subdirs = selectDirs($dir, $files);

	$files = filterFiles($files, $search);

	if(@$search["subpath"] && !@$search["nested"] || @$search["tnDir"])
		array_walk($files, "addSubpath", $search);

	return $files;
}

function addSubpath(&$item, $key, $search)
{
	$item = combine(@$search["subpath"], @$search["tnDir"], $item);
}

function filterFiles($files, $search)
{
	global $searchG;
	if(empty($search))
		return $files;

	if(@$search["type"])
		$search["exts"] = getExtensionsForTypes($search["type"]);

//debug("filterFiles", $search);
	$searchG = $search;
	$files = array_filter($files, "fileIsSelected");

debug("filterFiles count", @$search["count"]);
	if(@$search["count"])
	{
		$files = array_slice($files, 0, $search["count"]);
debug("filterFiles files", $files);
		$search["count"] -= count($files);
	}
	$searchG = null;

	//array_slice with maxcount
	return $files;
}

//$result = ($hasName || $hasTag) && $hasType;
function fileIsSelected($file, $search=null)
{
	global $searchG;
	setIfNull($search, $searchG);
	$result=true;
	splitFilename($file, $name, $ext);
//debug("fileIsSelected $file", $search);
	if(@$search["file"])
		return equals($file, @$search["file"]) || equals($name, @$search["file"]);

	if(@$search["name"])
		$hasName = $result = fileHasName($file, $search);

	if(@$search["tagfiles"])
	{
		$hasTag = array_key_exists($name, $search["tagfiles"]);
		$result = $result || $hasTag;
	}

	if(@$search["exts"] || @$search["type"] )
	{
		$hasType = @$search["exts"] && in_array(strtolower($ext), @$search["exts"]);
		$result = $result && $hasType;
	}

	if(function_exists("is_admin") && !is_admin())
		$result = $result && fileIsNotHidden($file);

	return $result;
}

function fileHasName($file, $search=null)
{
	global $searchG;
	setIfEmpty($search, $searchG);
	if(empty($search)) return true;
	$name = @$search["name"]; 
	if(!$name) return true;

	$file=getFilename($file);

	if(@$search["regex"])
		$result = preg_match($search["regex"], $file);
	else
		$result = equals($file, $name);
//	if ($result)	debug("fileHasName " . @$search["regex"], $result);
	return $result;
}


// dir listing functions with readdir
function listAllFilesInDir($dir)
{
	$files=array();
	if (!is_dir($dir)) return $files;

	$handle = opendir($dir);	
	while(($file = readdir($handle))!==false)
		$files[$file] = $file;
	closedir($handle);
	return $files;
}

function listAllFiles($dir, $maxCount=0)
{
	$search = array("count"=>$maxCount);
	return listFilesDir($dir, $search);
}

function getFileByName($dir, $name)
{
	$search = array("name" => "*$name*");
	return listFilesDir($dir, $search);
}

//find if name is matched  0 times, 1 time or multiple times
function countFilesByName($dir, $name, $group=true)
{
	$search = array("name" => $name, "count" => 4);
	parseWildcards($search);
	$list = listFilesDir($dir, $search);
	if($group)
		$list = groupByName($dir, $list);
	if(array_key_exists($name, $list)) 
		return array(key($list));

	return array_keys($list);
}

function parseWildcards(&$search)
{
	if(!@$search["name"]) return;
	$name = $search["name"];

	$starts = !startsWith($name, "*");
	if(!$starts)
		$name = substringAfter($name, "*");
	$search["name"]=$name;
	if(!$name)
		return $search;

	$ends=!endsWith($name, "*");
	if(!$ends)
		$name=substringBeforeLast($name, "*");
	$search["name"] = $name;

	$starts = $starts ? "^"  : ""; //starts with name
	$ends   = $ends   ? "$" : "";  //finishes with name before .extension
	$search["regex"]="/($starts($name)$ends)/i";

//	debug("parseWildcards", $search);
	return $search;
}

function sortFiles($files,$sort,$dateIndex=array())
{
//TODO: sort by type + by title + case insensitive
	if(!$sort)
		return $files;
	$reverse=parseSort($sort);

	if(startsWith("shuffle",$sort)) 
		shuffle($files);
	else if (startsWith("name",$sort) && $reverse)  //TODO: case insensitive
		arsort($files);
	else if (startsWith("name",$sort))
		asort($files);
	else if(startsWith("date",$sort) && !empty($dateIndex))
	{
		$dateIndex=array_intersect_key($dateIndex, $files);
		$files=array_keys($dateIndex);
		if($reverse) $files=array_reverse($files);
	}
	return $files;
}	

function sortDirs($relPath,$dirs,$sort)
{
//TODO: sort by type + by title + case insensitive
	if(!$sort)
		return $dirs;
	$reverse=parseSort($sort);

	if(startsWith("shuffle",$sort)) 
		shuffle($dirs);
	else if (startsWith("name",$sort) && $reverse)
		arsort($dirs);
	else if (startsWith("name",$sort))
		asort($dirs);
	else if(startsWith("date",$sort))
	{
		global $relPathG,$reverseG;
		$relPathG=$relPath;
		$reverseG=$reverse;
		usort($dirs,"compareNewestFileDates");
	}
	return $dirs;
}

function parseSort(&$sort)
{
	$sort=strtolower($sort);
	$reverse = $sort[0]=="r";
	if($reverse)
		$sort=substr($sort,1);

	return $reverse;
}

//to sort dirs by date
function compareNewestFileDates($dir1,$dir2,$relPath="",$reverse=false)
{
	global $relPathG,$reverseG;
	setIfEmpty($relPath,$relPathG);
	setIfEmpty($reverse,$reverseG);
	$d1=getNewestFileDate(combine($relPath,$dir1));
	$d2=getNewestFileDate(combine($relPath,$dir2));
	$reverse=$reverse ? -1 : 1;
	return $reverse * strcmp($d1,$d2);
}


function loadIgnoreList($dir)
{
	global $ignoreList;
	$ignoreList=readArray("$dir/.ignore.txt", true);
	return $ignoreList;
}

//function with condition to exclude file before adding it to array
function ignoreFile($file)
{
	global $ignoreList, $relPathG;
	//always ignore $specialDirs and file names and types
	if(isSpecialFile($file)) return true;
	//list hidden and ignored files for admin
	if(function_exists("is_admin") && is_admin()) return false;
	if(isset($ignoreList[$file])) return true;

	return fileIsHidden($file);
}

function isSpecialFile($file)
{
	$specialFiles = getConfig("SPECIAL_FILES");
	if(!$specialFiles) return false;

	$file=strtolower($file);
	if(isset($specialFiles[$file])) return true;
	foreach ($specialFiles as $value)
		if(startsWith($file, $value)) return true;

	return false;
}

//list filter*.*
//to find comments
function listFilesStartingWith($relPath,$name,$type,$maxCount=0)
{
	$search = array();
	$search["type"]=$type;
	$search["name"]=$name;
	$search["start"]=true;
	$search["count"]=$maxCount; // find only first one
	return listFilesDir($relPath,$search);
}

//see if a dir has at least one files of listed type
//if yes, find 1st file of type
function hasFiles($relPath,$type="",$recursive=false)
{
	$search =  array();
	$search["type"]=$type;
	$search["depth"]=$recursive;
	$search["count"]=1; // find only first one
	return listFilesRecursive($relPath,$search);
}

function listSubdirs($relPath,$recursive=0)
{
	$search =  array();
	$search["type"]="DIR";
	$search["depth"]=$recursive;
	//$search["count"]=1; // find only first one
	return listFilesRecursive($relPath,$search);
}


//remove files with same name as previous 
// mov0001.avi, mov001.flv => mov0001
//$files must be sorted
function getDistinctNames($files)
{
	$distinct=array();
	$prev="";
	foreach ($files as $file)
	{
		$name=substringBeforeLast($file,".");
		if($prev!=$name)
			$distinct[] = $file;
		$prev=$name;
	}
	return $distinct;
}

function groupByName($relPath, $files, $byType=false, $details=true)
{
	global $relPathG;
	$relPathG=$relPath;

	$distinct=array();
	foreach ($files as $file)
	{
		//split subdir/file.ext
		splitFilePath($file, $subdir, $filename);
		splitFilename($filename, $name, $ext);
		$type = getFileType($file);
		$filePath=combine($relPath, $subdir, $filename);
		$exists=file_exists($filePath);
//debug("groupByName $subdir / $file", $filePath);
		$key = combine($subdir, $name);//, !$byType ? $type : false);

		if($byType) 
		{
			$new = !isset($distinct[$type]);
			if($new) $distinct[$type] = array();

			$new = !isset($distinct[$type][$key]);
			if($new) $distinct[$type][$key] = array();
		}
		else
		{
			$new = !isset($distinct[$key]);
			if($new) $distinct[$key] = array();
		}
		$element = $byType ? $distinct[$type][$key] : $distinct[$key];
		if($new)
		{
			$element["name"] = $name;
			$element["subdir"] = $subdir;
			$element["type"] = $type;
			$element["exts"] = array();
			if($details && $exists)
			{
				$element["size"] = array();
				$element["date"] = array();
			}
		}
		$element["exts"][]=$ext;
		if($details && $exists)
		{
			$element["size"][]=filesize($filePath);
			$element["date"][]=formatFilemtime($filePath);
		}
		if($byType) 
			$distinct[$type][$key] = $element;
		else
			$distinct[$key] = $element;
	}

	//article files: remove description files that have same name as another mediaFile
	if($byType && isset($distinct["TEXT"])) 
	{
		$articleFiles = $distinct["TEXT"];
		foreach ($distinct as $type => $typeFiles)
		{
			if($type=="TEXT") continue;
			$articleFiles = array_diff_key($articleFiles, $typeFiles);
		}
		$distinct["TEXT"] = $articleFiles;
	}
	return $distinct;
}

//filter array of filenames
function removeIgnoredFiles($relPath,$files)
{
	return excludeFiles($files, loadIgnoreList($relPath));
}

function excludeFiles($files, $list)
{
	if(is_string($list))	$list=readArray($list);		
	if(empty($list))		return $files;
	return array_diff($files,$list);
}

//Filter sub dirs only
function selectDirs($relPath, $fileList)
{
	global $relPathG;
	$relPathG=$relPath;
debug("selectDirs",  $relPathG);	
	$filteredList = array_filter($fileList,"fileIsDir");
	return $filteredList;
}

function fileIsDir($file)
{
	global $relPathG;
	$dirPath = combine($relPathG, $file);
//debug ("fileIsDir" . $dirPath, is_dir($dirPath));
	return is_dir($file) || is_dir($dirPath);
}

function selectFilesByType($fileList,$ext,$sort=false)
{
	if(empty($ext))
		return $fileList;
		
	global $extG;
	$extG=getExtensionsForTypes($ext);
	$filteredList = array_filter($fileList,"fileHasType");
	if($sort)
		asort($filteredList);
	return $filteredList;
}

function selectFiles($fileList,$name,$ext,$sort=false)
{
	if(empty($ext) && empty($name))
		return $fileList;
	global $nameG,$extG;
	$nameG=$name;
	$extG=getExtensionsForTypes($ext);
	$filteredList = array_filter($fileList,"fileHasNameAndType");
	if($sort)
		asort($filteredList);
	return $filteredList;
}

//type => exts
//todo: multiple types, split by | or , or ' '
function getExtensionsForTypes($exts)
{
	if(!isset($exts) || !$exts)	return "";
	$exts=toArray($exts);
	$result=array();
	foreach ($exts as $ext)
		$result=array_merge($result, getExtensionsForType($ext));
debug("getExtensionsForTypes",$result);
	return $result;
}

function getExtensionsForType($ext)
{
	if(is_array($ext))
		return flattenArray($ext);

//	debugStack();
	global $config;
	$key=strtoupper($ext);
	if(isset($config["TYPES"][$key]))	return flattenArray($config["TYPES"][$key]);
	return toArray($ext);
}

//file boolean Filter functions
function fileExists($file)
{
	global $relPathG;
	return file_exists(combine($relPathG,$file));
}

function fileIsImage($filename)
{
	return getFileType($filename)=="IMAGE";
}

function fileIsInSubdir($file)
{
	return contains($file,"/",true);
}

function fileIsInCurrentDir($file)
{
	return !contains($file,"/",true);
}

function fileIsNotHidden($file)
{
	return $file[0] != ".";
}

function fileIsHidden($file)
{
	return $file[0] == ".";
}


//windows only
function isFileHiddenNTFS($file)
{
	if(PHP_OS != "WINNT") return false;

	$cmd='FOR %A IN ("'.$file.'") DO @ECHO %~aA';
debug("isFileHiddenNTFS",$cmd);
    $attr = trim(exec($cmd));
    return (@$attr[3] === 'h');
}

//windows only
function listFilesNTFS($dir, $options="", $valuesAsKeys=false)
{
	if(PHP_OS != "WINNT") return array();
debug("listFilesNTFS", $options);
	$cmd="dir /B $options \"$dir\"";
    $files = execCommand($cmd, false, false);
	if($valuesAsKeys)
		$files = array_combine($files, $files);
    return $files;
}

function listHiddenFilesNTFS($dir, $valuesAsKeys=false)
{	
	if(PHP_OS != "WINNT") return array();
	return listFilesNTFS($dir, "/AH", $valuesAsKeys);
}

//ext => type
function getFileType($file, $checkExists=false)
{
	global $config;
	if($checkExists && !file_exists($file))	return false;

	if(fileIsDir($file))	return "DIR";

	$fileExt=strtolower(getFilenameExtension($file));
	foreach ($config["TYPES"] as $type => $ext)
		if(arraySearchRecursive($ext, $fileExt)!==false) 
			return $type;

	return "FILE";
}

function fileHasType($file, $ext="")
{
	global $extG;
	setIfEmpty($ext,$extG);
	if(is_array($ext) && count($ext)==1) $ext=$ext[0];

	if(empty($ext))	return true;
//debug("fileHasType $file", $ext);
	if(equals($ext,"DIR") && fileIsDir($file)) return true;
	if(equals($ext,"FILE") && !fileIsDir($file)) return true;

	$fileExt=getFilenameExtension($file);
	$result=arraySearchRecursive($ext, $fileExt);
	return $result!==false;
}

/*
//filter by partial or complete name (without extension)
function fileHasName($file, $name="", $start=false, $end=false)
{
	global $nameG;
	setIfEmpty($name,$nameG);
	if(empty($name)) return true;

	$file=getFilename($file);

	$start = $start ? "^"  : ""; //starts with name
	$end   = $end   ? "$" : "";  //finishes with name before .extension 
	$regex="/($start$name$end)/i";
	$result = preg_match($regex, $file);
	//if ($result)
		debug("fileHasName $file $regex", $result);
	return $result;
}


function fileHasNameAndType($file,$name="",$ext="",$start=false,$end=false)
{
	$result=true;
	if($ext)	$result = fileHasType($file,$ext);	
	if($name)	$result = $result && fileHasName($file,$name,$start,$end);
	return $result;
}
*/

// filter array between 2 values passed as an array
//to search by name or date range
function isBetween($element,$min="", $max="")
{
	global $minG, $maxG;
	setIfEmpty($min,$minG);
	setIfEmpty($max,$maxG);
	return isBetweenValues($element,array($min,$max));
}

function isBetweenValues($element,$values="")
{
	global $valueG;
	setIfEmpty($values,$valueG);
		
	if(empty($values))		return true;		
	if(empty($values[0]) && empty($values[1]))		return true;		
	if(empty($values[0]))	return ($element<=$values[1]);
	if(empty($values[1]))	return ($element>=$values[0]);

	sort($values);
	return ($element>=$values[0] && $element<=$values[1]);
}

function isPrivate($path)
{
	return 	substr($path, 0, 1) == "." || (strpos($path, "/.") !== false);
}

//find folder.css in current path, or another css file
//if not found, look in parent directories
//returns relative link to FIRST FILE found, or empty if none found
//$getOther boolean: retrieve another file of the same type if file not found
function findInParent($relPath, $file, $getOther=false)
{
	$found = findFilesInParent($relPath, $file, $getOther, 1, true);
	return reset($found);
}

//find multiple files in parent
function findFilesInParent($relPath, $file, $getOther=false, $maxCount=0, $appendPath=true)
{
	$search =  array();
	if(!$getOther)
		$search["file"]=$file;
	else
		$search["type"]=getFilenameExtension($file);

	$search["depth"] = -10; // TODO, use depth of path?
	$search["count"] = $maxCount; // find only first one
	$found = listFilesRecursive($relPath, $search);
	if(!$found) 
		return $found;

	$found=array_values($found);
	if($appendPath)
		for($i=0; $i<count($found); $i++)
			$found[$i]=combine($relPath,$found[$i]);

	return $found;
}

//pick random 4 thumbs in this dir
function subdirThumbs($relPath, $max_thumbs)
{
	$search = array("type" => "IMAGE", "depth" => 2, "tnDir" => ".tn", "count" => 2 * $max_thumbs);
	$pics=listFilesRecursive($relPath, $search);
	$pics = pickRandomElements($pics, $max_thumbs);
	return $pics;
}


function findFirstImage($relPath)
{
	$pics = findFirstImages($relPath,1);
	if($pics)
		return end($pics);
}

function findFirstImages($relPath, $maxCount=1)
{
	$search = array("type" => "IMAGE", "depth" => 1, "tnDir" => ".ss", "count" => $maxCount);
	$pics=listFilesRecursive($relPath, $search);
	if(!$pics)
	{
		$search["tnDir"] = ".tn";
		$pics=listFilesRecursive($relPath, $search);
	}
	if(!$pics)
	{
		unset($search["tnDir"]);
		$pics=listFilesRecursive($relPath, $search);
	}
	debug("findFirstImage", $pics);
	return $pics;
}

//pick random 4 thumbs in this dir
function pickRandomElements($array, $nb)
{
	shuffle($array);
	return array_slice($array, 0, $nb);
}
?>