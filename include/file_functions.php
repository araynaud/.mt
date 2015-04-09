<?php 
//splits file into $name.$ext
function splitFilename($file,&$name,&$ext)
{	
	$arr = splitBeforeAfter($file, ".", true);
	$name = $arr[0];
	$ext = $arr[1];
	return !empty($ext);
}

//split file and parent path
function splitFilePath($file,&$parent,&$filename)
{	
	$arr = splitBeforeAfter($file, "/", true);
	$parent = $arr[0];
	$filename = $arr[1];
	if(!$filename) 
	{	
		$filename = $parent;
		$parent = "";
	}
	return !empty($parent);
}

function getFilenameExtension($file)
{	
	return substringAfterLast($file,".");
}

//add or replace file extension
function getFilename($file, $ext="", $append=false)
{	
	if(!$append)
		$file=substringBeforeLast($file,".");
	return $ext ? "$file.$ext" : $file;
}

//key : filename without extension and special characters
function getFileId($filename, $type="")
{
	$id =  preg_replace("/[\.#\(\)\{\}' -]/", "_", $filename);
	if(!$type)	return $id;
	return $type . "_$id";
};

function readTextFile($filename)
{
	if(!file_exists($filename) || filesize($filename)==0 || is_dir($filename))
		return "";

	return file_get_contents($filename);
}


function splitChunks($relPath, $filename, $chunkSize, &$chunks=array())
{
	$filePath = combine($relPath, $filename);
	if(!file_exists($filePath)) return 0;
	if(!$chunkSize) return 1;
	$filesize = filesize($filePath);
	$nbChunks = ceil($filesize / $chunkSize);
	//no need to split
	if($nbChunks <= 1) return $nbChunks;

	$n=0;
	$fp = fopen($filePath, "rb");
	$chunks=array();
	while(++$n <= $nbChunks)
	{
		$chunk = fread($fp, $chunkSize);
		$chunkName = getChunkName($filename, $n, $nbChunks);
		$chunks[]=$chunkName;
		$chunkPath = combine($relPath, $chunkName);
		file_put_contents($chunkPath, $chunk);
		debug("splitChunks $chunkName", filesize($chunkPath));
	}
	fclose($fp);
	return $nbChunks;
}

function getChunkName($filename, $n , $nbChunks="")
{
	if($n == $nbChunks) $n="last";
	return "$filename.$n.chunk";
}

function joinChunks($relPath, $filename, $delete=true)
{
	$n=1;
	$chunkName = getChunkName($filename, $n);
	$chunkPath = combine($relPath, $chunkName);
	if(!file_exists($chunkPath)) return 0;

	$filePath = combine($relPath, $filename);
	$outputFilename = $filePath; //file_exists($filePath) ? "$filePath.join" : $filePath;
	$fp = fopen($outputFilename, "w");
	while(file_exists($chunkPath))
	{
		$chunk = file_get_contents($chunkPath);
		fwrite($fp, $chunk);
		debug("joinChunks $chunkName", filesize($chunkPath));
		if($delete)
			deleteFile($chunkPath);
		$n++;
		$chunkName = getChunkName($filename,$n);
		$chunkPath = combine($relPath, $chunkName);
	}

	$chunkName = getChunkName($filename, "last");
	$chunkPath = combine($relPath, $chunkName);
	$chunk = file_get_contents($chunkPath);
	fwrite($fp, $chunk);
	debug("joinChunks $chunkName", filesize($chunkPath));
	if($delete)
		deleteFile($chunkPath);

	debug("joinChunks $outputFilename", filesize($outputFilename));
	fclose($fp);
	return $n;
}

function deleteChunks($relPath,$filename)
{
	$n=1;
	$chunkName = getChunkName($filename,$n);
	$chunkPath = combine($relPath, $chunkName);
	while(file_exists($chunkName))
	{
		debug("deleteChunks $chunkName", filesize($chunkPath));
		deleteFile($chunkPath);
		$n++;
		$chunkName = getChunkName($filename,$n);
		$chunkPath = combine($relPath, $chunkName);
	}

	$chunkName = getChunkName($filename, "last");
	$chunkPath = combine($relPath, $chunkName);
	debug("deleteChunks $chunkName", filesize($chunkPath));
	deleteFile($chunkPath);
	return $n;
}

// Read array of lines
function readArray($filename, $valuesAsKeys=false)
{
	if(!file_exists($filename) || filesize($filename)==0 || is_dir($filename))
		return array();
	//remove windows CR/LF  from each line
	$lineArray = file($filename, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
	if($lineArray && $valuesAsKeys)
		return array_combine($lineArray, $lineArray);
	return $lineArray;
}

// make an array from config files
// in .mp/config, then data root, then subdirs to current path
function LoadConfiguration($path=null, &$configData = array())
{
	$appRootDir = pathToAppRoot();
//1 default config in .mp/config
	$configFilename = combine($appRootDir, "config", ".config.csv");
	$configData = readConfigFile($configFilename); 

//2 site root config: should contain directory mappings.
	$configFilename = combine(pathToDataRoot(), ".config.csv");
	readConfigFile($configFilename, $configData);

//3 default config by path depth
	$depth=pathDepth($path);
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
//debug("2: $configFilename", $configData);

//5 supersede values with device specific config file in appRoot 
	$devices = checkUserAgent();
	if(is_array($devices))
		foreach($devices as $dev)
		{
			$configFilename = combine($appRootDir, "config", ".config.$dev.csv");
			readConfigFile($configFilename, $configData);
		}

//finally add some keys to output
	debug("SPECIAL_FILES", $configData["SPECIAL_FILES"]);
	$configData["ENABLE_FFMPEG"] = isFfmpegEnabled();
	$configData ["SITE_NAME"] = getSiteName();
	$configData["thumbnails"]["dirs"] = array_keys($configData["thumbnails"]["sizes"]);

//output config for default site
	$publish = getConfig("_publish");
	if($publish)
	{
		$site = $publish["default"];
		$configData["publish"] = $publish[$site];
	}
	return $configData;
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
	if(!isset($key)) return $configData;

//debug("getDirConfig", $configData);
	
	return isset($configData[$key]) ? $configData[$key] : "";
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

//$filename: csv file to open
//$keyColumn: index of column to use as key
//$separator: column separator character
//$csvRows = optional array to append/merge
//return csv data as array
function readCsvFile($filename, $keyColumn=false, $separator=",", $keySeparator=false, &$csvRows = array())
{
//	debug("readCsvFile",$filename);
	if(!file_exists($filename))
		return $csvRows;
	if (($handle = fopen($filename, "r")) == FALSE)
		return $csvRows;

	$prevKey=null;
	while (($rowData = fgetcsv($handle, 0, $separator)) !== FALSE)  
	{
		if($keyColumn===false)
		{
			for($i=0;$i<count($rowData);$i++)
				$rowData[$i]=parseValue($rowData[$i]);
			$csvRows[] = $rowData;
			continue;
		}

		//key: always a string
		$key = trim($rowData[$keyColumn]);
		if($key==="")	continue;
		//parse other columns
		unset($rowData[$keyColumn]);
		$rowData = array_values($rowData);
		foreach($rowData as $i => $column)
			$rowData[$i] = parseValue($rowData[$i]);

		//value = single value or array?
		if(is_array($rowData) && count($rowData)==1)
			$rowData = $rowData[0];
		
		if($keySeparator)
			$key=getKeyArray($key, $prevKey, $keySeparator);
		setNestedArrayValue($csvRows, $key, $rowData);
		$prevKey=$key;
	}
	fclose($handle);
	return $csvRows;
}

function getKeyArray($key, $prevKey=null, $keySeparator=".")
{
	if(!is_array($key))	
		$key = explode($keySeparator, $key);
	if(!$prevKey)
		return $key;
	if(!is_array($prevKey))
		$prevKey = explode($keySeparator, $prevKey);
	//if empty element in the key: take it from previous key
	$c=count($key);
	for($i = 0; $i < $c; $i++)
		if($key[$i]==="" && isset($prevKey[$i]))
			$key[$i] = $prevKey[$i];
	return $key;
}

//create array values within CSV data
// TYPES.VIDEO;flv;mp4	=> $csv["TYPES"]["VIDEO"] = ["flv", "mp4"]
// keep previous key: .STREAM;flv;mp4	=> $csv["TYPES"]["STREAM"] = ["flv", "mp4"]
function setNestedArrayValue(&$csvRows, $key, $value)
{
	if(!is_array($key))
	{
		$csvRows[$key]=$value;
		return $value;
	}
	
	//test if each level of key exists
	//create array(s) if necessary
	$var=&$csvRows;
	$c=count($key)-1;
	for($i=0;$i<$c;$i++)
	{
		$name=$key[$i];
		//or create nested array nodes
		if(!isset($var[$name]))
			$var[$name]=array();
		$var=&$var[$name]; //change reference
	}
	//last node: assign value
	$name=$key[$c];
	$var[$name] = $value;

	return $var[$name];
}

//read table with key names as first row
function readCsvTableFile($filename, $keyColumn=false, $columnNames=false, &$csvRows = array())
{
	$text = readTextFile($filename);
	if(!$text)		return $csvRows;
//debug("readCsvTableFile", $filename);
	return parseCsvTable($text, $keyColumn, $columnNames, $csvRows);
}

function parseCsvTable($text, $keyColumn=false, $columnNames=false, &$csvRows = array())
{
	$separator=";";
	$separator2="";
	if(!$text)		return $csvRows;
	$lines = splitLines($text);
	if(!$lines)		return $csvRows;
	if($columnNames && is_string($columnNames))
		array_unshift($lines, $columnNames);
//debug("parseCsvTable lines",$lines,"print_r");
	$header=array();
	$key="";
	if($columnNames)
	{
		if (!$lines[0])	return $csvRows;
		$header = explode($separator, $lines[0]);
		if($keyColumn!==false)
		{
			$key = $header[$keyColumn];
			unset($header[$keyColumn]);
		}
		array_shift($lines);
	}

	foreach ($lines as $n => $line)
	{
		if(!$line) continue;
		$rowData = explode($separator, $line);
		$row = array();
		$key="";
		if($keyColumn!==false)
		{
			$key = $rowData[$keyColumn];			
			unset($rowData[$keyColumn]);
		}
		foreach($rowData as $i => $column)
		{
			$ckey = isset($header[$i]) ? $header[$i] : $i;
			$value = parseValue($column, $separator2);
			$prev = @$row[$ckey];
			if(!$prev)
				$row[$ckey]= $value;
			else
			{
				if(!is_array($prev))
					$row[$ckey]= array($prev);
				$row[$ckey][]= $value;
			}
		}

		//value = single value or array?
		if(is_array($row) && count($row)==1)
			$row = array_shift($row);

		if(!$key)
			$csvRows[] = $row;
		else
			$csvRows[$key] = $row;
	}
	return $csvRows;
}

function writeBinaryFile($filename, $data, $append=false)
{
	if(!$data && !$append)	return deleteFile($filename);
	if(!$data)	return false;
	debug("writeBinaryFile file",$filename);
	debug("writeBinaryFile length",count($data));
	$mode = $append ? "ab" : "w";
	$fh = @fopen($filename, $mode);
	if(!$fh) return false;
	fwrite($fh, $data);
	fclose($fh);
	debug("exists",file_exists($filename));
	return true;
}

function writeTextFile($filename, $text, $append=false)
{
	if($text)
		$text = str_replace("\\'", "'", $text);
	return writeBinaryFile($filename, $text, $append);
}

function writeCsvFile($filename, $data, $includeEmpty=false, $separator=";")
{
	debug("writeCsvFile",$data);
	if(!$data) return deleteFile($filename);
	if(is_string($data)) return	writeTextFile($filename,$data);
	if(!is_array($data)) return	writeBinaryFile($filename,$data);

	$csv = csvValue($data, $includeEmpty, $separator);
	return writeTextFile($filename, $csv);
}

//sum: false if key = start time
//true: if key = duration
function readPlaylistFile($filename, $durations=false, $format=false)
{
	$columnNames = $durations ? "duration" : "start";
	$columnNames = "$columnNames;title";

	$playlist = readCsvTableFile($filename, false, $columnNames);
//	debug("readCsvTableFile", $playlist, true);
	$prevSec = 0;
	$end=0;
	$start=0;

	foreach ($playlist as $i => $item)
	{
		$seconds = parseTime(reset($item));
		$playlist[$i]["number"] = str_pad($i+1, 2, "0", STR_PAD_LEFT);
		$playlist[$i]["title"] = cleanupFilenameCmd($playlist[$i]["title"]);
		if($durations)
		{
			$duration = $seconds;
			$end += $seconds;
			$start = $end - $seconds;
			$playlist[$i]["duration"] = $format ? formatTime($duration) : $duration;
			$playlist[$i]["start"] = $format ? formatTime($start) : $start;
			$playlist[$i]["end"] = $format ? formatTime($start + $duration) : $start + $duration;
		}
		else
		{
			$start = $seconds;
			$duration = $seconds - $prevSec;
			$playlist[$i]["start"] = $format ? formatTime($seconds) : $seconds;
			if($i>0)
			{
				$playlist[$i-1]["duration"] = $format ? formatTime($duration) : $duration;
				$playlist[$i-1]["end"] = $format ? formatTime($seconds) : $seconds;
			}
		}
		$prevSec = $seconds;
		$i++;
	}

	return $playlist;
}



//write table data
//$data must be 2 dimensional array
function writeCsvTableFile($filename, $data, $columnNames=false, $writeKey="")
{
	if(!$data) return deleteFile($filename);
	$csv = toCsvTable($data, $columnNames, $writeKey);
	return writeTextFile($filename, $csv);
}

function toCsvTable($data, $columnNames=false, $writeKey="")
{
	$csv = "";
	$end="";
	if($columnNames)
	{
		$csv .= csvHeaderRow($data, $writeKey);
		$end = "\n";
	}

	foreach ($data as $key => $row)
		if($row)
		{
			$csv .= $end . csvDataRow($row, $writeKey ? $key : null);
			$end = "\n";
		}
	return $csv;
}

//write key names from 1st row
//TODO: union of keys in all rows
function csvHeaderRow($data, $writeKey="")
{
	$separator=";";
	$columns=array();
	if(!$data) return "";
	//get union of keys in all rows
	foreach ($data as $key => $row)
		if($row)
			$columns = arrayUnion($columns, $row);

	$k = array_keys($columns);
	if($writeKey)
		array_unshift($k, $writeKey);
	return implode($separator, $k);
}

function csvDataRow($row, $key)
{
	$separator=";";
	$separator2=":";
	if(!$row) return "";
	if($key)	array_unshift($row, $key);

	foreach ($row as $key => $value)
		$row[$key] = csvValue($value, false, $separator2);
//	debug("csvDataRow", $row);
	return implode($separator, $row);
}

function mergeCsv($filename, $data)
{
	//1 read CSV data
	//2 add/replace data rows by key
	//3 write csv.
}

function copyRedirect($relPath)
{
	if(!getConfig("COPY_REDIRECT")) return;
	//copy redirect.htm to $relPath/index.htm
	if(!file_exists("$relPath/index.php") && !file_exists("$relPath/index.htm")
	&& file_exists("redirect.html")
	&& (!file_exists("$relPath/index.html")	|| filemtime("$relPath/index.html") < filemtime("redirect.html")))
		@copy("redirect.html", "$relPath/index.html");
}

function setFileDate($file, $date)
{
	if($date)
		touch($file, strtotime($date));
}

function getFileDate($filename)
{
	$currentDate = formatDate(microtime(true));

	//Get most recent file for a directory
	if (is_dir($filename))	return getNewestFileDate($filename);

	//or Get EXIF data for an image
	$date=getExifDateTaken($filename);
	if(!empty($date) && $date < $currentDate) return $date;// ."FN";

	//or Get date from filename
	$date = getDateFromFilename($filename);
	if(!empty($date) && $date < $currentDate) return $date;// ."FN";

	//or Get file modified date
	return formatFilemtime($filename);// ."FM";
}

function parseDateFromFilename($filename)
{
	$filename=getFilename($filename);
	$sep="[-_:]?";
//16 105_10151545228463135_1547522270_n	
	$dateRegex="(1[0-9]{3}|2[0-9]{3})$sep(0[1-9]|1[012])$sep(0[1-9]|[12][0-9]|3[01])";  //yyyy-mm-dd or yyyymmdd
	$timeRegex="(0[0-9]|1[0-9]|2[0-3])$sep([0-5][0-9])$sep([0-5][0-9])";
	$dateTimeRegex="$dateRegex$sep$timeRegex";
	$status = preg_match("/$dateTimeRegex/",$filename,$matches);
//debug("$dateTimeRegex $filename",$matches);
	if($status) return $matches;
	$status = preg_match("/$dateRegex\$/",$filename,$matches);
//debug("$dateRegex $filename",$matches);
	return $matches;
}

function getDateFromFilename($filename)
{
	$matches=parseDateFromFilename($filename);
	if(empty($matches))
		return "";

	$date=array_shift($matches);
	//date
	$result=array_shift($matches) . "-" . array_shift($matches) . "-" . array_shift($matches);
	//time
	if(!empty($matches))
		$result.= " " . array_shift($matches) . ":" . array_shift($matches) . ":" . array_shift($matches);
	return $result;
}

function formatFilemtime($filename)
{
	if(!file_exists($filename))
		return;
	return formatDate(filemtime($filename));
}

function deleteFile($relPath, $file="")
{
	$file = combine($relPath, $file);
	$result = false;
	if(is_dir($file))
	{
		$result = deleteDir($file);
		debug("deleteDir", $result);
	}
	else if(file_exists($file))
	{
		$result = unlink($file);
		debug("deleteFile", $result);
	}
	return $result;
}

//rmdir always returns false on windows
function deleteDir($dir)
{
	if(!is_dir($dir)) return false;
	rmdir($dir);
	return true; //!file_exists($dir);
}

function delTree($dir) 
{
	$files = scandir($dir);
	$files = array_diff($files, array('.','..'));
    foreach ($files as $file)
    {
      $filePath = combine($dir, $file);
      if(is_dir($filePath))
      	delTree($filePath);
      else
     	deleteFile($filePath);
    }
    return deleteDir($dir);
} 

//move file and create target directory if necessary
function moveFile($relPath, $file, $relTarget, $newName="")
{
	$inputFile = combine($relPath, $file);
	if(empty($file) || !file_exists("$inputFile"))
		return false;

	splitFilePath($file, $subdir, $file);
	splitFilename($file, $name, $ext);
	$newName = $newName ? getFilename($newName, $ext) : $file;

	//create target dir if it does not exist
	createDir($relTarget, $subdir);
	$outputFile = combine($relTarget, $subdir, $file);
debug("moveFile $inputFile to", $outputFile);
	return rename($inputFile, $outputFile);
}

function createDir($relPath,$dir="")
{
	//create output folder if necessary
debug("createDir $dir in ",$relPath);
	$outputDir = combine($relPath, $dir);
	$ex=file_exists($outputDir);
	debug("file_exists $outputDir", $ex);
	if(file_exists($outputDir))
		return false;
	//TODO create parent subdirs if they do not exist, recursive
	$dir = dirname($dir);
	if($dir && $dir!=".")
		createDir($relPath,$dir);
	return @mkdir($outputDir, 0700);
}

function updateFileMetadata()
{
	//load MetaData file
	setNestedArrayValue($metadata, $key ,$rowData);
//save file
}

function findThumbnails($dir, $file, $appendPath=true)
{
	$sizes = getConfig("thumbnails.sizes");
	if(!$sizes) return false;
	$thumbnails = array();
	foreach($sizes as $tndir => $size)
		$thumbnails[$tndir] = findThumbnail($dir, $file, ".$tndir", $appendPath);

	return $thumbnails;
}

function findThumbnail($dir, $file, $tndir, $appendPath=true)
{
//for image, get .tn/image
	$thumb=combine($dir,$tndir,$file);
	if(file_exists($thumb))
		return $appendPath ? $thumb : $file;
//for video, get .tn/name.jpg
	$file=getFilename($file,"jpg");
	$thumb=combine($dir,$tndir,$file);
	if(file_exists($thumb))	
		return $appendPath ? $thumb : $file;
	return false;
}
?>