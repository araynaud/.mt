<?php 

// dir listing functions with readdir
function listAllFilesInDir($dir)
{
	$files=array();
	if (!is_dir($dir)) return $files;

	$handle=opendir($dir);	
	while(($file = readdir($handle))!==false)
		$files[$file] = $file;
	closedir($handle);
	return $files;
}

function listAllFiles($dir, $maxCount=0)
{
	$search=array("maxCount"=>$maxCount);
	return listFiles($dir, $search);
}

//if $type=DIR: list subdirs only
//if $type=FILE: list files without subdirs
//0 or false: this dir only, +N into subdirs N levels, -N into parents N levels
//true: no depth limit, recurse in all subdirs
//$sub: subfolder to list (ex: .tn, .ss)
//$search =  array( "name"=>"","type"=>"","depth"=>0,"maxCount"=>0,"tnDir"=>"");

function listFiles($dir,$search=array(),$subPath="",$remaining=null,$recurse=null)
{
	global $config;
	
	$files=array();
	if (!is_dir($dir))	return $files;
	//init recursive variables from search array
	setIfNull($remaining,@$search["maxCount"]);
	setIfNull($recurse,@$search["depth"]);	
	if(!isset($search["exts"]) && isset($search["type"]))
		$search["exts"]=getExtensionsForTypes(@$search["type"]);

	//search 1 exact filename
	if(isset($search["file"]))
	{
		$file=$search["file"];
		if(file_exists(combine($dir,$file)))
			$files[]=combine($subPath,$file);
	}
	else
	{
		global $ignoreList, $relPathG;

		parseWildcards($search);
		$handle=opendir($dir);	
		if(!$handle)
			return $files;

		loadIgnoreList($dir);	//load from .ignore.txt file only once
		$relPathG=$dir;
			
		$subdirs=array();
		while(($file = readdir($handle))!==false)
		{
			//filter by files: works if dir sorted by name and case sensitive
			// if($first && $file < $first) continue;
			// if($last && $file > $last && !startsWith($file,$last) ) break; 
			//pass function with condition to filter file before adding to array		

			if(ignoreFile($file)) continue;
			if($recurse && fileIsDir($file))
				$subdirs[$file] = $file;

			splitFilename($file,$key,$ext);

			if(in_array($ext, @$config["TYPES"]["SPECIAL"])) continue;

//			if(fileHasType($file, "SPECIAL")) continue;
				
			if(!fileHasNameAndType($file,@$search["name"],@$search["exts"],@$search["starts"],@$search["ends"])) continue;
//use name as key
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
		$parentFiles=listFiles($newDir,$search,combine($subPath,".."),$remaining,$recurse);
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
			$subdirFiles=listFiles($newDir,$search,combine($subPath,$subdir),$remaining,$recurse);
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
	$ignoreList=readArray("$dir/.ignore.txt");
	return $ignoreList;
}

//function with condition to exclude file before adding it to array
function ignoreFile($file)
{
	global $config, $ignoreList;
	//always ignore $specialDirs and file names and types
	if(in_array($file, @$config["SPECIAL_FILES"])) return true;

	//list hidden and ignored files for admin
	if(is_admin()) return false;
	if(in_array($file, $ignoreList)) return true;
	return fileIsHidden($file);
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

function groupByName($files, $byType=false)
{
	$distinct=array();
	foreach ($files as $file)
	{
		//split subdir/file.ext
		splitFilePath($file,$subdir,$filename);
		splitFilename($filename,$name,$ext);
		$type = getFileType($file);
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
			$element["subdir"] = $subdir;
			$element["name"] = $name;
			$element["exts"] = array();
			//if(!$byType)
			$element["type"] = $type;
		}
		$element["exts"][]=$ext;

		if($byType) 
			$distinct[$type][$key] = $element;
		else
			$distinct[$key] = $element;
	}
	return $distinct;
}

//filter array of filenames
function removeIgnoredFiles($relPath,$files)
{
	return excludeFiles($files,loadIgnoreList($relPath));
}

function excludeFiles($files,$list)
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

//ext => type
function getFileType($file)
{
	global $config;
	if(fileIsDir($file))
		return "DIR";

	$fileExt=strtolower(getFilenameExtension($file));
	foreach ($config["TYPES"] as $type => $ext)
		if(arraySearchRecursive($ext, $fileExt)!==false) 
			return $type;

	return "FILE";
}

function fileHasType($file,$ext="")
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
	$start = $start ? "^"  : ""; //starts with name
	$end   = $end   ? "\." : "";  //finishes with name before .extension 
	$regex="/($start$name$end)/i";
//debug("fileHasName regex", $regex);
	return preg_match($regex,$file);
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
		
	$search["depth"]=-10;
	$search["maxCount"]=$maxCount; // find only first one
	$found=@listFiles($relPath,$search);
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

function pickRandomElements($array,$nb)
{
	shuffle($array);
	return array_slice($array, 0, $nb);
}
?>