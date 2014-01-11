<?php
// media file, 1 per name
class Album extends BaseObject
{
//    private $config;
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
	private $mediaFiles; //array of MediaFile in the directory
	private $dirs; //subdirectories
	private $_allFiles; //array of files in the directory
	private $otherFiles; //array of MediaFileVersion thumbnail images in different subdirectories
	private $_dateIndex; //array of date,filename entries
	
    public function __construct($path="", $details=false)
	{
		global $config;
		global $dateIndex;
	
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
			$this->_allFiles=selectFilesByType($allFiles,"VIDEO|IMAGE");
			$this->_allFiles=array_merge($this->_allFiles, $this->dirs);
			//Group by name / make MediaFile objects
			$dateIndex=getRefreshedDateIndex($this->relPath,$this->_allFiles,true);
			//$this->dateIndex=$dateIndex;
//			createDir($this->relPath,".tn");
			$this->mediaFiles=$this->createMediaFiles($allFiles, $dateIndex);
			if($this->search["sort"])
				$this->mediaFiles=sortFiles($this->mediaFiles, $this->search["sort"], $dateIndex);
			$this->mediaFiles=array_values($this->mediaFiles);

			$this->oldestDate=getOldestFileDate($this->relPath);
			$this->newestDate=getNewestFileDate($this->relPath);
			$this->cDate=$this->oldestDate;
			$this->mDate=$this->newestDate;
			if($this->search["conf"])			
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
		$this->search["conf"]=getParamBoolean("conf",true);

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
//		if(isMappedPath($this->path) && !$this->urlAbsPath)
		if(!$this->urlAbsPath)
			$this->urlAbsPath = resolveMappedPath($this->relPath);
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
	
    public function test()
	{
		echo $this->path . "  " . $this-> title . "\n";
    }
	
	//create array of MediaFile objects
	private function createMediaFiles($files, $dateIndex)
	{
		$distinct=array();
		$prevDir="";
		foreach ($files as $file)
		{
			//split subdir/file.ext1:ext2
			splitFilePath($file,$subdir,$filename);
			splitFilename($filename,$name,$ext);
			$key=combine($subdir,$name);
			$filePath=combine($this->path,$subdir);
			if($subdir!=$prevDir) // if file in different dir: load new date index
				$dateIndex=loadDateIndex($filePath);

			if(!isset($distinct[$key]))
			{
				$distinct[$key] = new MediaFile($this,$subdir,$name,$ext);
				$this->checkDateRange($distinct[$key]);
			}
			else
				$distinct[$key]->addVersion("",$ext);

			$prevDir=$subdir;
		}
		return $distinct;
	}

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
		return $this->mediaFiles[$index];
	}

}

?>