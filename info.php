<a href="./">back</a>
<pre>
<?php
$configFile="include/config.php";
if(file_exists("../$configFile"))
	$configFile = "../$configFile";
require_once("$configFile");

setDebugMode();

debug("current dir", realpath("."));
debug("config File " . $configFile, realpath($configFile));
debug("User Agent", $_SERVER["HTTP_USER_AGENT"]);
debug("user", get_current_user());

$mappings = getConfig("_mapping");
debugVar("mappings", "print_r");

foreach ($mappings as $key => $dir)
{	
//	if($key == "_") continue;
	$path = realpath($dir);
	if(!$path) continue;
	$files = scandir($path);
	debug($key, $files, true);
}

getTimer(true);
setDebugMode(false);
?>
</pre>

<?php
phpinfo();
getTimer(true);
?>

