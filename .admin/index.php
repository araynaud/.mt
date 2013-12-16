<?php session_start(); 
require_once("../include/config.php");
set_admin();
$path=getPath();
$relPath=getRelPath($path);
$user=session_login();

$format=getParam('format','html');
if($format=="ajax")
{
	$u=new User();
	echo $u->toJson();
	return;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
	"user: " <?php echo $user?><br/>
	<script type="text/javascript">
		window.location = "../?path=<?php echo $path?>";
	</script>
</body>
</html>
