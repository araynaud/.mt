<?php session_start(); 

require_once("../include/config.php");

$user=session_login();
$path=getPath($_POST['path']);
$content_dir=getRelPath($path);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
<?php displayBackground($relPath); 

echo "free space: " . disk_free_space("/") . "<br/>";
echo "getcwd(): " . getcwd() . "<br/>";
echo "user: $user<br/>";

if( !isset($_POST['upload']) ) return;
		
$file=$_POST['file'];
$newname=$_POST['newname'];
splitFilename($file,$filename,$ext);	

if (!empty($_POST['description']) ) //si upload ok, stocker description
{
	$desc_file="$content_dir/$file";
	echo "storing description : " . $desc_file . "<br/>";
	writeTextFile($desc_file, $_POST['description']);
	echo "Description stored.";
}

if(isset($newname) && $newname != $filename) //rename
{
	echo "renaming.";
	rename("$content_dir/$file", "$content_dir/$newname.$ext"); //file
	echo "File $content_dir/$file renamed.";
}
?>	
	<a href="../?path=<?php echo $_POST['path']?>">index</a>
	
	<script type="text/javascript">
		window.location = "../?path=<?php echo $_POST['path']?>";
	</script>
</body>
</html>