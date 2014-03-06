<?php
// media file, 1 per name
class MediaFile extends BaseObject
{
    private $id;
    private $_filePath;
    protected $name;
    protected $subdir;
	protected $type;
	protected $exts=array();		//array of extensions available for this filename
    private $title;
    private $description;
    private $takenDate;
    private $cDate;
    private $mDate;
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
	private $tnsizes=array(); //array of thumbnail file sizes
	private $vsizes=array(); //array of thumbnail file sizes
	private $tags=array();

    public function __construct($album, $file)
	{
		$this->_parent=$album;
		foreach ($file as $key => $value)
			$this->$key = $value;

		$this->setMultiple($file);

		foreach($this->exts as $ext)
			$this->addVersion($ext);

		$this->title = makeTitle($this->name);
		$this->_filePath = $this->getFilePath();
		$this->getDescription();
		$this->getTakenDate();

		if($this->isDir())
		{
			$this->oldestDate=getOldestFileDate($this->_filePath);
			$this->newestDate=getNewestFileDate($this->_filePath);
			$this->takenDate=$this->newestDate;
			$this->thumbnails=subdirThumbs($this->_filePath, 4);
		}
		else
		{
			$this->getMetadata();
			if($this->isVideo())
			{
				$streamTypes = getConfig("TYPES.VIDEO_STREAM");
				$this->stream = array_intersect($streamTypes, $this->exts);
				$this->animated = true;
			}
			//thumbnails: image: .tn & .ss, same ext.
			$this->addImageThumbnails();
		}
	}

    public static function getMediaFile()
    {    	
		$path=reqPath();
		$file = reqParam("file");
		if($file)
		{
			$relPath=getDiskPath($path);
			$_REQUEST["type"] = getFileType("$relPath/$file");
			$_REQUEST["name"] = getFilename($file);
		}
		$album = new Album($path, true);
		$mf = $album->countMediaFiles() == 1 ? $album->getMediaFile() : $album->getMediaFiles();
		return $mf;
	}

    public function addImageThumbnails()
    {
    	$tnSizes = getConfig("thumbnails.sizes");
    	if(!$tnSizes) return;
    	$noThumbTypes = getConfig("TYPES.IMAGE_NOTHUMB");
		$noThumb = array_intersect($this->exts, $noThumbTypes);
    	if($noThumb) return;

    	foreach ($tnSizes as $subdir => $size)
    	{
    		if(!$this->animated && $this->imageIsSmaller($size)) break;
			debug("addImageThubnails $subdir $size", $this->width . "x" . $this->height);
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
		if(!$sizes || $this->imageIsSmaller($maxSize))
			return false;

		$tnIndex = 0;
		foreach($sizes as $dir => $size)
		{
			if($size <= $maxSize)
			 return $dir;
		}
	}
	
	public function getName()
	{
		return $this->name;
	}

	public function getFileType()
	{
		return $this->type;
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
		return $ext ? $this->name.".$ext" : $this->name;
	}

    public function getFilePath($ext=0)
	{
		return combine($this->getRelPath(), $this->subdir, $this->getFilename($ext));
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
		return combine(".$subdir", $filename);
	}

    public function getThumbnailFilePath($subdir)
	{
		return combine($this->getRelPath(), $this->subdir, $this->getThumbnailFilename($subdir));
	}

    public function getThumbnailFilesize($subdir)
	{
		$filePath = $this->getThumbnailFilePath($subdir);
		return file_exists($filePath) ? filesize($filePath) : -1;
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

    public function setDescription($desc)
	{
		$filename = $this->getDescriptionFilename(true);
		$this->description = $desc;
		return writeTextFile($filename, $desc);
	}
	
    public function getMetadata()
	{
		$index = $this->_parent->getMetadataIndex($this->type);
		$key = combine($this->subdir, $this->name);
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
		if($moveTo)
			return $this->move($moveTo);

		$filePath = $this->getFilePath();
		if(is_dir($filePath))
			$result = rmdir ($filePath);

		$filenames=$this->getFilenames();
		$dir = $this->getFileDir();
		$result=0;
		foreach ($filenames as $key => $file)
		{
debug("deleteFile", "($dir, $file)");
			$result += deleteFile(combine($dir, $file));
		}			
		return $result;
	}

	public function addTag($tag)
	{
		$this->tags[]=$tag;
debug("addTag " . $this->name, $tag);
	}

	public function setTag($tag, $state)
	{
		return saveFileTag($this->getFileDir(), $this->name, $tag, $state);
	}

	public function getTags()
	{
		return $this->tags;
	}

}

?>