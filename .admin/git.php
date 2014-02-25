<?php
require_once("../include/config.php");
setContentType("text", "plain");

$gitPath = getConfig("_GIT.PATH");

if(!$gitPath || !file_exists($gitPath)) 
{
	echo "git disabled.";
	return;
}

echo "\nstatus:\n";
echo execCommand("$gitPath status");
echo "\npull:\n";
echo execCommand("$gitPath pull --rebase");
?>