<?php
// media file, 1 per name
class MediaFile extends BaseObject
{
    private $id;
    private $_filePath;
    private $name;
    private $filename;
    private $subdir;
	private $type;
    private $title;
    private $description;
    private $takenDate;
    private $cDate;
    protected $animated;
    private $mDate;
    private $metadata;
    private $oldestDate;
    private $newestDate;
	private $exts=array();		//array of extensions available for this filename
	private $_versions=array(); //array of MediaFileVersion in the same dir with different extensions
	private $_thumbnails=array(); //array of MediaFileVersion thumbnail images in different subdirectories
	private $stream;
	private $tnsizes=array(); //array of thumbnail file sizes
	private $vsizes=array(); //array of thumbnail file sizes
    protected $format;
    protected $width;
    protected $height;
    protected $ratio;

    public function __construct($album, $file)
	{
		$this->_parent=$album;
		foreach ($file as $key => $value)
			$this->$key = $value;

		foreach($this->exts as $ext)
			$this->addVersion("", $ext);

		$this->title = makeTitle($this->name);
		$this->_filePath = $this->getFilePath();
		$this->getDescription();
		$this->getTakenDate();

		if($this->type=="DIR")
		{
			$this->oldestDate=getOldestFileDate($this->_filePath);
			$this->newestDate=getNewestFileDate($this->_filePath);
			$this->takenDate=$this->newestDate;
			$this->thumbnails=subdirThumbs($this->_filePath, 4);
		}
		else if($this->type=="IMAGE")
		{
			$this->getImageInfo();
			//thumbnails: image: .tn & .ss, same ext.
			$this->addImageThumbnails();
		}
		else if ($this->type=="VIDEO")
		{
			$streamTypes = getConfig("TYPES.VIDEO.STREAM");
			$this->stream = array_intersect($this->exts, $streamTypes);
			//thumbnails: video: .tn/.jpg
			$this->addThumbnail("tn","jpg");
			if($this->_thumbnails[0]->exists)
			{
				$tnPath = $this->_thumbnails[0]->getFilePath();
				$this->getImageInfo($tnPath);
			}
			$this->addThumbnail("ss", "jpg");
			if(!$this->_thumbnails[1]->exists && !isFfmpegEnabled())
				unset($this->tnsizes[1]);
//				$this->getMetadata();
		}
	}

    public static function getMediaFile()
    {    	
		$path=getPath();
		$file = getParam('file');
		if($file)
		{
			$relPath=getDiskPath($path);
			$_GET["type"] = getFileType("$relPath/$file");
			$_GET["name"] = getFilename($file);
		}
		$album = new Album($path, true);
		$mf = $album->countMediaFiles() == 1 ? $album->getMediaFile() : $album->getMediaFiles();
		return $mf;
	}

    public function addImageThumbnails($ext="")
    {
    	$tnSizes = getConfig("thumbnails.sizes"); 
    	if(!$tnSizes) return;
    	foreach ($tnSizes as $subdir => $size)
    	{
    		if($this->animated && $this->width <= $size && $this->height <= $size) break;
			debug("addImageThubnails $subdir $size", $this->width . "x" . $this->height);
 			$this->addThumbnail($subdir, $ext);
//    		if($this->width < $size && $this->height < $size) break;
    	}
    	debug("tnsizes", $this->tnsizes);
    }

	public function getName()
	{
		return $this->name;
	}
	
	public function getSubdir()
	{
		return $this->subdir;
	}
	
	public function getRelPath()
	{
		if($this->_parent)
			return $this->_parent->getRelPath();
		return "";
	}

    public function getTakenDate()
	{
		$dateIndex = $this->_parent->getDateIndex();
		if(!$this->takenDate)
			$this->takenDate = coalesce(@$dateIndex[$this->name], getFileDate($this->_filePath));
		return $this->takenDate;
	}

    public function getExtension($i=0)
	{
		if(!$this->exts)				return "";
		if(isset($this->exts[$i]))		return $this->exts[$i];
		if(in_array($i, $this->exts))	return $i;
		return "";
	}

    public function getFilename($i=0)
	{
		$ext=$this->getExtension($i);
		$this->filename = $ext ? $this->name.".$ext" : $this->name;
		return $this->filename;
	}

    public function getFilePath($ext=0)
	{
		return combine($this->getRelPath(), $this->subdir, $this->getFilename($ext));
	}

    public function getFileDir()
	{
		return combine($this->getRelPath(), $this->subdir);
	}

//return original file names, thubmnails, metadata, description
    public function getFilenames()
	{
		$filenames=array();
		foreach($this->exts as $ext)
			$filenames[] = $this->getFilename($ext);

//add metadata
		$filenames[] = $this->getMetadataFilename();

//add thumbnails
    	$tnSizes = getConfig("thumbnails.sizes"); 
    	if($tnSizes)
    	{
    		$i=0;
	    	foreach ($tnSizes as $subdir => $size)
	    		if(isset($this->_thumbnails[$i]))
					$filenames[] = combine(".$subdir", $this->_thumbnails[$i++]->getFilename());			
		}
		return $filenames;
	}
 
//return original file name, thubmnails, metadata, description 
//disk paths or urlPaths
    public function getFilePaths($exist=false, $urls=false)
	{
		$filenames = $this->getFilenames($exist);
		$basePath = $this->getFileDir();

		foreach ($filenames as $key => $file)
			$filenames[$key] = "$basePath/$file";

		if($exist)
			$filenames=array_filter($filenames,"file_exists");

		if($urls)
		foreach ($filenames as $key => $file)
			$filenames[$key] = diskPathToUrl($file);

		return $filenames;
	}

	public function getDescriptionFilename($withPath=false)
	{
		$basePath = $withPath ? $this->getFileDir() : "";
		if($this->type=="DIR")
			return combine($basePath, $this->name, "readme.txt");
		return combine($basePath, getFilename($this->name, "txt"));
	}

	public function getMetadataFilename($withPath=false)
	{
		$basePath = $withPath ? $this->getFileDir() : "";
		return combine($basePath, ".tn", getFilename($this->name, "csv"));
	}	

    public function getDescription()
	{
		$this->description=readTextFile($this->getDescriptionFilename(true));
		return $this->description;
	}
	
    public function getMetadata()
	{
		$this->metadata=getMediaFileInfo($this->_filePath);
		return $this->metadata;
	}

    public function getImageInfo($filePath="")
	{
		if(!$filePath) $filePath = $this->_filePath; 
		$info = getImageInfo($filePath, true);
		//$this->metadata = $info;
		$this->setMultiple($info);
		return $info;
	}

    public function isAnimated($ext)
	{
		if(!equals($ext,"GIF")) return false;

		$this->frames = countAnimatedGifFrames($this->_filePath);
		$this->animated = ($this->frames > 1);
		
		return $this->frames;
	}

    public function isTransparent($ext)
	{
		$this->transparent = false; 
		$this->transparent = hasTransparentColor($this->_filePath, $ext);
//debug($this->name . " is transparent", $this->transparent);
		return $this->transparent;
	}

    public function isAlpha($ext)
	{
		$this->alpha = false; 
		$this->alpha = hasAlphaPixels($this->_filePath, $ext);
//debug($this->name . " is transparent", $this->transparent);
		return $this->alpha;
	}
	
    public function addVersion($subdir="",$ext="")
	{
//		$this->exts[]=$ext;
		$mf=new MediaFileVersion($this,$subdir,$ext,true);
//		$this->versions[]= $mf;
		$this->vsizes[$ext] = $mf->getSize();
	}
	
    public function addThumbnail($subdir="",$ext="")
	{
		$mf = new MediaFileVersion($this,$subdir,$ext,true);
		$this->_thumbnails[] = $mf;
		$this->tnsizes[] = $mf->getSize();
    }
	
    public function test()
	{
		print_r($this);
		print_r($this->versions[0]);
		echo $this->path ." ".	$this->filename . " " . $this->takenDate . "\n";
    }

	public function move($targetDir, $newName="")
	{
		$filenames=$this->getFilenames();
		$dir = $this->getFileDir();
		$targetDir = combine($dir, $targetDir);
debug("targetDir", $targetDir);
		$result=0;
		foreach ($filenames as $key => $file)
		{
//debug("moveFile", "($dir, $file, $targetDir)");
			$result += moveFile($dir, $file, $targetDir, $newName);
		}
		return $result;
	}

	public function delete()
	{
		$moveTo=getConfig("file.delete.to");
		if($moveTo)
			return $this->move($moveTo);

		$filePath = $this->getFilePath();
		if(is_dir($filePath))
			$result = rmdir ($filePath);

		$filenames=$this->getFilenames();
		$dir = $this-getFileDir();
		$result=0;
		foreach ($filenames as $key => $file)
		{
debug("deleteFile", "($dir, $file)");
//			$result += deleteFile(combine($dir, $file);
		}			
		return $result;
	}

}




//1 version of a file: extension or thumbnail
class MediaFileVersion extends BaseObject
{
	private $mediaFile;
	private $ext="";
	private $subdir="";
    public $exists=false;
    private $cDate;
    private $mDate;
    private $size;
	
	public function __construct($mediaFile, $subdir, $ext="", $details=false)
	{
debug("new MediaFileVersion $subdir", $ext);
		$this->_parent=$mediaFile;

		if($subdir)	$this->subdir=$subdir;
		if($ext)	$this->ext=$ext;

debug("getExtension", $this->getExtension());
debug("getExtension parent", $this->_parent->getExtension());
debug("getFilename", $this->getFilename());

		$filePath=$this->getFilePath();
		$this->_filePath=$filePath;
		$this->exists=file_exists($filePath);
		$this->getSize();
		if($this->exists && $details)
		{
			$this->size=filesize($filePath);
			$this->mDate=formatDate(filemtime($filePath));
			$this->cDate=formatDate(filectime($filePath));
		}
	}

    public function getFilename()
	{
		return $this->_parent->getName() . "." . $this->getExtension();
	}

    public function getSize()
	{
		if(!$this->size)
			$this->size = file_exists($this->_filePath) ? filesize($this->_filePath) : -1;
		return $this->size;
	}

    public function getExtension()
	{
		if($this->ext) return $this->ext;
		return $this->_parent->getExtension();
	}

    public function getFilePath()
	{
		return combine($this->_parent->getRelPath(), $this->_parent->getSubdir(), "." . $this->subdir, $this->getFilename());
	}
	
}

?>