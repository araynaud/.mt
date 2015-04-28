<?php session_start(); 
require_once("include/config.php");
$user=session_logout();

{
	$u=new User();
	echo $u->toJson();
	return;
}

$path=getPath();
$relPath=getDiskPath($path);

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	"user: " <?=$user?><br/>
	<script type="text/javascript">
	</script>
</body>
</html>

