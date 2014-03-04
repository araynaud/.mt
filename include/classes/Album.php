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
    private $depth;
    private $mdate;
    private $cdate;
    private $oldestDate;
    private $newestDate;
	private $description;
	private $dirs; //subdirectories
	private $_allFiles; //array of files in the directory
	private $_metadataIndex = array();
	private $groupedFiles; //array of files in the directory
	private $mediaFiles; //array of MediaFile in the directory
	private $otherFiles; //array of MediaFileVersion thumbnail images in different subdirectories
	private $_dateIndex; // = array(); //array of date,filename entries
	private $_dateIndexEnabled;
	private $isCompleteIndex;
    private $config;
	private $tags;

    public function __construct($path="", $details=false)
	{
		global $config;
	
        $this->user = new User();
        $this->path = $path;
		$this->getRelPath();
		$this->getAbsPath();
		$this->getTitle();
		$this->getDescription();

		if($details)
		{
			$this->getSearchParameters();
			//list files according to search, etc.		
			$allFiles=listFiles($this->relPath, $this->search); //TODO : group by name / make MediaFile objects
//debug("allFiles", $allFiles, true);
			$this->dirs=selectDirs($this->relPath, $allFiles);
			$this->groupedFiles=groupByName($this->relPath, $allFiles, true);
			$allFiles=groupByName($this->relPath, $allFiles);
//debug("allFiles", $allFiles, true);
			$this->_dateIndexEnabled = getConfig("dateIndex.enabled");
			$this->getDateIndex();

			$this->getMetadataIndex("IMAGE");
			$this->getMetadataIndex("VIDEO");

			//Group by name / make MediaFile objects
			$this->tags = loadTagFiles($this->relPath, $allFiles);

			$this->createMediaFiles();

			$this->oldestDate=getOldestFileDate($this->relPath);
			$this->newestDate=getNewestFileDate($this->relPath);
			$this->cDate=$this->oldestDate;
			$this->mDate=$this->newestDate;
			if($this->search["config"])			
				$this->config = $config;
		}
		//$this->jquery = allowJqueryFX();
		$this->private = isPrivate($path);
		$this->buildTime=getTimer();
    }

	//init search parameters from request
    public function getSearchParameters()
	{
		$this->search = getSearchParameters();	
		return $this->search;
	}

	public function isCompleteIndex()
	{
		$this->isCompleteIndex = !$this->search["type"] && !$this->search["name"] && !$this->search["maxCount"];
		return $this->isCompleteIndex;
	}

	public function getDepth()
	{
		if($this->depth==null)
			$this->depth=getParamNumOrBool("depth", 0);
		return $this->depth;
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
				$result = arrayReplace($result, $typeFiles);
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
				$fileDir=combine($this->path, @$file["subdir"]);
	 			if($file["subdir"] != $prevDir && $this->_dateIndexEnabled)
					$dateIndex = loadDateIndex($fileDir);
				$mf = new MediaFile($this, $file);
				$this->checkDateRange($mf);
				$this->setMediaFileTags($mf);
				$this->groupedFiles[$type][$name] = $mf;
				$prevDir = $file["subdir"];
			}
			debug("createMediaFiles $type", array_keys($this->groupedFiles[$type]));
		}
		//return $distinct;
		return $this->groupedFiles;
	}


	private function setMediaFileTags($mf)
	{
		//for each tag file, 		//mediafile -> addtag
		if(!$this->tags) return;
		foreach ($this->tags as $tag => $tagList)
			if(isset($tagList[$mf->getName()]))
				$mf->addTag($tag);
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
		return @$this->groupedFiles[$type];
	}
	
	public function countFilesByType($type)
	{
		return count(@$this->groupedFiles[$type]);
	}

	public function getMediaFiles()
	{
		return flattenArray($this->groupedFiles);
	}

	public function countMediaFiles()
	{
		return count($this->getMediaFiles());
	}

	public function getMediaFile($index=0)
	{
		$files=$this->getMediaFiles();
		return $files[$index];
	}

}

?>