<?php
require_once("include/config.php");

$mf = MediaFile::getMediaFile();
debugVar("mf");
if(!$mf) return;

debug($path, urlencode($path));

$fileUrl = $mf->getFileUrl("mp4"); 
$imageUrl = $mf->getThumbnailUrl("ss");
$playerUrl = getConfig("MediaPlayer.jwplayer.flash");
debugVar("playerUrl");
$params = array("file" => $fileUrl); //, "autostart"=>"true");
$playerUrl = getAbsoluteUrl("",$playerUrl, $params);
debugVar("playerUrl");

$embedUrl = currentUrl();
debugVar("embedUrl");
$sharerUrl = "https://www.facebook.com/sharer/sharer.php?" . http_build_query(array("u" => $embedUrl));
$debugUrl = "https://developers.facebook.com/tools/debug/og/object?" . http_build_query(array("q" => $embedUrl));

$stylesheetUrl = "/" . getAppRoot() . "/MediaThingy.css";
$description = $mf->getDescription();
if(!$description) $description = $mf->getAlbumDescription();
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $mf->getTitle()?></title>
    <link type="text/css" rel="stylesheet" href="<?php echo $stylesheetUrl?>" />
    <meta property="og:type" content="movie" /> 
    <meta property="og:url" content="<?php echo $embedUrl ?>" /> 
    <meta property="og:site_name" content="<?php echo getDirConfig("", "TITLE")?>" /> 
    <meta property="og:title" content="<?php echo $mf->getTitle() ?>" /> 
    <meta property="og:description" content="<?php echo $description ?>" />
    <meta property="og:video:height" content="260" /> 
    <meta property="og:video:width" content="420" /> 
    <meta property="og:video:type" content="application/x-shockwave-flash" />
    <meta property="og:image" content="<?php echo $imageUrl?>" />
    <meta property="og:video" content="<?php echo $playerUrl?>" /> 
  </head>
  <body>
  <img src="<?php echo $imageUrl?>"/><br/>
  <a href="<?php echo $imageUrl?>">image</a>
  <br/>
  
  <video src="<?php echo $fileUrl?>"></video>
  <br/>
  <a href="<?php echo $fileUrl?>">video</a>
  <a href="<?php echo $playerUrl?>">player</a>
  <br/>  
  <a href="<?php echo $sharerUrl?>">FB sharer</a>
  <a href="<?php echo $debugUrl?>">FB OpenGraph debug</a>

  </body>
</html>