<?php
// media file, 1 per name
class Album extends BaseObject
{
    private $path;
    private $relPath;
    private $urlAbsPath;
	private $buildTime;
    private $name;
    private $title;
    private $user;
	private $jquery;
	private $private;
	private $search; //array of search criteria: types, name, depth, dates
    private $mdate;
    private $cdate;
    private $oldestDate;
    private $newestDate;
	private $description;
	private $dirs; //subdirectories
	private $_allFiles; //array of files in the directory
	private $_metadataIndex = array();
	private $youtube; //array of files in the directory
	private $groupedFiles; //array of files in the directory
	private $mediaFiles; //array of MediaFile in the directory
	private $otherFiles; //array of MediaFileVersion thumbnail images in different subdirectories
	private $_dateIndex; // = array(); //array of date,filename entries
	private $_dateIndexEnabled;
	private $isCompleteIndex;
    private $config;
	private $tags;

    public function __construct($filters="", $details=false)
	{
		global $config;

debug("new Album", $filters);
	
		if(is_array($filters))
		{
			$this->search = $filters;
			$details = true;
			$path = $filters["path"];
		}
		else
			$path = $filters;

        $this->user = new User();
        $this->path = $path;
		$this->getRelPath();
		$this->getAbsPath();
		$this->getTitle();
		$this->getDescription();
debug("new Album", $details);
		if($details)
		{
			$this->getSearchParameters();
			//list files according to search, etc.		
			$allFiles=listFilesRecursive($this->relPath, $this->search); //TODO : group by name / make MediaFile objects
//debug("allFiles", $allFiles, true);
			$this->dirs=selectDirs($this->relPath, $allFiles);
			$this->groupedFiles=groupByName($this->relPath, $allFiles, true);
			$allFiles=groupByName($this->relPath, $allFiles);
			if(!$this->path)
				$this->addMappedDirs();
//debug("allFiles", $allFiles, true);
			$this->_dateIndexEnabled = getConfig("dateIndex.enabled");
			$this->getDateIndex();

			$this->getMetadataIndex("IMAGE");
			$this->getMetadataIndex("VIDEO");

			//Group by name / make MediaFile objects
			$this->tags = loadTagFiles($this->relPath, $this->getDepth(), null, $allFiles);
			$this->youtube = loadYoutubePlaylist($this->relPath);
debug("youtube", $this->youtube,"print_r");
			$this->createMediaFiles();

			$this->oldestDate=getOldestFileDate($this->relPath);
			$this->newestDate=getNewestFileDate($this->relPath);
			$this->cDate=$this->oldestDate;
			$this->mDate=$this->newestDate;
			if($this->search["config"])			
				$this->config = $config;
		}
		$this->private = isPrivate($path);
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
			$this->description = readTextFile(combine($this->relPath, "readme.txt"));
		return $this->description;
	}

    public function getDateIndex()
	{
		//TODO use dateIndex.types;IMAGE
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
		//TODO use dateIndex.types;IMAGE		
		if(!isset($this->_metadataIndex[$type]))
			$this->_metadataIndex[$type] = getMetadataIndex($this->relPath, $type, @$this->groupedFiles[$type], $this->isCompleteIndex());
		return $this->_metadataIndex[$type];
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
	
	//create array of MediaFile objects
	private function createMediaFiles()
	{
		//$distinct=array();
		$prevDir="";
		$dateIndex = $this->_dateIndex;
		foreach ($this->groupedFiles as $type => $typeFiles)
		{
			foreach ($typeFiles as $name => $file)
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
		//return $distinct;
		return $this->groupedFiles;
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
			//$dir["filePath"] = $path;
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

	public function getMediaFile($index=0)
	{
		if($this->search["name"] && $mf = $this->getFileByName($this->search["name"], $this->search["type"]))
				return $mf;

		$files=$this->getMediaFiles();
		return @$files[$index];
	}

}

?>