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
		echo	"/ <a href=\"?path=$pp\">$titleArray[$i]</a> ";
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


function addScripts($relPath)
{
	if(!is_dir($relPath) && file_exists($relPath))
		return addScript($scriptPath);

	$search =  array();
	$search["type"]="js";
	$files = listFiles($relPath, $search);

	foreach($files as $file)
		addScript($relPath, $file);

	return $files;
}

function addScript($scriptPath, $file="")
{
	if (empty($scriptPath)) return false;
?>	<script type="text/javascript" src="<?php echo combine($scriptPath, $file) ?>"></script>
<?php
	return $scriptPath;
}

function addJavascript($url)
{
	if (!empty($url)) {?>
<script type="text/javascript" src="<?php echo $url?>"></script>
<?php }
}

function addStylesheet($relPath)
{
	$stylesheet = findInParent($relPath,"night.css",true);
	$stylesheet = diskPathToUrl($stylesheet);

	if (!empty($stylesheet)) {
?><link type="text/css" rel="stylesheet" media="screen" href="<?php echo $stylesheet?>"/>
<?php }
	return $stylesheet;
}

function displayBackground($path, $hidden=false)
{
	$background = findInParent($path, ".bg.jpg", false);
	$background = diskPathToUrl($background);
	$class=isIE()? "bgIE" : "bg";
	if (!empty($background)) {
?>	<div id="divbg" class="bg noprint">
		<img id="imgbg" src="<?php echo $background?>" alt="" class="stretch<?php if($hidden) echo " hidden" ?>"/>
	</div>
<?php }
	return $background;
}

// ---------Visit tracker: http://st.free.fr/phpmyvisites.php
function visitBody()
{
	$path=reqPath();
	if(isLocal()) return;
?>
<a id="visitLink" href="http://st.free.fr/" title="Free web analytics, website statistics" onclick="window.open(this.href);return(false);">
	<script type="text/javascript">
		var a_vars = Array();
		var pagename="<?php echo combine($path,currentScriptName());?>";
		var phpmyvisitesSite = 238250;
		var phpmyvisitesURL = "http://st.free.fr/phpmyvisites.php";
	</script>
	<script type="text/javascript" src="http://st.free.fr/phpmyvisites.js"></script>
</a>
<?php
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
	$meta["og:site_name"] = getDirConfig("", "TITLE"); //get root dir title	
	$meta["og:url"] = currentUrl(); //getAbsoluteUrl($path);
	$meta["og:title"] = $album->getTitle();		 //get current dir title	

//TODO: image: 1st best, or 1st image, use maxcount ?
//or if start use this one
	$mediaFile = MediaFile::getMediaFile();
	debug("mediaFile", $mediaFile);
	$meta["og:description"] = metaDescription($album, $mediaFile);

//TODO: mediaFile method findOgImage
	$image="";
	if(!$mediaFile)
		$image = findFirstImages($relPath,4);
	else
		$image = $mediaFile->getBestImage(1000);
debug("metaTags image", $image);

	if($article)
	{
		$meta["article:published_time"] = formatDate(filectime($relPath), true);	//dir creation date or newest file date?
		$meta["article:modified_time"]  = formatDate(filemtime($relPath), true);	//dir modified date?
		$meta["article:author"] = "MinorArt"; //uploader username ?

		//list album tags
		$tags=listTagFiles($relPath, $album->getDepth());
		$meta["article:tag"] = array_keys($tags);
	}

	foreach ($meta as $key => $value) 
		echo metaTag($key, $value);

	metaImage($path, $relPath, $image);

	return $meta;
}

function metaImage($path, $relPath, $image)
{
	if(!$image) return;
	if(is_array($image))
	{
		foreach ($image as $el)
			metaImage($path, $relPath, $el);
		return;
	}

	$is = @getimagesize(combine($relPath, $image));
	if(!$is) return;
debug("getimagesize", $is);
	$meta=array();
	$meta["og:image"] = getAbsoluteFileUrl($path, $image);
	$meta["og:image:width"]  = $is[0];
	$meta["og:image:height"] = $is[1];
	$meta["og:image:type"] = $is["mime"];

	foreach ($meta as $key => $value) 
		echo metaTag($key, $value);

	return $meta;
}

function metaDescription($album, $mediaFile)
{
	$ad = $album ? $album->getDescription() : ""; 
	$md = $mediaFile ? $mediaFile->getDescription() : "";
	if($ad && $md) return "$ad. $md";
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
	if($sort = "key")
		ksort($options);
	else if($sort)
		asort($options);

	if ($reverse)
		$options=array_reverse($options);

	foreach($options as $key=>$value)
	{	
		$selected=($default===$value) || (!is_numeric($key) && $default===$key);
		$selected = $selected ? " selected='selected'" : "";
		$title = strtolower(makeTitle($value));

		if($keysAsValues || !is_numeric($key))
			echo "\t<option value='$key'$selected>$title</option>\n";
//		else if($title === $value)
//			echo	"\t<option$selected>$value</option>\n";
		else
			echo	"\t<option value='$value'$selected>$title</option>\n";
	}
	echo "</select>\n";
}

function displayPaginateOptions($cssClass="")
{
	global $config;
	displayDropDown("dd_page", $cssClass, $config["PAGINATE"]["OPTIONS"], $config["PAGINATE"]["DEFAULT"], true);
}

function displaySizeOptions($cssClass="")
{
	global $config;
	displayDropDown("dd_size", $cssClass, $config["SIZE"]["OPTIONS"], $config["SIZE"]["DEFAULT"]);
	//, "key", false, true);
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