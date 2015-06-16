<?php
require_once("include/config.php");
session_start();

debugVar("params");
$album = new Album($path, 1);

$relPath = $album->getRelPath();
$title = $album->getTitle();
$siteName = getSiteName();
$pageTitle = ($title == $siteName) ? $title : "$title - $siteName";
$description=$album->getDescription();
$depth=$album->getDepth();

//if FLV files exist: load jw player instead of html5 player
$hasFlash = getConfig("USER_AGENT.USE_FLASH") || $album->filterFiles(array("exts"=>"flv"));
if($hasFlash)
{
	$configFilename = combine(pathToAppRoot(), "config/.config.jwplayer.csv");
	readConfigFile($configFilename, $config); 
}

copyRedirect($relPath);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $pageTitle?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php
if(!empty($description)) {?>
	<meta name="description" content="<?php echo $description?>"/>
<?php }
metaTags($album);
if(isMobile()) {?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, target-densitydpi=device-dpi" />
<?php } ?>

<link type="text/css" rel="stylesheet" href="MediaThingy.css"/>
<?php addStylesheet($relPath);?>
<link rel="alternate" type="application/rss+xml" href="rss.php?path=<?php echo $path?>" title="<?php echo $title?> news"/>

<script type="text/javascript" src="js/lib/jquery.min.js"></script>
<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom.min.js"></script>
<script type="text/javascript" src="js/lib/jsrender.js"></script>
<script type="text/javascript" src="js/lib/jquery.views.js"></script>
<script type="text/javascript" src="js/lib/querystring.js"></script>
<script type="text/javascript" src="js/lib/date.format.js"></script>
<?php addJavascript(getConfig("MediaPlayer.jwplayer.js"))?>

<script type="text/javascript" src="js/mt.extensions.js"></script>
<script type="text/javascript" src="js/mt.extensions.jquery.js"></script>
<script type="text/javascript" src="js/mt.color.js"></script>
<script type="text/javascript" src="js/mt.user.js"></script>
<script type="text/javascript" src="js/mt.mediafile.js"></script>
<script type="text/javascript" src="js/mt.album.js"></script>
<script type="text/javascript" src="js/mt.transition.js"></script>
<script type="text/javascript" src="js/mt.slideshow.js"></script>

<?php 	addJavascript(getConfig("MediaPlayer.js"));
		addJavascript(getConfig("youtube.iframeApiUrl"));
?>

<meta name="mobile-web-app-capable" content="yes" />
<link rel="icon" href="images/folder32.png">
<link rel="icon" sizes="128x128" href="icons/folder.png">
<link rel="apple-touch-icon" sizes="128x128" href="icons/folder.png">
<link rel="apple-touch-icon-precomposed" sizes="128x128" href="icons/folder.png">

<script type="text/javascript" src="js/mt.ui.js"></script>
<script type="text/javascript" src="js/mt.keys.js"></script>
<script type="text/javascript" src="js/mt.index.js"></script>
<script type="text/javascript" src="js/mt.actions.js"></script>
<script type="text/javascript" src="js/mt.templates.js"></script>
<script type="text/javascript" src="js/mt.downloads.js"></script>
<script type="text/javascript" src="js/mt.progressbar.js"></script>
<script type="text/javascript" src="js/phpmyvisites.js"></script>

<script type="text/javascript">
<?php echoJsVar("hasFlash"); ?>
<?php echoJsVar("params"); ?>
var qs = new Querystring();
delete qs.params[qs.whole];
var search = Object.merge(qs.params, params, true);

var config;
UI.transition = new Transition({elementSelector: "div.mediaFileList", type: 2, clear: true, maxType:3, duration: 1000});

$(document).ready(function()
{
	UI.setupElements();
	UI.makeBackgroundGradients();		
	Album.getAlbumAjax("album", search, true); //, albumOnLoad);
});

Album.onLoad = function (albumInstance) 
{
	try 
	{
		if(!albumInstance) return;
		config = albumInstance.config;
		UI.displayUser();
		UI.slideshow = new Slideshow(config.slideshow);
		UI.slideshow.setOptions(search);
		UI.transition.setOptions(config.transition);

		$("#description").html(albumInstance.description);
		$("#dateRange").html(albumInstance.formatDateRange(true));	
		if(isEmpty(albumInstance.mediaFiles))
			$("#pagesTop").html("No files in this album.");
		else
		{
			UI.selectCountPerPage(false);
			var mf=null;
			if(location.hash)
				search.start = location.hash.substringAfter("#");
			if(search.start)
				mf=albumInstance.getMediaFileByName(search.start);

			UI.sortFiles(!mf);

			UI.displayFileCounts(album.mediaFiles,"#counts", true);	
			UI.displayTags();
		}
		
		UI.styleCheckboxes();
		UI.setupEvents();

		//pmv(UI.visitImg);

		$(".lOption").each(UI.toggleLayoutOption); 

		albumInstance.albumTime = new Date() - albumInstance.startTime;
		UI.loadDirThumbnails();
		if(mf) mf.play();
	}
	catch(err) 
	{
		UI.setStatus(err.message);
		UI.addStatus(err.stack); 
	}
};

$(window).resize(function(event)
{
	if(!window.album) return;
	if(UI.mode==="slideshow")
		UI.slideshow.fitImage();

	UI.setContentHeight();
	UI.setContentWidth();
	UI.setColumnWidths();
});
</script>

<?php include("templates.html");?>
</head>
<body class="<?php echo isMobile() && !isIpad() ? "mobile" : "desktop"; ?>"
style="<?php echo getConfig("background.color") ? "background-color: " . getConfig("background.color") : ""; ?>">
<?php $background=displayBackground($relPath, false);?>

<div id="titleContainer" direction="down" callback="setColumnWidths">
	<div id="userDiv" class="floatR right noprint">
		<span id="userLabel"> </span>
		<img class="icon notloggedin" id="loginIcon" src="icons/login.gif" alt="Login" title="login" onclick="User.login('upload')"/>
		<img class="icon loggedin" id="logoutIcon" src="icons/logout.gif" alt="Log out" title="Log out" onclick="User.logout()"/>
<?php if(!isLocal()) {?>		
		<img id="visitImg" class="" alt="" onclick="UI.goToUrl(config.visittracker.url, 'pmv')"/>
<?php	}?>
		<br/>

		<div class="inlineBlock">
			<a class="upload" href=".upload/multiupload.php<?php echo qsParameters("path")?>">M<img class="upload" id="multiUploadIcon" src="icons/upload.png"/></a>
			<br/>
			<a class="upload" href=".upload/upload_form.php<?php echo qsParameters("path")?>"><img class="upload" id="multiUploadIcon" src="icons/upload.png"/></a>
		</div>

<?php if(!isMobile()) {?>		
		<div class="inlineBlock">
			<a class="spaceLeft admin" target="csv" title="date index" href="download.php?file=.dateIndex.csv<?php echo qsParameters("path",false)?>&type=text/plain">DI</a>
			<a class="admin" title="reset date index" href=".admin/delete.php?file=.dateIndex.csv<?php echo qsParameters("path",false)?>"><img src="icons/refresh.png"/></a>
			<br/>
			<a class="spaceLeft admin" target="csv" title="image metadata" href="download.php?file=.metadata.IMAGE.csv<?php echo qsParameters("path",false)?>&type=text/plain">MI</a>
			<a class="admin" title="reset image metadata" href=".admin/delete.php?file=.metadata.IMAGE.csv<?php echo qsParameters("path",false)?>"><img src="icons/refresh.png"/></a>
			<br/>
			<a class="spaceLeft admin" target="csv" title="video metadata" href="download.php?file=.metadata.VIDEO.csv<?php echo qsParameters("path",false)?>&type=text/plain">MV</a>
			<a class="admin" title="reset video metadata" href=".admin/delete.php?file=.metadata.VIDEO.csv<?php echo qsParameters("path",false)?>"><img src="icons/refresh.png"/></a>
		</div>
		<div class="inlineBlock">
		<a class="spaceLeft admin" title="reset best" href=".admin/delete.php?file=.tag/best.csv<?php echo qsParameters("path",false)?>"><img src="icons/star.png"/><img src="icons/delete.png"/></a>
		<br/>
		<a class="spaceLeft admin" title="delete background" href=".admin/delete.php?file=.bg.jpg<?php echo qsParameters("path",false)?>"><img class="admin" src="icons/background.png" alt="background"/><img class="admin" src="icons/delete.png"  alt="delete"/></a>
		</div>
		<div class="inlineBlock">
		<a class="spaceLeft upload" target="test" href="test2.php<?php echo qsParameters("path")?>"><img src="icons/testing.png" alt="description"/></a><br/>
		<a class="spaceLeft upload" href=".upload/description.php<?php echo qsParameters("path")?>"><img src="icons/comment.gif" alt="description"/></a>
		</div>
<?php	}?>
		<div class="inlineBlock right">
			<img class="icon" src="icons/info.png" id="browserInfoIcon" title="browser info" alt="info" onclick="UI.displayBrowserInfo();"/>
			<br/>
			<a class="" target="xml" href="data.php?data=album&format=xml&indent=1<?php echo qsParameters("path,depth,name,type",false)?>"><img src="icons/xml.png" alt="XML" title="XML index"/></a>
			<br/>
			<a class="" target="json" href="data.php?data=album&indent=1<?php echo qsParameters("path,depth,name,type",false)?>"><img src="icons/json_orange.png" alt="JSON" title="JSON index"/></a>
		</div>
	</div>
	<div id="title" class="title">
		<span class="" id="pathLinks">
			<?php pathLinks($path,true)?>
		</span>
		<a class="spaceLeft meme" href="<?php echo "?$path"?>"><?php echo $title?></a>
		<img class="icon" src="icons/thumbnails.png" id="thumbnailsIcon" alt="thumbnails" onclick="UI.setMode()"/>
		<img class="icon" src="icons/slideshow.png" id="slideshowIcon" alt="Slide show" title="Slide show (Space)" onclick="UI.slideshow.display()" />
		<img class="icon" src="icons/play.png" id="playIcon" alt="Play videos" title="Play all videos (V)" onclick="UI.playAllVideos()"/>
		<img class="icon" id="fbIconAlbum" src="icons/fb.png" alt="share" title="share on facebook" onclick="UI.fbShare(album)"/>
	</div>
	<div id="description" class="centered text subtitle"></div>
	<div class="centered">
		<span id="dateRange" class="subtitle"></span>
		<span id="indexStatus" class="status text translucent"></span>
	</div>
	<div id="bar" class="progressBar margin translucent hidden small">
		<div id="progress" class="progress shadowIn inlineBlock nowrap"><span class="progressValue"></span></div>
		<div class="remainingValue right"></div>
	</div>
	<div class="centered noprint controls">
		<img id="ajaxLoader" src="icons/ajax-loadereee.gif"/>
		<div id="textList" class="inlineBlock"  style="vertical-align:middle"></div>
		<span class="nowrap" id="counts"></span>
		<input id="cb_tagList" type="checkbox" class="lOption" label="Tags" title="Header"/>
		<input id="cb_all_tags" type="checkbox" class="operator" icon="icons/intersection10.png" label="All" title="Match all tags (intersect)"/>
		<input id="cb_tag_" type="checkbox" class="tagOption" icon="icons/delete.png" label="" title="untagged"/>
		<div id="tagList" class="inlineBlock"></div>
		<span id="pageCounts" class="translucent"></span>
		<img class="upload icon" src="icons/collage.png" title="Make collage" id="collageIcon" alt="collage" onclick="UI.collage()"/>
	</div>
</div>

<?php include("video.html");?>
<div id="articleContainer" class="hidden translucent centered left text" style="width: 90%; max-width:1000px; padding: 20px;">
</div>

<div id="indexContainer" class="nofloat">
	<div class="centered noprint">
		<span id="pagesTop" class="pager centered"></span>
	</div>
	<div class="floatR">
		<div id="downloadFileList" class="rightPane hidden" direction="right" callback="setContentWidth"></div>
	</div>

	<div id="files" class="container margin">
		<div id="mediaFileList0" class="mediaFileList"></div>
		<div id="mediaFileList1" class="mediaFileList"></div>
	</div>
	<div id="contentFooter" class="wrapper"><?php echo getConfig("FOOTER");?></div>
</div>

<?php include("slideshow.html");?>

<div id="audioContainer" class="footerRightCorner right noprint">
	<div id="musicPlayerMessage" class="text controls"></div>
	<div id="musicPlayerControls" class="controls"></div>	
	<div id="musicPlayerPlaylist" class="controls playlist scrollY"></div>
	<div id="musicPlayer"></div>
</div>

<?php include("options.php");?>
<?php include("edit.html");?>

</body>
</html>