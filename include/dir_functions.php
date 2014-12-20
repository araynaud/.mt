<?php 

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
	$search = array("maxCount"=>$maxCount);
	return listFiles($dir, $search);
}

function getFileByName($dir, $name)
{
	$search = array("name" => $name);
	return listFiles($dir, $search);
}

function fileExistsByName($dir, $name)
{
	$search = array("name" => $name, "maxCount"=>1);
	$list = listFiles($dir, $search);
	return count($list) ? reset($list) : false;
}


//if $type=DIR: list subdirs only
//if $type=FILE: list files without subdirs
//0 or false: this dir only, +N into subdirs N levels, -N into parents N levels
//true: no depth limit, recurse in all subdirs
//$sub: subfolder to list (ex: .tn, .ss)
//$search =  array( "name"=>"","type"=>"","depth"=>0,"maxCount"=>0,"tnDir"=>"");

function listFiles($dir, $search=array(), $subPath="", $remaining=null, $recurse=null)
{
	global $config;
	
	$files=array();
	if (!is_dir($dir))	return $files;

	//init recursive variables from search array
	$subdirs=array();
	setIfNull($remaining, @$search["maxCount"]);
	setIfNull($recurse, @$search["depth"]);	
	if(!isset($search["exts"]) && isset($search["type"]))
		$search["exts"]=getExtensionsForTypes(@$search["type"]);

	if(!@$search["tagfiles"])
	{
		$search["tagfiles"] = searchTagFiles($dir, $recurse, @$search["tag"]);
		$cnt=count($search["tagfiles"]);
		debug("listFiles: files matched by tags ($cnt)", $search["tagfiles"], true);
	}

debug("listFiles $dir", $search);
debug("subpath", $subPath);

	//search 1 exact filename
	if(isset($search["file"]))
	{
		$file=$search["file"];
		if(file_exists(combine($dir,$file)))
			$files[]=combine($subPath,$file);
	}
	else
	{
		global $relPathG;
		$handle=opendir($dir);	
		if(!$handle)	return $files;

		$relPathG=$dir;
		loadIgnoreList($dir);	//load from .ignore.txt file only once

		while(($file = readdir($handle))!==false)
		{
			//filter by files: works if dir sorted by name and case sensitive
			// if($first && $file < $first) continue;
			// if($last && $file > $last && !startsWith($file,$last) ) break; 
			//pass function with condition to filter file before adding to array		
			if(ignoreFile($file)) continue;

			if($recurse && fileIsDir("$file"))
			{
				$subdirs[$file] = $file;			
				debug("fileIsDir $file", fileIsDir("$file"));
			}

			splitFilename($file, $key, $ext);

			$key=combine($subPath, $key);
			$hasNameAndType = fileMatches($file, $key, $search);
			if(isset($search["exts"]) && !$hasNameAndType && in_array($ext, @$config["TYPES"]["SPECIAL"])) continue;

			if(!$hasNameAndType) continue;

			$key=combine($subPath,$file);

//debug("splitFilename", "file=$file / key=$key / ext=$ext");
			if(@$search["tnDir"])
			{	
				$thumb=findThumbnail($dir, $file, $search["tnDir"], false);
				if($thumb)
					$files[$key] = combine($subPath,$search["tnDir"],$thumb);
			}
			else
				$files[$key] = $key; //combine($subPath,$file);

			if(count($files)==$remaining) break;
		}
		closedir($handle);
	}
	$relPathG=null;
//debug("keys", array_keys($files));
//debug("recurse",$recurse);	
//debug("subdirs",$subdirs);	

//if $subdir:
//1 list dirs from $relPath to recurse
//2 list files from $relPath/$subdir
	if(@$search["subdir"])
	{
debug("files in $dir", $files);		
debug("subdir", @$search["subdir"]);	
		$subdir = $search["subdir"];
		$newDir = combine($dir, @$search["subdir"]);
		unset($search["subdir"]);
		$files=listFiles($newDir, $search, combine($subPath, @$search["subdir"]), $remaining, 0);
debug("list files in $newDir", $files);		
		$search["subdir"]=$subdir;
	}

	if($recurse==0 || $remaining>0 && count($files)==$remaining)
		return $files;

	//recursion in parent dir
	if($recurse < 0 && $dir!=pathToDataRoot())
	{
		$newDir=getParent($dir);
		if($remaining)
		{
			if(count($files) >= $remaining) break;
			$remaining -= count($files);
		}
		$recurse++;
		$parentFiles = listFiles($newDir, $search, combine($subPath,".."), $remaining, $recurse);
		if($parentFiles)
			$files=array_merge($files,$parentFiles);
	}
	//recursion in subdirs
	//if maxcount, split results in subdirs, pick random subdir
	else if($recurse > 0 && $subdirs)
	{
		if(is_numeric($recurse)) $recurse--;
		$nbDirs=max(count($subdirs),1);
		if(@$search["tnDir"])
			shuffle($subdirs);

		if($remaining > 0)
			$remaining -= count($files);
			
		foreach($subdirs as $subdir)
		{
			$newDir=combine($dir,$subdir);
			$nb = ($remaining == 0) ? 0 : max(floor($remaining/$nbDirs), 1);
			$subdirFiles = listFiles($newDir, $search, combine($subPath,$subdir), $nb, $recurse);
			if(!$subdirFiles) continue;
			
			$files=array_merge($files,$subdirFiles); //1 array
			//if enough files, do not look into other subdirs
			if($remaining > 0)
			{	
				if(count($subdirFiles) >= $remaining) break; 
				$remaining -= count($subdirFiles);
			}
		}
	}
	return $files;
}


function fileMatches($file, $key, $search)
{
	$hasType = fileHasType($file, @$search["exts"]);
	if(!@$search["name"] && !@$search["tag"]) return $hasType;

	$hasName = $search["name"] && fileHasName($file, @$search["name"] , @$search["start"], @$search["end"]);
	$hasTag = $search["tagfiles"] && array_key_exists($key, $search["tagfiles"]);
	$result = ($hasName || $hasTag) && $hasType;
	if($result)
		debug("fileMatches $file $key", "name:$hasName | tag:$hasTag & type:$hasType = $result");
	return $result;
}

function getSearchParameters()
{
	$search = array();		
	$search["type"]=reqParam("type");
	$search["name"]=reqParam("name");
	$search["tag"]=reqParam("tag");
	$search["sort"]=reqParam("sort");
	$search["depth"]=reqParam("depth",0);
	$search["subdir"]=reqParam("subdir");
	$search["maxCount"]=reqParam("count",0);
	$search["config"]=reqParamBoolean("config",true);
	parseWildcards($search);

debug("getSearchParameters",$search);
	return $search;
}

function parseWildcards(&$search)
{
	$name = @$search["name"];
	if(!$name) return;

	$search["starts"]=!startsWith($name, "*");
	if(!$search["starts"])
		$name=substr($name, 1);
	if(!$name) return;

	$search["ends"]=!endsWith($name, "*");
	if(!$search["ends"])
		$name=substr($name, 0, strlen($name)-1);
	$search["name"] = $name;
debug("parseWildcards",$search);
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
	global $config, $ignoreList, $relPathG;
	//always ignore $specialDirs and file names and types
	if(isSpecialFile($file)) return true;
	//list hidden and ignored files for admin
	if(is_admin()) return false;
	if(isset($ignoreList[$file])) return true;

	return fileIsHidden($file);
}

function isSpecialFile($file)
{
	global $config;
	$specialFiles = @$config["SPECIAL_FILES"];
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
	$search["maxCount"]=$maxCount; // find only first one
	return listFiles($relPath,$search);
}

//see if a dir has at least one files of listed type
//if yes, find 1st file of type
function hasFiles($relPath,$type="",$recursive=false)
{
	$search =  array();
	$search["type"]=$type;
	$search["depth"]=$recursive;
	$search["maxCount"]=1; // find only first one
	return listFiles($relPath,$search);
}

function listSubdirs($relPath,$recursive=0)
{
	$search =  array();
	$search["type"]="DIR";
	$search["depth"]=$recursive;
	//$search["maxCount"]=1; // find only first one
	return listFiles($relPath,$search);
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
function selectDirs($relPath,$fileList)
{
	global $relPathG;
	$relPathG=$relPath;
	$filteredList = array_filter($fileList,"fileIsDir");
	return $filteredList;
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
//debug("=>",$result);
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

function fileIsDir($file)
{
	global $relPathG;
	return is_dir($file) || is_dir(combine($relPathG,$file));
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
	global $extG, $config;
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

//filter by partial or complete name (without extension)
function fileHasName($file,$name="",$start=false,$end=false)
{
	global $nameG;
	setIfEmpty($name,$nameG);
	if(empty($name)) return true;

	$file=getFilename($file);

	$start = $start ? "^"  : ""; //starts with name
	$end   = $end   ? "$" : "";  //finishes with name before .extension 
	$regex="/($start$name$end)/i";
//debug("fileHasName regex", $regex);
	return preg_match($regex, $file);
}

function fileHasNameAndType($file,$name="",$ext="",$start=false,$end=false)
{
	$result=true;
	if($ext)	$result = fileHasType($file,$ext);	
	if($name)	$result = $result && fileHasName($file,$name,$start,$end);
	return $result;
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

//find folder.css in current path, or another css file
//if not found, look in parent directories
//returns relative link to FIRST FILE found, or empty if none found
//$getOther boolean: retrieve another file of the same type if file not found
function findInParent($relPath,$file,$getOther=false)
{
	$found=findFilesInParent($relPath,$file,$getOther, 1, true);
	return $found[0];
}

//find multiple files in parent
function findFilesInParent($relPath,$file,$getOther=false, $maxCount=0, $appendPath=true)
{
	$search =  array();
	if(!$getOther)
		$search["file"]=$file;
	else
		$search["type"]=getFilenameExtension($file);
		
	$search["depth"] = -10; // TODO, use depth of path?
	$search["maxCount"] = $maxCount; // find only first one
	$found = listFiles($relPath,$search);
	if(!$found) 
		return false;

	$found=array_values($found);
	if($appendPath)
		for($i=0; $i<count($found); $i++)
			$found[$i]=combine($relPath,$found[$i]);

	return $found;
}

//pick random 4 thumbs in this dir
function subdirThumbs($relPath,$max_thumbs)
{
	$search = array();
	$search["type"]="IMAGE|VIDEO";
	$search["depth"]=2;
	$search["maxCount"]=2*$max_thumbs;
	$search["tnDir"]=".tn";

	$pics=listFiles($relPath,$search);
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
	$search = array();
	$search["type"]="IMAGE";
	$search["maxCount"]=$maxCount;
	$search["depth"]=1;
	$search["tnDir"]=".ss";
	$pics=listFiles($relPath,$search);
	if(!$pics)
	{
		$search["tnDir"]=".tn";
		$pics=listFiles($relPath,$search);
	}
	if(!$pics)
	{
		unset($search["tnDir"]);
		$pics=listFiles($relPath,$search);
	}
	debug("findFirstImage", $pics);
	return $pics;
}

//pick random 4 thumbs in this dir
function pickRandomElements($array,$nb)
{
	shuffle($array);
	return array_slice($array, 0, $nb);
}
?>