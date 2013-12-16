<?php
/*load comments for a file, format them:
author	 		date
text
*/
require_once("include/config.php");
//error_reporting(E_PARSE);
$path=getPath();
$relPath=getRelPath($path);

$file=getParam("file","readme.txt");
splitFilename($file,$name,$ext);

$description=readTextFile("$relPath/$name.txt");

$comments=listFilesStartingWith($relPath,$name,"txt"); //find comments
//echo "$relPath/$name.txt";

if(empty($description)&&empty($comments))
	header("HTTP/1.0 404 Not Found");

if(!empty($description))
{
	echo "<span class=\"big\">" . utf8_encode($description) . "</span>";
}
if(!empty($comments))
{
	foreach ($comments as $comment_file)
	{		
		$description=readTextFile("$relPath/$comment_file");
		$name_tokens=explode(".",$comment_file);
		if(count($name_tokens)>2)
		{
			unset($name_tokens[count($name_tokens)-1]); //remove .txt
			$nb=$name_tokens[1];
			$author=$name_tokens[2];
			$comment_date = date ("j M Y G:i", filemtime("$relPath/$comment_file"));
			echo "<p>$author&nbsp;<span class=\"small\">$comment_date</span><br/>";
			echo utf8_encode($description) . "</p>";
		}
	}
}
?>