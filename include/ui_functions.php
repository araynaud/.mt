<?php
//================ UI config functions
// depending on user agent

//allow jquery effects
function allowJqueryFX()
{
	global $config;
	setIfNull($config["JQUERYFX"],true);
	return $config["JQUERYFX"]; // && !isPlaystation();
}

//allow facebook comments
function allowFacebook($path)
{
	//return false;
	return !isLocal() && !isPrivate($path) &&  !(isIpad() || isMobile() || isPlaystation());
}

//display FB comments by default?
function displayFacebook()
{
	//return false;
	return !isLocal() && !(isIpad() || isPlaystation());
}

//display big icons in slideshow, for touch screens
function enableBigIcons()
{
	return true || !allowJqueryFX();
}

//render music JW player?
function allowMusicPlayer()
{
	//return false;
	return !(isIpad() || isPlaystation());
}

//render music JW player?
function playMusic()
{
	//return false;
	return !(isIpad() || isPlaystation());
}

//============== UI elements

function pathLinks($path, $parentOnly=false)
{
	$pathArray = explode('/',$path);
	$title = makeTitle($path);
	$titleArray=explode('/',$title);
	$depth=count($pathArray);
	if($parentOnly)
		$depth--;
?>	<a href="?"><img src="icons/home.gif" class="icon" alt="home"/></a>
<?php
	$pp="";
	$sep="";
	for($i=0;$i<$depth;$i++)
	{
		$pp .= $sep. $pathArray[$i];
		echo	"/ <a class='textOutline' href=\"?$pp\">$titleArray[$i]</a> ";
		$sep = '/';
	}
}

//TODO: add depth to links
function pageLinks($path, $start, $nbFiles, $count)
{
	if($count==0) return;
	$nbPages = ceil($nbFiles / $count);
	if($nbPages <= 1) return;	

	$page = ceil(($start+1) / $count);
	if($page > 1) {
?>
		<a class="icon" href="?path=<?php echo $path?>&amp;count=<?php echo $count?>">
			<img src="icons/arrow-first.png" alt="first page"/>
		</a>
<?php }
	if($page > 2) {
?>
		<a class="icon" href="?path=<?php echo $path?>&amp;start=<?php echo $start - $count?>&amp;count=<?php echo $count?>">
			<img src="icons/arrow-back.png" alt="previous page"/>
		</a>
<?php } ?>
		<a class="icon"><?php echo "page $page / $nbPages"?></a>
		<a class="icon" href="?path=<?php echo $path?>&amp;count=*" title="view all pages">(all)</a>
<?php if($page < $nbPages - 1) {
?>
		<a class="icon" href="?path=<?php echo $path?>&amp;start=<?php echo $start + $count?>&amp;count=<?php echo $count?>">
			<img src="icons/arrow-forward.png" alt="next page"/>
		</a>
<?php }
	if($page < $nbPages ) {
?>
		<a class="icon" href="?path=<?php echo $path?>&amp;start=<?php echo ($nbPages - 1) * $count?>&amp;count=<?php echo $count?>">
			<img src="icons/arrow-last.png" alt="last page"/>
		</a>
<?php }
}


function addAllScripts($relPath)
{
	if(!is_dir($relPath) && file_exists($relPath))
		return addScript($relPath);
	$search = array("type" => "js");
	$files = listFilesDir($relPath, $search);
	return addScript($relPath, $files);
}

function addScript($relPath, $file="", $local=false)
{
	if(is_array($file))
	{	
		foreach($file as $f)
			addScript($relPath, $f);
		return;
	}

	$url = combine($relPath, $file);
	if(!$local || file_exists($url))
		return addJavascript($url);
}

function addJavascript($url)
{
	if(!$url) return; 
?><script type="text/javascript" src="<?php echo $url?>"></script>
<?php
	return $url;
}

function addAllCss($relPath)
{
	if(!is_dir($relPath) && file_exists($relPath))
		return addCss($relPath);

	$search = array("type" => "css");
	$files = listFilesDir($relPath, $search);
	return addCss($relPath, $files);
}

function addStylesheet($relPath)
{
	$stylesheet = findInParent($relPath, "night.css", true);
	$stylesheet = diskPathToUrl($stylesheet);

	return addCss($stylesheet);
}

function addCss($relPath, $file="", $local=false, $media=NULL)
{
	if(is_array($file))
	{	
		foreach($file as $f)
			addCss($relPath, $f);
		return;
	}

	$url = combine($relPath, $file);

	if($media)
		$media = " media='$media'";

	if($local && !file_exists($url)) return;
	if (!$url) return;
?><link rel="stylesheet" type="text/css"<?=$media?> href="<?=$url?>"/>
<?php 
	return $url;
}

function addCssFromConfig($key, $file=NULL)
{
	global $APP_DIR;
	$cfg = getConfig($key);
	$relPath = combine($APP_DIR, $cfg["path"]);
	$url = isset($cfg["url"]) ? $cfg["url"] : $relPath;
	setIfNull($file, @$cfg["css"]);
	return addCss($url, $file);
}

function addScriptFromConfig($key, $file=NULL)
{
	global $APP_DIR;
	$cfg = getConfig($key);
	$relPath = combine($APP_DIR, $cfg["path"]);
	$url = isset($cfg["url"]) ? $cfg["url"] : $relPath;
	setIfNull($file, @$cfg["js"]);
	return addScript($url, $file);
}

function writeAttribute($name, $value)
{
    if($name && $value)
        return " $name=\"$value\"";
    return "";
}

function addIcons($icons)
{
    if(!$icons) return;
    $defaultIconSize = 32;
    foreach ($icons as $key => $icon)
    {
        if(is_numeric($key))
        {
            $rel = "icon";
            $size = ($key != $defaultIconSize) ? "$key" . "x$key" : "";
        }
        else
        {
            $rel = $key;
            $size = "128x128";
        }
        $sizeAttr = writeAttribute("sizes", $size);
        $relAttr =  writeAttribute("rel", $rel);
        $href = writeAttribute("href", "images/$icon");
        echo "<link$relAttr$sizeAttr$href/>\n";
    }
}

function addIconsFromConfig()
{
    $icons = getConfig("app.icon");
    debug("addIconsFromConfig", $icons);
    return addIcons($icons);
}

function displayBackground($path, $hidden=false, $printable=false)
{
	$background = findInParent($path, ".bg.jpg", false);
	$background = diskPathToUrl($background);
	$background = str_replace(' ', '%20', $background);
	$class="";
	if($hidden) $class .= " hidden";
	if(!$printable) $class .= " noprint";
	$style = "";
	if ($background) $style = "style=\"background-image: url($background)\"";
?>
	<div id="divbg" class="bg <?php echo $class?>" <?php echo $style?>></div>
<?php
	return $background;
}

//--------------- Facebook API ---------
function facebookCommentsScript()
{?>
	FB.init({appId: '159679107375436', status: true, cookie: true, xfbml: true});
<?php
}

function facebookCommentsBody($path, $width=320, $hidden=false, $dark=false)
{
	$oldFBcommentList=readArray("config/oldFBcomments.txt");
	$oldStyle = in_array($path, $oldFBcommentList);
	//$path="concerts/DamsTomSession";	$oldStyle = true;
?>
<div id="fb-root" <?php if($hidden) echo 'class="hidden"'?>>
	<fb:comments width="<?php echo $width?>" 
	<?php if($dark) echo 'colorscheme="dark"';
	if(!$oldStyle){?> href="<?php echo getAbsoluteUrl($path) ?>"	<?php } 
	else { ?> xid="<?php echo urlencode($path)?>" migrated="1" <?php } ?> />
</div>
<?php
}

function facebookLikeButton($path, $width=320)
{?>
	<fb:like href="<?php echo getAbsoluteUrl($path)?>" width="<?php echo $width?>" send="true" show_faces="true"/>
<?php }

function facebookLikeBox($path, $width=320)
{?>
	<fb:like-box href="<?php echo getAbsoluteUrl($path)?>" width="<?php echo $width?>" show_faces="true" stream="true" header="true"/>
<?php }

function facebookFacepile($path, $width=320)
{?>
	<fb:facepile href="<?php echo getAbsoluteUrl($path)?>" max_rows="2" width="<?php echo $width?>"></fb:facepile>
<?php }


//generate meta tags based on album data
/*
<meta property="og:site_name" content="Le Gorafi.fr Gorafi News Network" />
<meta property="og:type" content="article" />
<meta property="og:locale" content="fr_FR" />
<meta property="fb:app_id" content="142787252530222" />
<meta property="og:url" content="http://www.legorafi.fr/2013/07/22/quest-ce-que-va-changer-la-naissance-du-royal-baby-pour-nous/" />
<meta property="og:title" content="Qu&rsquo;est-ce que va changer la naissance du Royal Baby pour nous?" />
<meta property="og:description" content="." />
<meta property="og:image" content="http://www.legorafi.fr/wp-content/uploads/2013/07/katewilliam.jpg" />
<meta property="og:image:width" content="500" />
<meta property="og:image:height" content="372" />

<meta property="article:published_time" content="2013-07-22T19:47:40+00:00" />
<meta property="article:modified_time" content="2013-07-23T18:54:31+00:00" />
<meta property="article:author" content="http://www.legorafi.fr/author/admin/" />
<meta property="article:section" content="Monde Libre" />
<meta property="article:tag" content="Angleterre" />
<meta name="description" content="<?php echo $description?>"/>
*/

function metaTags($album, $article=true)
{
	if(is_string($album))
		$album = new Album($album, false);

	$path = $album->getPath();
	$relPath= $album->getRelPath();

	$meta=array();
	$meta["fb:app_id"] = number_format(getConfig("fb.app_id"), 0, "", "");
	$meta["twitter:site"] = $meta["og:site_name"] = getSiteName(); //get root dir title	
	$meta["twitter:url"] = $meta["og:url"] = currentUrl(); //getAbsoluteUrl($path);
	$meta["twitter:title"] =  $meta["og:title"] = $album->getTitle();		 //get current dir title	
	$search = $album->getSearchParameters();
	$meta["twitter:description"] = $meta["og:description"] = metaDescription($album, @$search["start"]);
	if(!$meta["twitter:description"])
		$meta["twitter:description"] = $meta["twitter:title"];
	$video = @$search["start"] ? findFirstVideo($relPath, $search) : "";
	$image = findFirstImage($relPath, $search);
//	$image = findFirstImages($relPath, 1, $search);
	metaImage($path, $relPath, $image, $video);

	if($article)
	{
		$meta["article:published_time"] = @formatDate(filectime($relPath), true);	//dir creation date or newest file date?
		$meta["article:modified_time"]  = @formatDate(filemtime($relPath), true);	//dir modified date?
		$meta["article:author"] = "MinorArt"; //uploader username ?

		//list album tags
		$tags=listTagFiles($relPath, $album->getDepth());
		$meta["article:tag"] = array_keys($tags);
	}

	foreach ($meta as $key => $value)
		if($value)
			echo metaTag($key, $value);

	return $meta;
}

function metaImage($path, $relPath, $image, $video=null)
{
	if(!$image && !$video) return;
	if($image && is_array($image))
	{
		foreach ($image as $el)
			metaImage($path, $relPath, $el, $video);
		return;
	}

	$meta=array();

	if($image && is_string($image))
	{
		$imagePath = combine($relPath, $image);
debug("metaImage $image", $imagePath);
		$is = @getimagesize($imagePath);
	//	if(!$is) return;
debug("getimagesize", $is);

		$meta["og:image"] = $meta["twitter:image"] = getAbsoluteFileUrl($path, $image);
		if($is)
		{
			$meta["og:image:width"]  = $is[0];
			$meta["og:image:height"] = $is[1];
			$meta["og:image:type"] = $is["mime"];
		}
	}

	if($video)
		$meta["og:video:url"] = $meta["twitter:stream:url"] =  $meta["twitter:player"] = getAbsoluteFileUrl($path, $video);

	//TODO: for animated gif: og:type=video.other
	$meta["og:type"] = $video ? "video" : "image";
	$meta["twitter:card"] = "summary_large_image"; //$video ? "player" : "summary_large_image";

	foreach ($meta as $key => $value) 
		echo metaTag($key, $value);

	return $meta;
}

function metaDescription($album, $file)
{
	$ad = $album->getDescription();
	$md = $file ? $album->getFileDescription($file) : "";
	if($ad && $md) return "$md. $ad";
	if($ad) return $ad;
	return $md;
}

function metaTag($key, $value)
{
	if(!is_array($value))
		return "\t<meta property=\"$key\" content=\"$value\" />\n";

	$result="";
	foreach ($value as $k => $element)
		$result .= metaTag($key, $element);
	return $result;
}

function metaTagArray($data)
{
	if(!$data) return "";	

	$result="";
	foreach ($data as $k => $element)
		$result .= metaTag($k, $element);
	return $result;
}

// HTML list for the HTML5 player
function html5playlist($relPath,$files)
{?>
<ol id="playlist">
<?php
	$i=0;
	foreach ($files as $file)
	{
		splitFilename($file,$name,$ext);
		$poster=findThumbnail($relPath, $file, ".tn");
		if(!$poster) $poster="icons/play.png";
?>
		<li class="translucent">
			<a href="javascript:playMovie(<?php echo $i++?>)"> <img class="tinyThumb" src="<?php echo $poster?>"/> <?php echo makeTitle($name);?></a>
		</li>
<?php
	}
?>
</ol>
<?php
}


function displayDropDown($ddId, $cssClass, $options, $default, $sort=false, $reverse=false, $keysAsValues=false)
{
	if($cssClass) $cssClass = " class='$cssClass'";
	echo "\n<select id='$ddId'$cssClass>\n";
	//add default if not already in options
	if(!in_array($default,$options))
		$options[]=$default;
	//sort options
debug("sort", $sort);
	if($sort === "key")
		ksort($options);
	else if($sort === "value")
		asort($options);
	else if($sort)
		sort($options);

	if ($reverse)
		$options=array_reverse($options);
debug("options", $options);
	foreach($options as $key=>$value)
	{	
		$selected=($default===$value) || (!is_numeric($key) && $default===$key);
		$selected = $selected ? " selected='selected'" : "";
		$title = strtolower(makeTitle($value));

		if($keysAsValues || !is_numeric($key))
			echo "\t<option value='$key'$selected>$title</option>\n";
		else
			echo	"\t<option value='$value'$selected>$title</option>\n";
	}
	echo "</select>\n";
}

function displayPaginateOptions($cssClass="")
{
	displayDropDown("dd_page", $cssClass, getConfig("PAGINATE.OPTIONS"), getConfig("PAGINATE.DEFAULT"), true);
}

function displaySizeOptions($cssClass="")
{
	displayDropDown("dd_size", $cssClass, getConfig("SIZE.OPTIONS"), getConfig("display.size"));
}

function displaySortOptions($cssClass="")
{
	global $config;
	displayDropDown("dd_sort", $cssClass, $config["SORT"]["OPTIONS"], $config["SORT"]["DEFAULT"]);
	if($cssClass) $cssClass = " class='$cssClass'";
?>
<input id="cb_reverse"<?php echo $cssClass?> type="checkbox" label="I" title="Inverse"/>
<input id="cb_dirsFirst"<?php echo $cssClass?> type="checkbox" label="D" title="directories on top"/>
<?php
}
?>