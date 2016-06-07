<?php 
require_once("../include/config.php");
startTimer();
setContentType("text", "plain");

$background = getParam("background");

echo "\n========= start script\n";
$output = execPhp("async", $_GET, $background);
print_r($output);
$time = getTimer(true);
echo "\n========== end script $time \n";
?>
