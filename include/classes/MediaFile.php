<?php
// media file, 1 per name
class MediaFile extends BaseObject
{
    //private $id;
    protected $_filePath;
    protected $name;
    protected $subdir;
	protected $type;
	protected $exts=array();		//array of extensions available for this filename
	protected $size;
	protected $date;
    private $takenDate;
    private $title;
    private $description;
    private $metadata;
    private $oldestDate;
    private $newestDate;
	private $stream;
    protected $format;
    protected $width;
    protected $height;
    protected $ratio;
    protected $duration;
    protected $animated;
    protected $alpha;
    protected $mapped;
    protected $_mappedPath;
    protected $urlAbsPath;
    protected $gdimage;

	private $tnsizes=array(); //array of thumbnail file sizes
	private $vsizes=array(); //array of thumbnail file sizes
	private $tags=array();

    public function __construct($album, $file)
	{
		$this->_parent=$album;
		$this->setMultiple($file);

//		foreach($this->exts as $ext)
//			$this->addVersion($ext);

		//$this->getTitle();
		$this->_filePath = $this->getFilePath();
		$this->getDescription();
		$this->getTakenDate();

		if($this->isDir())
		{
			$_mappedPath = isMappedPath($this->name);
			if($_mappedPath)
				$this->urlAbsPath = diskPathToUrl($_mappedPath);

			$this->oldestDate=getOldestFileDate($this->_filePath);
			$this->newestDate=getNewestFileDate($this->_filePath);
			$this->takenDate=$this->newestDate;
debug("MediaFile " . $this->name , $this->_filePath);
			$this->thumbnails=subdirThumbs($this->_filePath, 4);
debug("dates " . $this->oldestDate, $this->newestDate);
		}
		else
		{
			$this->getMetadata();
			if($this->isVideo())
				$this->animated = true;
			$this->addImageThumbnails();
			//$this->addThumbnails();
		}
	}

    public static function getMediaFile($search=null)
    {
		if(!$search) $search = getSearchParameters();
debug("MediaFile::getMediaFile", $search);
		$album = new Album($search, 4);
debug("MediaFile::getMediaFile countMediaFiles", $album->countMediaFiles());
		return $album->getMediaFile();
	}

    public static function getMediaFiles($search=null)
    {    	
		if(!$search) $search = getSearchParameters();
debug("MediaFile::getMediaFiles", $search);
		$album = new Album($search, 4);
debug("MediaFile::getMediaFiles countMediaFiles", $album->countMediaFiles());
		return $album->getMediaFiles();
	}


    public function addThumbnails()
    {
    	if($this->_parent)
	    	$this->tnsizes = $this->_parent->getFileThumbnails($this->name);
    	return $this->tnsizes;
	}

    public function addImageThumbnails()
    {
    	$tnSizes = getConfig("thumbnails.sizes");
    	if(!$tnSizes) return;
    	$noThumbTypes = getConfig("TYPES.IMAGE_NOTHUMB");
		$noThumb = array_intersect($this->exts, $noThumbTypes);
    	if($noThumb) return;
		debug("addImageThumbnails", $tnSizes);

    	foreach ($tnSizes as $subdir => $size)
    	{
    		if(!$this->animated && $this->imageIsSmaller($size)) break;
			debug("addImageThumbnails $subdir $size", $this->width . "x" . $this->height);
 			$this->addThumbnail($subdir);
    		if($this->imageIsSmaller($size)) break;
    	}
    	debug("tnsizes", $this->tnsizes);
    }

    public function addVersion($ext="")
	{
		$this->vsizes[$ext] = $this->getFilesize($ext);
	}

    public function addThumbnail($subdir="")
	{
		$size = $this->getThumbnailFilesize($subdir);
		//if thumb does not exist and video and no FFMPEG: do not add
		if($size > 0 || $this->type == "IMAGE" || isFfmpegEnabled())
			$this->tnsizes[] = $size;
    }

//select largest thubnail <= $maxSize
//if none, return false: use original
    public function selectThumbnail($maxSize)
	{
		if(!$maxSize) return false;

		$sizes = getConfig("thumbnails.sizes");
//		rsort($sizes);
		$sizes = array_reverse($sizes);
		debug("thumbnails.sizes", $sizes);
	
		//use original file
		if(!$sizes || $this->isImage() && $this->imageIsSmaller($maxSize))
			return false;

		foreach($sizes as $dir => $size)
		{
			if($size <= $maxSize)
			 return $dir;
		}
		return $dir;
	}
	
	public function getName()
	{
		return $this->name;
	}

	public function getRatio()
	{
		if(!$this->height || !$this->width) 
			$this->ratio = 0;
		else
			$this->ratio = $this->width / $this->height;
		return $this->ratio;
	}

	public function getImageInfo()
	{
debug("MediaFile.getImageInfo", $this->getMultiple("width,height,ratio,format"));
		$this->getRatio();
		return $this->getMultiple("width,height,ratio,format");
	}

	public function getTitle()
	{
		if(!$this->title)
			$this->title = makeTitle($this->name);
		return $this->title;
	}

	public function getFileType()
	{
		return $this->type;
	}
		
	public function getSubdir()
	{
		return $this->subdir;
	}

	public function getPath()
	{
		if($this->_parent)
			return $this->_parent->getPath();
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
		return $ext ? $this->name.".$ext" : $this->name;
	}

    public function getFilePath($ext=0)
	{
		$path = $this->getPath();
		if(!$path)
			return $this->getDiskPath();
//		return $this->getRelPath() ."/". $this->subdir ."/". $this->getFilename($ext);
		return combine($this->getRelPath(), $this->subdir, $this->getFilename($ext));
	}

	public function getDiskPath()
	{
		return getDiskPath($this->name);
	}

	public function getRelPath()
	{
		if($this->_parent)
			return $this->_parent->getRelPath();
		return "";
	}

    public function getFileUrl($ext=0)
    {
    	$path = $this->getPath();
		$filename = $this->getFilename($ext);
    	return getAbsoluteFileUrl($path, $filename);
    }

    public function getFilesize($ext=0)
	{
		$filePath = $this->getFilePath($ext);
		return file_exists($filePath) ? filesize($filePath) : -1;
	}

    public function getFileDir()
	{
		return combine($this->getRelPath(), $this->subdir);
	}

    public function getThumbnailExtension()
	{
	 	$ext = getConfig("thumbnails." . $this->type . ".ext");
 		return coalesce($ext, $this->getExtension());
	}

    public function getThumbnailFilename($subdir="")
	{		
		$filename = $this->name . "." . $this->getThumbnailExtension();
		if(!$subdir) return $filename;
		$tnPath = combine(".$subdir", $filename);
		return $tnPath;

//		$fullPath = combine($this->getRelPath(), $this->subdir, $tnPath);
//		return file_exists($fullPath) ? $tnPath : $filename;
	}

    public function getThumbnailFilePath($subdir="")
	{
		return combine($this->getRelPath(), $this->subdir, $this->getThumbnailFilename($subdir));
	}

    public function getThumbnailFilesize($subdir)
	{
		$filePath = $this->getThumbnailFilePath($subdir);
		return file_exists($filePath) ? filesize($filePath) : -1;
	}

    public function getThumbnailUrl($subdir="")
	{
    	$path = $this->getPath();
		$filename = $this->getThumbnailFilename($subdir);
    	return getAbsoluteFileUrl($path, $filename);
	}

    public function thumbnailExists($subdir="")
	{
		$tnPath = $this->getThumbnailFilePath($subdir);
debug("MediaFile.thumbnailExists $subdir $tnPath", file_exists($tnPath));
		return file_exists($tnPath);
	}


    public function getBestImagePath($maxSize)
	{
		$dir = $this->selectThumbnail($maxSize);
debug("MediaFile.getBestImagePath $maxSize", $dir);
		if($this->thumbnailExists($dir))
			return $this->getThumbnailFilePath($dir);
		if($this->isImage()) 
			return $this->getFilePath();
		return false;
	}

    public function getBestImage($maxSize)
	{
		$dir = $this->selectThumbnail($maxSize);
debug("MediaFile.getBestImage $maxSize", $dir);
		if($this->thumbnailExists($dir))
			return $this->getThumbnailFilename($dir);
		if($this->isImage()) 
			return $this->getFilename();
		return false;
	}


    public function loadImage($maxSize)
	{
		$imagePath = $this->getBestImagePath($maxSize);
debug("MediaFile.loadImage",$imagePath);		
		$this->gdimage = $imagePath ? loadImage($imagePath) : null;
		return $this->gdimage;
	}

    public function unloadImage()
	{
		if($this->gdimage)
			imagedestroy($this->gdimage);
		$this->gdimage = null;
	}

    public function createThumbnail($tndir)
	{
		if(!$tndir) return false;
		$size = getConfig("thumbnails.sizes.$tndir");
		return createThumbnail($this->getFileDir(), $this->getFilename(), $tndir, $size);
	}

//return original file names, thubmnails, metadata, description
    public function getFilenames($versions=true, $thumbnails=true, $other=true)
	{
		$filenames=array();
		if($versions)
			foreach($this->exts as $ext)
				$filenames[$ext] = $this->getFilename($ext);
		
		if($thumbnails) //add thumbnails
		{
	    	$tnSizes = getConfig("thumbnails.sizes"); 
	    	if($tnSizes)
		    	foreach ($tnSizes as $subdir => $size)
					$filenames[$subdir] = $this->getThumbnailFilename($subdir);			
		}
		
		if($other) //add description and metadata
		{
			$filenames["description"] = $this->getDescriptionFilename();
			$filenames["metadata"] = $this->getMetadataFilename();
		}
		return $filenames;
	}
 
//return original file name, thubmnails, metadata, description 
//disk paths or urlPaths
    public function getFilePaths($exist=false, $urls=false, $versions=true, $thumbnails=true, $other=true)
	{
		$filenames = $this->getFilenames($versions, $thumbnails, $other);
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

	public function getSubtitleFilename($withPath=false)
	{
		$basePath = $withPath ? $this->getFileDir() : "";
		if($this->type=="VIDEO")
			return combine($basePath, getFilename($this->name, "sub"));
		return "";
	}

	public function getMetadataFilename($withPath=false)
	{
		$basePath = $withPath ? $this->getFileDir() : "";
		return combine($basePath, ".tn", getFilename($this->name, "csv"));
	}	

    public function getDescription()
	{
		$filename = $this->getDescriptionFilename(true);
		$this->description = readTextFile($filename);
		return $this->description;
	}

    public function getAlbum()
	{
		return $this->_parent;
	}

    public function getAlbumTitle()
	{
		if($this->_parent)
			return $this->_parent->getTitle();
		return "";
	}

    public function getAlbumDescription()
	{
		if($this->_parent)
			return $this->_parent->getDescription();
		return "";
	}

    public function setDescription($desc)
	{
		$filename = $this->getDescriptionFilename(true);
		$this->description = $desc;
		return writeTextFile($filename, $desc);
	}
	
    public function getMetadata()
	{
		$key = combine($this->subdir, $this->name);
		$index = $this->_parent->getMetadataIndex($this->type);
		$metadata = @$index[$key];
debug("getMetadata $key", $metadata);		
		$this->setMultiple($metadata);
		return $metadata;
	}

    public function isImage()
	{
		return $this->type=="IMAGE";
	}

    public function isVideo()
	{
		return $this->type=="VIDEO";
	}

    public function isAudio()
	{
		return $this->type=="AUDIO";
	}

    public function isDir()
	{
		return $this->type=="DIR";
	}

    public function isFile()
	{
		return $this->type=="DIR";
	}


    public function isVideoStream()
	{
		$streamTypes = getConfig("TYPES.VIDEO_STREAM");
		$this->stream = array_intersect($streamTypes, $this->exts);
		$this->stream = reset($this->stream);
		return $this->stream;
	}

    public function imageIsLarger($maxSize)
	{
		if(!$maxSize || !$this->width) return false;
		return $this->width > $maxSize || $this->height > $maxSize;
	}

    public function imageIsSmaller($maxSize)
	{
		if(!$maxSize || !$this->width) return true;
		return $this->width <= $maxSize && $this->height <= $maxSize;
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

		//set tags for new file/dir
		$newName = $newName ? $newName : $this->name;

		if($this->tags)
			foreach ($this->tags as $tag) 
				saveFileTag($targetDir, $newName, $tag, true);


		return $result;
	}

	public function delete()
	{
		$moveTo=getConfig("file.delete.to");
debug("file.delete.to", $moveTo);		
		if($moveTo)
			return $this->move($moveTo);

		$filePath = $this->getFilePath();
		if(is_dir($filePath))
			$result = deltree($filePath);

		$filenames=$this->getFilenames();
		$dir = $this->getFileDir();
		$result=0;
		foreach ($filenames as $key => $file)
		{
			$status = deleteFile(combine($dir, $file));
			debug("deleteFile($dir, $file)", $status);
			$result += $status ? 1 : 0;
		}			
		return $result;
	}

	public function setBackground()
	{
		debug("setBackgroundImage", $this->getRelPath() ." / ". $this->getFilename());
		return setBackgroundImage($this->getRelPath(), $this->getFilename());
	}

	public function addTag($tag)
	{
		$this->tags[$tag] = $tag;
debug("addTag " . $this->name, $tag);
	}

	public function removeTag($tag)
	{
		if(isset($this->tags[$tag]))
			unset($this->tags[$tag]);
debug("removeTag " . $this->name, $tag);
	}

	public function setTag($tag, $state)
	{
		if($state)
			$this->addTag($tag);
		else
			$this->removeTag($tag);
		return saveFileTag($this->getFileDir(), $this->name, $tag, $state);
	}

	public function getTags()
	{
		return $this->tags;
	}

	public function setDate($date)
	{
		if(!$date) return $this;

		$this->takenDate = $date;
		//TODO: update date index

		//change filemdate
		$versions = $this->getFilePaths(true, false, true, false, false);
		debug("MediaFile.setDate", $versions);
		foreach ($versions as $key => $filePath)
		{
			setFileDate($filePath, $date);
			debug("setFileDate $filePath", $date);
		}
		return $this;		
	}
}

?>