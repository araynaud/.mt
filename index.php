<?php
require_once("include/config.php");
session_start();
$path=getPath();
$album = new Album($path, false);
$relPath=$album->getRelPath();
$title=$album->getTitle();
$description=$album->getDescription();
$depth=$album->getDepth();

copyRedirect($relPath);
$allowJquery=allowJqueryFX();
$allowFacebook=allowFacebook($path);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=9"/>
<?php
if(!empty($description)) {?>
	<meta name="description" content="<?php echo $description?>"/>
<?php }
//prevent scaling on mobile browser
// iOS <meta name="viewport" content="width=1165, user-scalable=no">
if(isMobile()) {?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, target-densitydpi=device-dpi" />
<?php } ?>

<link type="text/css" rel="stylesheet" href="MediaThingy.css"/>
<?php addStylesheet($relPath);?>
<link rel="alternate" type="application/rss+xml" href="rss.php?path=<?php echo $path?>" title="<?php echo $title?> news"/>

<script type="text/javascript" src="js/lib/jquery-1.8.3.js"></script>
<script type="text/javascript" src="js/lib/jquery-ui-1.9.2.custom.min.js"></script>
<script type="text/javascript" src="js/lib/jsrender.js"></script>
<script type="text/javascript" src="js/lib/jquery.views.js"></script>
<script type="text/javascript" src="js/lib/querystring.js"></script>
<script type="text/javascript" src="js/lib/date.format.js"></script>
<script type="text/javascript" src="js/lib/jwplayer510.js"></script>

<script type="text/javascript" src="js/mt.extensions.js"></script>
<script type="text/javascript" src="js/mt.color.js"></script>
<script type="text/javascript" src="js/mt.user.js"></script>
<script type="text/javascript" src="js/mt.mediafile.js"></script>
<script type="text/javascript" src="js/mt.album.js"></script>
<script type="text/javascript" src="js/mt.transition.js"></script>
<script type="text/javascript" src="js/mt.slideshow.js"></script>
<script type="text/javascript" src="js/mt.mediaplayer.js"></script>
<script type="text/javascript" src="js/mt.ui.js"></script>
<script type="text/javascript" src="js/mt.keys.js"></script>
<script type="text/javascript" src="js/mt.index.js"></script>
<script type="text/javascript" src="js/mt.templates.js"></script>
<script type="text/javascript" src="js/mt.downloads.js"></script>
<script type="text/javascript" src="js/mt.progressbar.js"></script>

<script type="text/javascript">
var config;
UI.transition = new Transition({elementSelector: "div.mediaFileList", type: 2, clear: true, maxType:3, duration: 1000});
$(document).ready(function()
{
	UI.setupElements();
	UI.makeBackgroundGradients();		

	var search = new Querystring().params;
	Album.getAlbumAjax("album", search); //, true, albumOnLoad);
	if(!window.album) return;

	config = album.config;
	UI.displayUser();
	UI.slideshow = new Slideshow(config.slideshow);
	UI.transition.setOptions(config.transition);

	$("#description").html(album.description);
	$("#dateRange").html(album.formatDateRange(true));	
	if(!album.mediaFiles)
	{
		$("#pagesTop").html("No files in this album.");
		return;
	}

	UI.selectCountPerPage(false);
	UI.sortFiles(!search.start);
	UI.displayFileCounts(album.mediaFiles,"#counts");	
	$("#slideshowIcon").toggle(album.hasFiles("IMAGE"));
	$("#playIcon").toggle(album.hasFiles("VIDEO"));

	UI.styleCheckboxes("checkButton");

	UI.setupEvents();

	if(search.start)
		UI.slideshow.display(search.start);
});

//show all thumbnail images if in cache
$(window).load(function()
{	
	if(!window.album) return;

	$(".lOption").each(UI.toggleLayoutOption); 
/*	.each(function()
	{
		var id = this.id.substringAfter("_");
		$('#'+id).toggleEffect($(this).isChecked());
	});*/
});
//if columns : new columns on a different row.
//make page container .nofloat
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
<body class="<?php echo isMobile() && !isIpad() ? "mobile" : "desktop"; ?>">
<?php $background=displayBackground($relPath, false);?>

<div id="titleContainer" direction="down" callback="UI.setColumnWidths">
	<div id="userDiv" class="floatR right noprint">
		<span id="userLabel"> </span>
		<img class="icon notupload" id="uploadLoginIcon" src="icons/upload.png" alt="Upload login" title="upload" onclick="User.login('upload')"/>
		<img class="icon notadmin" id="adminLoginIcon" src="icons/login.gif" alt="Admin" title="admin" onclick="User.login('admin')"/>
		<img class="icon upload" id="logoutIcon" src="icons/logout.gif" alt="Log out" title="Log out" onclick="User.login()"/>
		<br/>
		<img class="icon" src="icons/info.png" id="browserInfoIcon" alt="info" onclick="UI.displayBrowserInfo();"/>
		<a class="spaceLeft upload" href=".upload/multiupload.php<?php echo qsParameters("path")?>">M<img class="upload" id="multiUploadIcon" src="icons/upload.png"/></a>
		<a class="spaceLeft upload" href=".upload/upload_form.php<?php echo qsParameters("path")?>"><img class="upload" id="multiUploadIcon" src="icons/upload.png"/></a>
		<a class="spaceLeft admin" target="csv" title="view date index" href="download.php?file=.dateIndex.csv<?php echo qsParameters("path",false)?>&type=text/plain">DI</a>
		<a class="admin" title="reset date index" href=".admin/delete.php?file=.dateIndex.csv<?php echo qsParameters("path",false)?>"><img src="icons/refresh.png" alt="description"/></a>
		<a class="spaceLeft admin" title="delete background" href=".admin/delete.php?file=.bg.jpg<?php echo qsParameters("path",false)?>"><img class="admin" src="icons/delete.png"  alt="delete"/><img class="admin" src="icons/background.png" alt="background"/></a>

		<a class="spaceLeft upload" href="test.php<?php echo qsParameters("path")?>"><img src="icons/testing.png" alt="description"/></a>
		<a class="spaceLeft upload" href=".upload/description.php<?php echo qsParameters("path")?>"><img src="icons/comment.gif" alt="description"/></a>
		<a class="spaceLeft" target="xml" href="data.php?data=album&format=xml&indent=1<?php echo qsParameters("path,depth,name,type",false)?>"><img src="icons/xml.png" alt="XML" title="XML index"/></a>
		<a class="spaceLeft" target="json" href="data.php?data=album&format=json&indent=1<?php echo qsParameters("path,depth,name,type",false)?>"><img src="icons/json_orange.png" alt="JSON" title="JSON index"/></a>
<?php if(isLocal()) {?>		
		<a class="spaceLeft" href="<?php echo getLocalUrl($relPath)?>"><img src="icons/explorer.gif" alt="Explorer" title="View in explorer"/></a>
<?php	}?>
<?php if(getConfig("downloads.enabled")) {?>		
		<img class="icon" id="downloadAllIcon" src="icons/download.gif" alt="Download" title="Download selected files"/>
		<img class="icon upload" id="uploadAllIcon" src="icons/upload16.png" alt="Upload" title="Upload selected files"/>
<?php	}?>
	</div>
	<div id="title" class="title">
		<span class="small" id="pathLinks">
			<?php pathLinks($path,true)?>
		</span>
		<a class="spaceLeft" href="<?php echo $relPath?>"><?php echo $title?></a>
		<span class="small" id="counts"></span>
		<img class="icon" src="icons/slideshow.png" id="slideshowIcon" alt="Slide show" title="Slide show (Space)" onclick="UI.slideshow.display()" />
		<img class="icon" src="icons/play.png" id="playIcon" alt="Play videos" title="Play all videos (V)" onclick="UI.playAllVideos()"/>
		<img class="icon" src="icons/music.png" id="playMusicIcon" alt="Music" onclick="MediaPlayer.audio.loadMusicPlaylist(album.musicFiles)"/>
		<img class="icon" src="icons/thumbnails.png" id="makeThumbnailsIcon" alt="thumbnails" onclick="Album.createMissingThumbnails(album.mediaFiles);"/>
	</div>
	<div id="description" class="centered text"></div>
	<div id="dateRange" class="centered text"></div>
	<div id="bar" class="progressBar margin translucent hidden small">
		<div id="progress" class="progress shadowIn inlineBlock nowrap"><span class="progressValue"></span></div>
		<div class="remainingValue right"></div>
	</div>
	<div class="centered">
		<span id="indexStatus" class="status text translucent"></span>
	</div>
	<div id="pageCounts" class="floatR small bold"></div>
</div>
	
<div id="videoContainer" class="wrapper hidden">
	<div class="floatL controls" id="videoBackDiv">
		<img class="iconTrans" id="indexButtonBig" src="icons/close128.png" onclick="UI.setMode()" title="back to thumbnails (Esc)" alt="back to thumbnails"/>
	</div>

	<div class="inlineBlock">
		<div class="translucent right">
			<span id="videoPlayerMessage" class="text"></span>
		</div>
		<div id="videoPlayer"></div>
	</div>
	<div class="inlineBlock left">
		<div class="translucent right">
			<span id="videoPlayerControls"></span>
		</div>
		<ul id="videoPlayerPlaylist" class="playlist scrollY"></ul>
	</div>
</div>

<div id="indexContainer" class="nofloat">
	<div class="floatR">
		<div class="right noprint">
			<input id="cb_downloadFileList" type="checkbox" class="lOption" label="F" title="Files"/>
			<input id="cb_titleContainer" type="checkbox" class="lOption" label="H" title="Header"/>
		</div>
		<div id="downloadFileList" class="rightPane hidden" direction="right" callback="UI.setContentWidth"></div>
	</div>
	<div id="pagesTop" class="pager centered"></div>
	<div id="files" class="container margin">
		<div id="mediaFileList0" class="mediaFileList"></div>
		<div id="mediaFileList1" class="mediaFileList"></div>
	</div>
	<div id="contentFooter" class="wrapper">
		<img class="icon" id="loadMoreIcon" alt="load more" title="load more" src="icons/media-next.png" href="#" onclick="UI.appendNextPage();"/>
	</div>
</div>

<div id="audioContainer" class="footerRightCorner right noprint">
	<div id="musicPlayerMessage" class="text"></div>
	<div id="musicPlayerControls"></div>	
	<ul id="musicPlayerPlaylist" class="playlist scrollY"></ul>
	<div id="musicPlayer"></div>
</div>

<div id="editDiv" class="hidden edit">
	<img class="icon upload floatL rotateIcon" id="rotateIcon_left" src="icons/rotate-left.png" alt="L" title="Rotate Left"/>
	<img class="icon upload floatR rotateIcon" id="rotateIcon_right" src="icons/rotate-right.png" alt="R" title="Rotate Right"/>

	<input class="notdir" id="cb_selected" type="checkbox" label="X" title="Select"/>

	<img class="" id="dataIcon" src="icons/info.png" alt="convert" onclick="UI.goToActionPage('data', null, 'data')"/>

	<img class="icon upload notdir" id="editIcon" src="icons/edit.gif" alt="edit" onclick="UI.goToActionPage('.upload/imageEdit')"/>
	<img class="icon upload" id="commentIcon" src="icons/comment.gif" alt="description" onclick="UI.goToActionPage('.upload/description')"/>
	<img class="icon upload video" id="convertIcon" src="icons/step_next.png" alt="convert" onclick="UI.goToActionPage('.upload/video_convert', {debug:true, format:'ajax'}, 'convert')"/>
	<img class="icon admin notdir" id="parentIcon" src="icons/parent.gif" onclick="UI.confirmFileAction('move','..')" title="move to parent" alt="move to parent"/>
	<img class="icon admin notdir" id="bestIcon" src="icons/star.png" onclick="UI.confirmFileAction('move','best')" title="move to best" alt="best"/>
	<img class="icon admin notdir" id="deleteIcon" src="icons/delete.png" onclick="UI.confirmFileAction('move')" title="delete" alt="delete"/>
	<img class="icon admin notdir" id="refreshIcon" src="icons/refresh.png" onclick="UI.refreshThumbnail(this)" title="refresh" alt="refresh"/>
	<img class="icon admin notdir" id="backgroundIcon" src="icons/background.png" onclick="UI.confirmFileAction('background')" title="set as background" alt="background"/>
</div>

<?php include("slideshow.html");?>
<?php include("options.php");?>

</body>
</html>