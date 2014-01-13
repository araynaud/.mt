<?php 
error_reporting(E_ERROR | E_PARSE | E_WARNING | E_NOTICE);
session_start(); 
require_once("../include/config.php");

$user = current_user();
$path=getPath($_POST['path']);
$relPath=getDiskPath($path);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link type="text/css" rel="stylesheet" href="../MediaThingy.css"/>
	<?php addStylesheet($relPath); ?>
</head>
<body>
	<?php displayBackground($relPath); ?>
<?php
echo "free space: " . disk_free_space("/") . "<br/>";
echo "getcwd(): " . getcwd() . "<br/>";
echo "user: $user<br/>";

if( !isset($_POST['upload']) ) return;
echo "posted path:" . $_POST['path'];
echo " path: $path / $relPath<br/>";

$file=$_POST['file'];
$newname=isset($_POST['newname']) ? $_POST['newname'] : "readme";
splitFilename($file,$filename,$ext);	
//TODO générer nom fichier apres post, pas avant

//if upload ok, store description
if (isset($_POST['description']) ) 
{
	$desc=$_POST['description'];
	echo "Desc: $desc<br/>";

	if($_POST['upload']=="add")
		$desc_file="$relPath/$newname.txt";
	else
		$desc_file="$relPath/$filename.txt";
		
	echo "storing description : '" . $desc_file . "'<br/>";
	writeTextFile($desc_file, $desc);
	echo "Description stored.";
}

//renommer, pas sur un ajout de commentaires
if($_POST['upload']=="OK" && isset($newname) && $newname != $filename) //rename
{
	echo "renaming.";
	rename("$relPath/$file",			"$relPath/$newname.$ext"); //file
	rename("$relPath/$filename.txt",	"$relPath/$newname.txt"); //description
	rename("$relPath/.tn/$file",		"$relPath/.tn/$newname.$ext");	//image thumb
	rename("$relPath/.tn/$filename.jpg","$relPath/.tn/$newname.jpg"); //video jpg thumb
	rename("$relPath/.ss/$file",   		"$relPath/.ss/$newname.$ext"); //slideshow version

	echo "File $relPath/$file renamed.";
}
?>	
	<a href="../?path=<?php echo $path?>">index</a>
	
	<script type="text/javascript">
	window.location = "../?path=<?php echo $path?>";
	</script>
</body>
</html>