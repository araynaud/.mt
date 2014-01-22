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
	private $_groupedFiles; //array of files in the directory
	private $mediaFiles; //array of MediaFile in the directory
	private $otherFiles; //array of MediaFileVersion thumbnail images in different subdirectories
	private $_dateIndex; // = array(); //array of date,filename entries
	private $_dateIndexEnabled;
    private $config;

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
			$allFiles=listFiles($this->relPath,$this->search); //TODO : group by name / make MediaFile objects
			$this->dirs=selectDirs($this->relPath,$allFiles);
			$this->_groupedFiles=groupByName($allFiles, true);
			$this->_dateIndexEnabled = getConfig("dateIndex.enabled");
			$this->getDateIndex();
			//Group by name / make MediaFile objects
			$this->mediaFiles = $this->createMediaFiles();
			if($this->search["sort"])
				$this->mediaFiles=sortFiles($this->mediaFiles, $this->search["sort"], $this->_dateIndex);
			if($this->search["array"])
				$this->mediaFiles=array_values($this->mediaFiles);

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
		$this->search = Array();		
		$this->search["type"]=getParam("type");
		$this->search["name"]=getParam("name");
		$this->search["sort"]=getParam("sort");
		$this->search["depth"]=$this->getDepth();
		$this->search["metadata"]=getParamBoolean("metadata");
		$this->search["maxCount"]=getParam("count",0);
		$this->search["config"]=getParamBoolean("config",true);
		$this->search["array"]=getParamBoolean("array", true);

		return $this->search;
	}

	public function getDepth()
	{
		global $DEFAULT_DEPTH;
		if($this->depth==null)
			$this->depth=getParamNumOrBool("depth", $DEFAULT_DEPTH);
		return $this->depth;
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
			$this->urlAbsPath = diskPathToUrl($this->relPath);
		return $this->urlAbsPath;
	}
	
    public function getTitle()
	{
		if(!$this->title)	$this->title = GetDirConfig($this->path,"TITLE");
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
			$this->_dateIndex = getRefreshedDateIndex($this->relPath, $diFiles, true);
		}
		return $this->_dateIndex;
	}

    public function getDateIndexFiles()
	{
		//TODO use dateIndex.types;IMAGE;VIDEO
		$result = array();
		$types = (array) getConfig("dateIndex.types");
debug("dateIndex.types", $types);
		foreach ($this->_groupedFiles as $type => $typeFiles)
		{
debug($type, count($typeFiles));
			if($type == $types || in_array($type, $types))
				$result = array_merge($result, $typeFiles);
		}
		return $result;
	}

    public function test()
	{
		echo $this->path . "  " . $this-> title . "\n";
    }
	
	//create array of MediaFile objects
	private function createMediaFiles()
	{
		$distinct=array();
		$prevDir="";
		$dateIndex = $this->_dateIndex;
		foreach ($this->_groupedFiles as $type => $typeFiles)
		{
			foreach ($typeFiles as $name => $file)
			{
	 			// if file in different dir: load new date index
				$fileDir=combine($this->path, @$file["subdir"]);
	 			if($file["subdir"] != $prevDir && $this->_dateIndexEnabled)
					$dateIndex = loadDateIndex($fileDir);
				$mf = new MediaFile($this, $file);
				$this->checkDateRange($mf);
				$distinct[]=$mf;
				$prevDir = $file["subdir"];
			}
		}
		return $distinct;
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
		return selectDirs($this->relPath,$this->mediaFiles);
	}
	
	public function getFilesByType($type)
	{
		return selectFilesByType($this->_allFiles,$type);
	}
	
	public function countFilesByType($type)
	{
		return count($this->getFilesByType($type));
	}

	public function getMediaFiles()
	{
		return $this->mediaFiles;
	}

	public function getMediaFile($index=0)
	{
		return @$this->mediaFiles[$index];
	}

}

?>