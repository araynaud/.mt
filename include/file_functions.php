<?php 
//splits file into $name.$ext
function splitFilename($file,&$name,&$ext)
{	
	$len = strrpos($file,'.');
	//if no dot: ext empty, name=file
	if($len==false)
	{
		$ext="";
		$name=$file;
		return $len;
	}	
	$ext = strrchr($file,'.');
	$ext=substr($ext,1);
	if(contains($ext,':'))
		$ext=explode(':',$ext);
	$name=substr($file,0,$len);
	return true;
}

//split file and parent path
function splitFilePath($file,&$parent,&$filename)
{	
	$len = strrpos($file,'/');
	//if no slash: parent empty, filename=file
	if($len===false)
	{
		$parent="";
		$filename=$file;
		return $len;
	}	
	$filename = strrchr($file,'/');
	$filename=substr($filename,1);
	$parent=substr($file,0,$len);
	return true;
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
function readArray($filename)
{
	if(!file_exists($filename) || filesize($filename)==0 || is_dir($filename))
		return array();
	$lineArray=file($filename, FILE_SKIP_EMPTY_LINES);
	//remove windows CR/LF  from each line
	for($i=0; $i<count($lineArray); $i++)
	{
		$lineArray[$i]=rtrim($lineArray[$i]);
	}
	//$trim? 
	//$separators: split words separated by space, \t ?
	return $lineArray;
}

// make an array from config files
// in .mp/config, then data root, then subdirs to current path
function LoadConfiguration($path=null, &$configData = array())
{
	$relPath=getRelPath($path);

	$appRootDir=pathToAppRoot();
//1 default config in .mp/config
	$configFilename=combine($appRootDir,"config",".config.csv");
	$configData=readCsvFile($configFilename, 0, ";", ".");

	$configFilename=combine($appRootDir,"config",".config.mapping.csv");
	readCsvFile($configFilename, 0, ";", ".", $configData);

//2 default config by path depth
	$depth=pathDepth($path);
	$configFilename=combine($appRootDir,"config",".config.$depth.csv");
	readCsvFile($configFilename, 0, ";", ".", $configData);

//3 supersede values with folder specific config file in $relPath 
// find in parents and load from root to current dir
	if($relPath)
		$configFilenames=findFilesInParent($relPath,".config.csv");
	if($configFilenames) 
	{
		sort($configFilenames);
		foreach($configFilenames as $configFilename)
			readCsvFile($configFilename, 0, ";", ".", $configData);
	}
//debug("2: $configFilename", $configData);

//4 supersede values with device specific config file in appRoot 
	$devices = checkUserAgent();
	if(is_array($devices))
		foreach($devices as $dev)
		{
			$configFilename=combine($appRootDir,"config",".config.$dev.csv");
			readCsvFile($configFilename, 0, ";", ".", $configData);
		}

//finally add some keys to output

	$configData["SPECIAL_FILES"] = readArray(combine($appRootDir, $configData["SPECIAL_FILES"]));
	$configData["ENABLE_FFMPEG"] = isFfmpegEnabled();

	$configData["thumbnails"]["dirs"] = array_keys($configData["thumbnails"]["sizes"]);
//	config.thumbnails.dirs=Object.keys(config.thumbnails.sizes);


//output config for default site
	$publish = $configData["_publish"];
   	$site = $publish["default"];
	$configData["publish"] = $publish[$site];

	return $configData;
}

//config for the current dir only
function getDirConfig($path, $key=null)
{
	$relPath=getRelPath($path);
	$depth=pathDepth($path);

//1 default config by path depth
	$appRootDir=pathToAppRoot();
	$configFilename=combine($appRootDir,"config",".config.$depth.csv");
	$configData = readCsvFile($configFilename, 0, ";", ".");

//2 supersede values with folder specific config file in $relPath 
	$configFilename=combine($relPath,".config.csv");
	readCsvFile($configFilename, 0, ";", ".", $configData);
	if(!isset($key)) return $configData;
	
	return isset($configData[$key]) ? $configData[$key] : "";
}

//$filename: csv file to open
//$keyColumn: index of column to use as key
//$separator: column separator character
//$csvRows = optional array to append/merge
//return csv data as array
function readCsvFile($filename, $keyColumn=false, $separator=",", $keySeparator=false, &$csvRows = array())
{
	debug("readCsvFile",$filename);
	if(!file_exists($filename))
		return $csvRows;
	if (($handle = fopen($filename, "r")) == FALSE)
		return $csvRows;

	$prevKey=null;
	while (($rowData = fgetcsv($handle, 0, $separator)) !== FALSE)  
	{
		for($i=0;$i<count($rowData);$i++)
			$rowData[$i]=parseValue($rowData[$i]);
		if($keyColumn===false)
		{
			$csvRows[] = $rowData;
			continue;
		}
		$key=trim($rowData[$keyColumn]);
		if($key==="")
			continue;
		unset($rowData[$keyColumn]);
		$rowData=array_values($rowData);
		//value = single value or array?
		if(is_array($rowData) && count($rowData)==1)
			$rowData = $rowData[0];
		
		if($keySeparator)
			$key=getKeyArray($key,$prevKey,$keySeparator);
		setNestedArrayValue($csvRows,$key,$rowData);
		$prevKey=$key;
	}
	fclose($handle);
	return $csvRows;
}

function getKeyArray($key, $prevKey=null, $keySeparator=".")
{
	if(!is_array($key))	$key=explode($keySeparator, $key);
	if(!$prevKey) return $key;
	if(!is_array($prevKey))	$prevKey=explode($keySeparator, $prevKey);
	//if empty element in the key: take it from previous key
	$c=count($key);
	for($i=0;$i<$c;$i++)
		if($key[$i]==="" && isset($prevKey[$i]))
			$key[$i]=$prevKey[$i];
	return $key;
}

//create array values within CSV data
// TYPES.VIDEO.STREAM;flv;mp4	=> $csv["TYPES"]["VIDEO"]["STREAM"] = ["flv", "mp4"]
// TODO: keep previous key
// ..STREAM;flv;mp4	=> $csv["TYPES"]["VIDEO"]["STREAM"] = ["flv", "mp4"]
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
function readCsvTableFile($filename, $separator=";", &$csvRows = array())
{
	debug("readCsvTableFile",$filename);
	if(!file_exists($filename))
		return $csvRows;
	if (($handle = fopen($filename, "r")) == FALSE)
		return $csvRows;

	$header = fgetcsv($handle, 0, $separator);
	if ($header === FALSE)
	{
		fclose($handle);
		return $csvRows;
	}

	while (($rowData = fgetcsv($handle, 0, $separator)) !== FALSE)  
	{
		$row = array();
//debug("row", count($csvRows) . ": " . count($header) . " / " . count($rowData) . " " . $rowData[0] );		
		for($i=0;$i<count($rowData);$i++)
		{
			$key = isset($header[$i]) ? $header[$i] : $i;
			$value = parseValue($rowData[$i]);
			$row[$key]= $value;
		}
		$csvRows[] = $row;
	}
	fclose($handle);
	return $csvRows;
}

function deleteFile($filename)
{
	if(file_exists($filename))
		return unlink($filename);
	return false;
}

function writeBinaryFile($filename, $data, $append=false)
{
	if(!$data && !$append)	return deleteFile($filename);
	debug("writeBinaryFile file",$filename);
	debug("writeBinaryFile length",count($data));
	$mode = $append ? "ab" : "w";
	$fh = fopen($filename, $mode);
	if(!$fh) return;
	fwrite($fh, $data);
	fclose($fh);
	debug("exists",file_exists($filename));
}

function writeTextFile($filename, $text, $append=false)
{
	if(!$text) 	return deleteFile($filename);

	$mode = $append ? "ab" : "w";
	$fh = fopen($filename, $mode); 
	if(!$fh) return;
	$text = str_replace("\'", "'", $text);
	fwrite($fh, $text);
	fclose($fh);
}

function writeCsvFile($filename, $data, $includeEmpty=false, $separator=";")
{
debug("writeCsvFile",$data);
	if(!$data) return deleteFile($filename);
	if(is_string($data)) return	writeTextFile($filename,$data);
	if(!is_array($data)) return	writeBinaryFile($filename,$data);

	$fh = fopen($filename, 'w');
	if(!$fh) return;

	$csv = csvValue($data, $includeEmpty, $separator);
	fwrite($fh, $csv);
	fclose($fh);
	return;
}

function copyRedirect($relPath)
{
	//copy redirect.htm to $relPath/index.htm
	if(!file_exists("$relPath/index.php") && !file_exists("$relPath/index.htm")
	&& (!file_exists("$relPath/index.html")	|| filemtime("$relPath/index.html") < filemtime("redirect.html") ))
		copy("redirect.html", "$relPath/index.html");
}

function getFileDate($filename)
{
	//Get most recent file for a directory
	if (is_dir($filename))	return getNewestFileDate($filename);

	//or Get EXIF data for an image
	$date=getExifDateTaken($filename);
	if(!empty($date)) return $date;// ."EX";

	//or Get date from filename
	$date = getDateFromFilename($filename);
	if(!empty($date)) return $date;// ."FN";

	//or Get modified date
	return formatFilemtime($filename);// ."FM";
}

function parseDateFromFilename($filename)
{
	$filename=getFilename($filename);
	$sep="[-_:]?";
//16 105_10151545228463135_1547522270_n	
	$dateRegex="(1[0-9]{3}|2[0-9]{3})$sep(0[1-9]|1[012])$sep(0[1-9]|[12][0-9]|3[01])";  //yyyy-mm-dd or yyyymmdd
	$timeRegex="(0[1-9]|1[0-9]|2[0-3])$sep([0-5][0-9])$sep([0-5][0-9])";
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

function formatDate($mtime)
{
	if(!$mtime) return "";
	return date("Y-m-d H:i:s", $mtime);
}


//move file and associated files: other versions .tn, .ss
// and create target directory if necessary
function moveFile($relPath,$file,$relTarget,$newName="")
{
	if(empty($file) || !file_exists("$relPath/$file"))
		return false;

	//create target dir if it does not exist
	if (!is_dir($relTarget))
		mkdir ($relTarget, 0700);

	return rename("$relPath/$file","$relTarget/$file");
}


//move file and associated files: other versions, description, .tn, .ss
function moveMediaFile($relPath,$file,$relTarget,$newName="")
{
	splitFilename($file,$name,$ext);
	//original file	
	$result=moveFile("$relPath","$file","$relTarget");
	//TODO: other versions: use searchFiles("nameStart")
	moveFile($relPath,"$name.txt",$relTarget);				//description
	moveFile("$relPath/.ss",$file,"$relTarget/.ss");		//slide show
	moveFile("$relPath/.tn",$file,"$relTarget/.tn");		//thumbnail
	moveFile("$relPath/.tn","$name.jpg","$relTarget/.tn");	//thumbnail for video
	
	return $result;
}


function deleteMediaFile($relPath,$file)
{
	if(is_dir("$relPath/$file"))
		return rmdir ("$relPath/$file");

	//for files
	splitFilename($file,$name,$ext);
	//original file
	$result=unlink("$relPath/$file");
	//TODO: other versions

	//slide show version
	deleteFile("$relPath/.ss/$file");
	//TODO: test if other versions
	//delete thumbnail and description if no other version of the file
	deleteFile("$relPath/.tn/$file");
	deleteFile("$relPath/.tn/$name.jpg");
	deleteFile("$relPath/.tn/$name.csv");
	deleteFile("$relPath/$name.txt");
	
	return $result;
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
	return mkdir($outputDir, 0700);
}
?>