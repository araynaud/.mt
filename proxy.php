<?php
//echo "open URL: allow_url_fopen='" .  ini_get('allow_url_fopen') . "'\n";
header('content-type: application/json; charset=utf-8');
$url = @$_SERVER['PATH_INFO'];
$url = substr($url, 1);

if($_SERVER['QUERY_STRING'])
	$url .= "?" . @$_SERVER['QUERY_STRING'];
	
//if(!$url) $url="minorart.free.fr/.mt/data.php?data=album";
$url = "http://$url";
$contents=file_get_contents($url);
echo $contents;
?>