Start
<?php 
require_once("../include/config.php");
startTimer();

if(isset($argv))
	print_r($argv);

$args = getNamedArgs();

sleep(1);
foreach ($args as $key => $value) 
{
	echo "$key: $value\n"; 
	sleep(1);
}
?>
The end.
