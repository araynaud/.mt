<?php
// media file, 1 per name
class Album extends BaseObject
{
    private $path;
    private $relPath;
    private $urlAbsPath;
	private $buildTime;
	private $times;
    private $title;
    private $user;
	private $private;
	private $search; //array of search criteria: types, name, depth, dates
    private $oldestDate;
    private $newestDate;
	private $description;

	private $nbFiles; //array of files in the directory
	private $nbDirs; //array of the subdirectories
	private $nbFilenames; //array of distinct names (without extensions)
    private $nbThumbnails; //array of thumbnails 
	private $files; //array of files in the directory
	private $dirs; //array of the subdirectories
	private $allFilenames; //array of distinct names (without extensions)
	private $groupedFiles; //array of files in the directory, grouped by type and name
    private $thumbnails; //array of thumbnails 
	private $metadata;

	private $mediaFiles; //array of MediaFile in the directory
	private $otherFiles; //array of MediaFileVersion thumbnail images in different subdirectories

	private $_dateIndex; // = array(); //array of date,filename entries
	private $_dateIndexEnabled;
	private $isCompleteIndex;
    private $config;
	private $tags;
	private $youtube; //array of files in the directory

    public function __construct($filters="", $details=false)
	{
		global $config;
debug("new Album", $filters);
debug("details", $details);
	
		if(is_array($filters))
		{
			$this->search = $filters;
			$this->path = $filters["path"];
		}
		else
		{
			$this->path = $filters;
			$filters = array("path" => $this->path);
		}

        $this->user = new User();
		$this->private = isPrivate($this->path);
		$this->getRelPath();
		$this->getAbsPath();
		$this->getTitle();
		$this->getDescription();

		//list files according to search, etc.		
		$this->getSearchParameters();
		$this->times=array();
		$this->times[]=getTimer(true);
		//list files according to search, etc.
		if($details >=1)
		{
			$this->listFiles();
			$this->oldestDate=getOldestFileDate($this->relPath);
			$this->newestDate=getNewestFileDate($this->relPath);
			$this->times[]=getTimer(true);
		}

		if($details >=2)
		{
			$this->allFilenames = getDistinctNames($this->files);
			$this->nbFilenames = count($this->allFilenames);
			$this->groupedFiles = groupByName($this->relPath, $this->files, true, true);
			if(@$this->groupedFiles["DIR"])
			{
				$this->dirs = array_keys($this->groupedFiles["DIR"]);
				$this->nbDirs = count($this->dirs);
			}

			$this->listThumbnails();

			if(!$this->path)
				$this->addMappedDirs();

			$this->youtube = loadYoutubePlaylist($this->relPath);
			debug("youtube", $this->youtube,"print_r");

			$this->getDateIndex();
			$this->getMetadataIndex("IMAGE");
			$this->getMetadataIndex("VIDEO");
			$this->tags = loadTagFiles($this->relPath, $this->getDepth(), null, $this->allFilenames);
			$this->times[]=getTimer(true);
		}

		if($details == 3)
		{
			$this->addFileDetails();
			$this->times[]=getTimer(true);
			$this->thumbnails = $this->allFilenames = $this->files = null;
		}
		else if($details >=4)
		{
			//Group by name / make MediaFile objects
			$this->createMediaFiles();
			$this->thumbnails = $this->allFilenames = $this->files = null;
			$this->times[]=getTimer(true);
		}

		if($this->search["config"])			
			$this->config = $config;

		$this->buildTime=getTimer();
    }

	//init search parameters from request
    public function getSearchParameters()
	{
		if(!$this->search)
			$this->search = getSearchParameters();	
debug("Album::getSearchParameters", $this->search);		
		return $this->search;
	}

	public function isCompleteIndex()
	{
		$this->isCompleteIndex = !@$this->search["type"] && !@$this->search["name"] && !@$this->search["count"];
		return $this->isCompleteIndex;
	}

	public function getDepth()
	{
		$this->getSearchParameters();
		return $this->search["depth"];
	}

    public function getPath()
	{
		return $this->path;
	}
	
    public function getRelPath()
	{
		if(!$this->relPath)
			$this->relPath = getDiskPath($this->path);

		return $this->relPath;
	}

    public function getAbsPath()
	{
		if(!$this->urlAbsPath)
			$this->urlAbsPath = diskPathToUrl($this->getRelPath());
		return $this->urlAbsPath;
	}
	
    public function getTitle()
	{
		if(!$this->title)	$this->title = getDirConfig($this->path,"TITLE");
		if(!$this->title)	$this->title = makePathTitle($this->path);
		return $this->title;
	}

    public function getDescription()
	{
		if(!$this->description)
			$this->description = $this->getDirDescription();
		return $this->description;
	}

    public function getDirDescription($name="")
	{

		return testFunctionResult("readTextFile", combine($this->relPath, $name, "readme.txt"));
	}

    public function getFileDescription($name)
	{
		return testFunctionResult("readTextFile", combine($this->relPath, "$name.txt"));
	}

    public function getDateIndex()
	{
		//TODO use dateIndex.types;IMAGE
		$this->_dateIndexEnabled = getConfig("dateIndex.enabled");
		if(is_null($this->_dateIndex)  && $this->_dateIndexEnabled)
		{
			$diFiles = $this->getDateIndexFiles();
			$this->_dateIndex = getRefreshedDateIndex($this->relPath, $diFiles, $this->isCompleteIndex());
		}
		return $this->_dateIndex;
	}

//image: load width, height, animated, alpha, etc.
//image: load exts, width, height, duration, codec
    public function getMetadataIndex($type)
	{
debug("getMetadataIndex $type is set", isset($this->metadata));
		//TODO use dateIndex.types;IMAGE
		if(!isset($this->metadata))
			$this->metadata = array();		
		if(!isset($this->metadata[$type]))
			$this->metadata[$type] = getMetadataIndex($this->relPath, $type, @$this->groupedFiles[$type], $this->isCompleteIndex());
		return @$this->metadata[$type];
	}

    public function getDateIndexFiles()
	{
		//TODO use dateIndex.types;IMAGE;VIDEO
		$result = array();
		$types = (array) getConfig("dateIndex.types");
debug("dateIndex.types", $types);
		foreach ($this->groupedFiles as $type => $typeFiles)
		{
debug($type, count($typeFiles));
			if($type == $types || in_array($type, $types))
				$result = arrayUnion($result, $typeFiles);
		}
		return $result;
	}

	//list all files
	public function listFiles($search=null)
	{
		if($search)
			return listFilesRecursive($this->relPath, $search);
		$search = $this->search;
		$this->files = listFilesRecursive($this->relPath, $search); //TODO : group by name / make MediaFile objects
		$this->nbFiles = count($this->files);
		return $this->files;
	}

	//filter among current album files
	public function filterFiles($search)
	{
		if(!$this->files)
			$this->listFiles();
		return filterFiles($this->files, $search);
	}

	//list all thumbnails
	public function listThumbnails()
	{
		$tndirs = getConfig("thumbnails.dirs");
		$filters = $this->search;
		$filters["noext"] = true;
		$this->thumbnails = array();
		foreach ($tndirs as $tndir)
		{
			$filters["subdir"] = ".$tndir";
			$tnfiles = listFilesRecursive($this->relPath, $filters);
			$this->thumbnails[$tndir] = arrayToMap($tnfiles);
		}
		return $this->thumbnails;
	}

	public function getThumbnails($tndir="")
	{
		if(!$this->thumbnails) 		return array();
		if(!$tndir)		return $this->thumbnails;
		if(array_key_exists($tndir, $this->thumbnails))	return $this->thumbnails[$tndir];
		return array();
	}

	public function getFileThumbnails($name)
	{
		if(!$this->thumbnails) 		return array();
		$tnsizes = array();
		foreach($this->thumbnails as $tndir => $thumbnails)
			$tnsizes[] = $this->thumbnails && array_key_exists($name, $thumbnails) ? 11 : -1;
		return $tnsizes;
	}


	//create array of MediaFile objects
	public function createMediaFiles()
	{
		$prevDir="";
		$dateIndex = $this->_dateIndex;
		foreach ($this->groupedFiles as $type => $typeFiles)
		{
			array_walk($typeFiles, array($this, "createMediaFile"), $type);
			if(false)
			foreach($typeFiles as $name => $file)
			{
	 			// if file in different dir: load new date index
				$fileDir = coalesce(@$file["filePath"], combine($this->path, @$file["subdir"]));

	 			if($fileDir != $prevDir && $this->_dateIndexEnabled)
					$dateIndex = loadDateIndex($fileDir);
				$mf = new MediaFile($this, $file);
				$this->checkDateRange($mf);
				$this->setMediaFileTags($mf);
				$this->groupedFiles[$type][$name] = $mf;
				$prevDir = $fileDir;
			}

			debug("createMediaFiles $type", array_keys($this->groupedFiles[$type]));
		}

		return $this->groupedFiles;
	}


	public function createMediaFile(&$item, $name, $type)
	{
		$mf = new MediaFile($this, $item);
		$this->checkDateRange($mf);
		$this->setMediaFileTags($mf);
		$this->groupedFiles[$type][$name] = $mf;
		debug("createMediaFile $type $name", $this->groupedFiles[$type][$name]);
		return $mf;
	}


	public function addFileDetails()
	{
		$prevDir="";
		foreach ($this->groupedFiles as $type => &$typeFiles)
			array_walk($typeFiles, array($this, "setFileDetails"), $type);

		return $this->groupedFiles;
	}

	public function setFileDetails(&$mf, $name, $type)
	{
		if($mf["type"]=="DIR")
		{
			$dirPath = combine($this->relPath, $name); //Or something else for mapped dirs
			$_mappedPath = isMappedPath($name);
			if($_mappedPath)
			{
				$dirPath = $_mappedPath;
				$mf["urlAbsPath"] = diskPathToUrl($_mappedPath);
			}
			$mf["oldestDate"] = getOldestFileDate($dirPath);
			$mf["takenDate"] = $mf["newestDate"] = getNewestFileDate($dirPath);
			$mf["thumbnails"] = subdirThumbs($dirPath, 4);
			$mf["description"] = $this->getDirDescription($name);
		}
		else 
		{
			$mf["description"] = $this->getFileDescription($name);
			$mf["takenDate"] = arrayGet(@$this->_dateIndex, $name);
			if(contains("VIDEO,IMAGE",$type))
				$mf["tnsizes"] = $this->getFileThumbnails($name);
		}
		debug("setFileDetails $type $name", $mf);
	}

	private function setMediaFileTags($mf)
	{
		//for each tag file, 		//mediafile -> addtag
		if(!$mf || !$this->tags) return;
		$key = combine($mf->getSubdir(), $mf->getName());
		foreach ($this->tags as $tag => $tagList)
		{
			if(array_key_exists("$key", $tagList))
			{
				debug("\t$key addTag", $tag);
				$mf->addTag($tag);
			}
		}
	}

	private function addMappedDirs()
	{
		$mappings = getConfig("_mapping");
		if(!$mappings) return;

		$rootMapping = getConfig("_mapping._root");
debug("addMappedDirs", $mappings, true);
debug("rootMapping", $rootMapping);
		foreach ($mappings as $key => $path)
		{
			if($key =="_root" || $key == $rootMapping || !file_exists($path)) 
				continue;

			$dir = array();
			$dir["name"] = $key;
			$dir["mapped"] = true;
			$dir["type"] = "DIR";
			$this->groupedFiles["DIR"][$key] = $dir;
debug("adding", $dir);
		}
	}

//TODO store date range
//propagate to parent dirs.
	private function checkDateRange($mediaFile)
	{
		$takenDate=$mediaFile->getTakenDate();
		if($takenDate < $this->oldestDate)
			$this->oldestDate=$takenDate;

		if($takenDate > $this->newestDate)
			$this->newestDate=$takenDate;
	}
	
	public function selectDirs($filter)
	{
		return @$this->groupedFiles["DIR"];
	}
	
	public function getFilesByType($type)
	{
		if(isset($this->groupedFiles[$type]))
			return $this->groupedFiles[$type];
		return array();
	}

	public function countFilesByType($type)
	{
		return count(@$this->groupedFiles[$type]);
	}

	public function getFileByName($name, $type="")
	{
debug("Album.getFileByName", "name=$name type=$type");
		if($type)
			return arrayGet($this->groupedFiles, "$type.$name");

		if($this->groupedFiles)
			foreach ($this->groupedFiles as $type => $files)
				if(isset($files[$name]))
					return $files[$name];
		return null;
	}
	
	public function getMediaFiles($types="")
	{
		if(!$types)
			return flattenArray($this->groupedFiles);

		if(is_string($types))
			$types = explode('|', $types);
		$mediaFiles = array();
		foreach ($types as $type)
		{
			$typeFiles = $this->getFilesByType($type);
			$mediaFiles= array_merge($mediaFiles, $typeFiles);
		}
		return $mediaFiles;
	}

	public function getFilesByTag($tag)
	{
		$tagList = $this->tags[$tag];
		$fileList = array();
		foreach ($tagList as $name)
		{
			$mf = $this->getFileByName($name);
			if($mf)
				$fileList[] = $mf;
		}
		return $fileList;
	}

	public function countMediaFiles()
	{
		return count($this->getMediaFiles());
	}

	public function getMediaFile($index=0, $type="")
	{		
		if(@$this->search["name"] && $mf = $this->getFileByName($this->search["name"], $this->search["type"]))
				return $mf;

		$files=$this->getMediaFiles($type);
		return @$files[$index];
	}

}

?>