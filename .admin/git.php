<?php
require_once("../include/config.php");
setContentType("text", "plain");

$gitPath = getExePath($exe="GIT", $key="_GIT");

if(!$gitPath || !file_exists($gitPath)) 
{
	echo "git disabled.";
	return;
}

echo "\nstatus:\n";
echo execCommand(makeCommand("[0] status", $gitPath));
echo "\npull:\n";
echo execCommand(makeCommand("[0] pull --rebase", $gitPath));
?>