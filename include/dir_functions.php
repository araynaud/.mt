<?php 
function listFilesRecursive($dir, $search=array())
{
	$subdirs = true;
debug("listFilesRecursive $dir", $search);
addFunctionCall("listFilesRecursive");

	$files = listFilesDir($dir, $search, $subdirs);
	if(!@$search["depth"]) return $files;
	if(isset($search["count"]))
	{
		$search["count"] -= count($files);
		if($search["count"] <= 0) 
			return $files;
		debug("listFilesRecursive remaining", @$search["count"]);
	}

	if(!@$search["root"])
	{
		@$search["root"] = getMappedRoot($dir);
		debug("mapped root $dir", @$search["root"]);
	}

	//loop for dirs with depth-1, subpath
	if(@$search["depth"] > 0 && $subdirs)
	{	
		$search["depth"]--;
		$subpath = @$search["subpath"];
		foreach ($subdirs as $subdir)
		{
			if(isset($search["count"]) && $search["count"] <= 0) 
				return $files;

			$subdirPath = combine($dir, $subdir);
			$search["subpath"] = combine($subpath, $subdir);
debug("subpath", $search["subpath"]);

			$subdirFiles = listFilesRecursive($subdirPath, $search);
			if(!$subdirFiles) continue;
			if(isset($search["count"]))
			{
				$search["count"] -= count($subdirFiles);
				debug("listFilesRecursive remaining", @$search["count"]);
			}

debug("count", @$search["count"]);
			if(@$search["nested"]) //nested subdirs or flat array
				$files[$subdir] = $subdirFiles; 
			else
				$files = array_merge($files, $subdirFiles);
		}
	}
	//recursion in parent dir
	else if(@$search["depth"] < 0 && $dir != @$search["root"])
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

function listFilesDir($dir, &$search=array(), &$subdirs=false)
{
	addFunctionCall("listFilesDir");
	$files = array();
	$tn = arrayGetCoalesce($search, "tndir", "subdir");
	$tndir = combine($dir, $tn);
	if(!is_dir($dir))
	{
		debug("listFilesDir $tndir", "not a dir");
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
			if(!@$search["depth"])
				return $files;
		}
	}

	$tnFiles = $dirFiles = scandir($dir);
	if($tn)
		$tnFiles = is_dir($tndir) ? scandir($tndir) : array();

	$ignoreList = loadIgnoreList($dir);
	$specialFiles = getConfig("SPECIAL_FILES", array());

	if($subdirs!==false)
	{
		$subdirs = selectDirs($dir, $dirFiles);
		if($subdirs)
			$subdirs = array_diff($subdirs, $ignoreList, $specialFiles);
		debug("listFilesDir subdirs", count($subdirs));	
	}

debug("listFilesDir files", $files);	

	if(count($files) == 1)		return $files;

	$files = array_diff($tnFiles, $ignoreList, $specialFiles);

	if(@$search["type"] && !isset($search["exts"]))
	{
		$search["exts"] = getExtensionsForTypes($search["type"]);
		debug("exts", $search["exts"], "print_r");
	}

	if(@$search["tag"] && !isset($search["tagfiles"]))
	{
		$search["tagfiles"] = searchTagFiles($dir, $search["depth"], $search["tag"]);
		debug("tagfiles", $search["tagfiles"], "print_r");
	}

	global $relPathG;
	$relPathG = $dir;
	$files = filterFiles($files, $search);

	if(@$search["subpath"] && !@$search["nested"] || @$search["tndir"])
		array_walk($files, "addSubpath", $search);

	if(@$search["noext"])
		array_walk($files, "removeExt");

debug("listFilesDir $dir $tndir", count($files));

	return $files;
}

function removeExt(&$item, $key)
{
	$item = getFilename($item);
}

function addSubpath(&$item, $key, $search)
{
	$item = combine(@$search["subpath"], @$search["tndir"], $item);
}

function filterFiles($files, $search)
{
	global $searchG;
	if(empty($search))
		return $files;

//debug("filterFiles", $search);
	$searchG = $search;
	$files = array_filter($files, "fileIsSelected");

	if(isset($search["count"]))
		$files = array_slice($files, 0, $search["count"]);
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

	$specialPrefixes = getConfig("SPECIAL_PREFIX", array());
	foreach ($specialPrefixes as $prefix) 
		if(startsWith($name, $prefix))
			return false;

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
		$hasType = fileHasType($file, $search);
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

function fileHasType($file, $search=null)
{

	global $searchG;
	setIfEmpty($search, $searchG);
//debug("fileHasType $file", $search);
	if(empty($search))	return true;
	if(is_string($search))
		$search = array("type"=>$search);
	if(!@$search["type"]) return true;
//return getFileType($file) == $search["type"];

	$isdir = fileIsDir($file);
	if(contains(@$search["type"], "DIR") && $isdir) return true;
	if(contains(@$search["type"], "FILE") && !$isdir) return true;

	if(!isset($search["exts"]))
		$search["exts"] = getExtensionsForTypes($search["type"]);

	$ext = strtolower(getFilenameExtension($file));
	$result = $search["exts"] && in_array($ext, $search["exts"]);
//if ($result) debug("fileHasType $file " . @$search["type"], $result);
	return $result;
}


//ext => type
function getFileType($file, $checkExists=false)
{
	if($checkExists && !file_exists($file))	return false;

	if(fileIsDir($file))	return "DIR";

	$fileExt=strtolower(getFilenameExtension($file));
	$types=getConfig("TYPES");
	foreach ($types as $type => $exts)
		if($fileExt === $exts || is_array($exts) && in_array($fileExt, $exts))
			return $type;

	return "FILE";
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

function sortDirs($relPath, $dirs, $sort)
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
	$search["name"]="$name*";
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
		$name = substringBeforeLast($file, ".");
		if($prev != $name)
			$distinct[$name] = $name;
		$prev = $name;
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

	//return $distinct;

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
	$relPathG = $relPath;
	$filteredList = array_filter($fileList, "fileIsDir");
	return $filteredList;
}

function fileIsDir($file)
{
	global $relPathG;
	$dirPath = combine($relPathG, $file);
//debug ("fileIsDir $file / $dirPath", @is_dir($dirPath));
	return is_dir($file) || @is_dir($dirPath);
}

function selectFilesByType($fileList, $ext, $sort=false)
{
	if(empty($ext))
		return $fileList;	
	$search = array("type" => $ext);
	$fileList = filterFiles($fileList, $search);
	if($sort)
		asort($fileList);
	return $fileList;
}

//type => exts
//todo: multiple types, split by | or , or ' '
function getExtensionsForTypes($exts)
{
	if(!isset($exts) || !$exts)	return "";
debug("getExtensionsForTypes", $exts);
	$exts=toArray($exts);
	$result=array();
	foreach ($exts as $ext)
		$result=array_merge($result, getExtensionsForType($ext));
debug("getExtensionsForTypes", $result);
	return $result;
}

function getExtensionsForType($ext)
{
	$type=strtoupper($ext);
	if($type=="DIR" || $type=="FILE") return array();
	$exts = getConfig("TYPES.$type");
	$result = $exts ? $exts : $ext;
debug("getExtensionsForTypes $ext", $result);
	return arrayToMap($result);
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
	if($files && $valuesAsKeys)
		$files = array_combine($files, $files);
    return $files;
}

function listHiddenFilesNTFS($dir, $valuesAsKeys=false)
{	
	if(PHP_OS != "WINNT") return array();
	return listFilesNTFS($dir, "/AH", $valuesAsKeys);
}

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

function isPasswordProtected($relPath)
{

	$htaccess = findInParent($relPath,".htaccess");
debug(".htaccess", $htaccess);
	if(!$htaccess) return false;
	//return true;

	$htaccess = readTextFile($htaccess);
	return contains($htaccess, 'PerlSetVar AuthFile "');
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
function findFilesInParent($relPath, $file, $getOther=false, $maxCount=null, $appendPath=true)
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

//find .htaccess
function findFile($relPath, $filename, $depth=10)
{
	$search = array("file" => $filename, "depth" => $depth); //, "count" => 2 * $max_thumbs);
	$files = listFilesRecursive($relPath, $search);
	return $files;
}

//pick random 4 thumbs in this dir
function subdirThumbs($relPath, $max_thumbs, $depth=0)
{
	$search = array("type" => "IMAGE", "depth" => $depth, "tndir" => ".tn"); //, "count" => 2 * $max_thumbs);
	$pics = listFilesDir($relPath, $search);
	if(!$pics && $depth)
		$pics = listFilesRecursive($relPath, $search);
	$pics = pickRandomElements($pics, $max_thumbs);
	return $pics;
}

function findFirstImage($relPath, $search=null)
{
	$pics = findFirstImages($relPath, 1, $search);
	if($pics)
		return end($pics);
}

function findFirstImages($relPath, $maxCount=1, $search=null)
{
	if(!$search)
		$search = array("depth" => 1);
	$search["type"] = "IMAGE";
	if(@$search["start"])
		$search["name"] = $search["start"];
	$search["count"] = $maxCount;
	$search["tndir"] = ".ss";
	$pics=listFilesRecursive($relPath, $search);
	if(!$pics)
	{
		$search["tndir"] = ".tn";
		$pics=listFilesRecursive($relPath, $search);
	}
	if(!$pics)
	{
		unset($search["tndir"]);
		$pics=listFilesRecursive($relPath, $search);
	}
	debug("findFirstImages", $pics);
	return $pics;
}

//pick random 4 thumbs in this dir
function pickRandomElements($array, $nb)
{
	shuffle($array);
	return array_slice($array, 0, $nb);
}
?>