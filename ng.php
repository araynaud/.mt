<?php
require_once("include/config.php");
session_start();
$offline = getConfig("debug.offline");
?>
<!doctype html>
<html ng-app="app">
<head>
  <meta charset="utf-8">	
  <title>Media Thingy</title>
<?php
  //addCssFromConfig("lib.bootstrap"); 
  addAllCss(".");
  addScriptFromConfig("lib", "jquery.min.js");
  addScriptFromConfig("lib.bootstrap");
  addScriptFromConfig("lib.angular"); 
  addScriptFromConfig("lib"); 
  addScriptFromConfig("MediaThingy");
  addAllScripts("ng");
//  if(!$offline)     addJavascript("https://www.youtube.com/iframe_api"); 
  $mapping["dirs"] = array_keys(getConfig("_mapping"));
  $mapping["root"] = getConfig("_mapping._root");
?>
<script type="text/javascript">
<?php echoJsVar("mapping");  echoJsVar("config"); ?>
</script>
</head>
<body>
    <div id="main" ui-view></div>
</body>
</html>