<?php
require_once("../include/config.php");

header("Content-Type: text/plain");	

$url=getParam("url");

$path=getPath();
$relPath=getDiskPath($path);
$file=getParam("file");
$filePath = $file ? combine($relPath, $file) : "";

$qs = http_build_query($_GET);

echo "$qs\n";
echo "$url\n";
echo "$filePath / " . realpath ($filePath);

$username="arthur";
$password="4nawak";
$data = curlGet($url, $filePath, $username, $password);
echo "curlGet data:" .$data;
?>
