<?php
function isLocal()
{
	// echo "REMOTE_ADDR ". $_SERVER["REMOTE_ADDR"] ." ";
	// echo "HTTP_HOST " . $_SERVER["HTTP_HOST"] ." ";
	// if(isset($_SERVER["LOCAL_ADDR"]))
		// echo "LOCAL_ADDR " . $_SERVER["LOCAL_ADDR"] ." ";
	// if(isset($_SERVER["SERVER_ADDR"]))
		// echo "SERVER_ADDR ". $_SERVER["SERVER_ADDR"] ." ";

	return $_SERVER["REMOTE_ADDR"] == "::1" 
	 || $_SERVER["REMOTE_ADDR"] == $_SERVER["HTTP_HOST"]
	 || isset($_SERVER["LOCAL_ADDR"]) && $_SERVER["REMOTE_ADDR"] == $_SERVER["LOCAL_ADDR"];
}

function isUnix()
{
	return contains($_SERVER["SERVER_SOFTWARE"], "unix");
}

function clientIs($str)
{
	global $config;
	$userAgent = @$_SERVER['HTTP_USER_AGENT'] . " " . @$config["USER_AGENT"]["FORCE"];
	return contains($userAgent, $str);
//	return isset($_SERVER['HTTP_USER_AGENT']) && contains($_SERVER['HTTP_USER_AGENT'], $str);
}

//user agent functions
function isIpad()
{
	return clientIs("iPad");
}

function isKindle()
{
	return clientIs("Kindle") ||  clientIs("Silk");
}

function isMobile()
{
	return clientIs("mobile");
}

function isAndroid()
{
	return clientIs("Android");
}

function isFirefox()
{
	return clientIs("Firefox");
}

function isChrome()
{
	return clientIs("chrome");
}

function isPlaystation()
{
	return clientIs("Playstation");
}

function isIE()
{
	return clientIs("MSIE");
}


function checkUserAgent()
{
	global $config;
	$devices = @$config["USER_AGENT"]["DEVICES"];
	if(!$devices) return array();
	$config["USER_AGENT"]["DEVICES"] = array_filter($devices, "clientIs");
	return $config["USER_AGENT"]["DEVICES"];
}


//get request parameter from query string
function getParam($name, $default="")
{	
	return isset($_GET[$name]) && (@$_GET[$name]!="") ? utf8_decode($_GET[$name]) : $default;
}

//get request parameter from GET or POST
function reqParam($name, $default="")
{	
	return isset($_REQUEST[$name]) && (@$_REQUEST[$name]!="") ? utf8_decode($_REQUEST[$name]) : $default;
}

//get request parameter from POST body only
function postParam($name, $default="")
{	
	return isset($_POST[$name]) && (@$_POST[$name]!="") ? utf8_decode($_POST[$name]) : $default;
}

//get request parameter
function getParamBoolean($name,$default=false)
{	
	if(!isset($_GET[$name]))	return $default;
	return parseBoolean($_GET[$name],$default);
}

function reqParamBoolean($name,$default=false)
{	
	if(!isset($_REQUEST[$name]))	return $default;
	return parseBoolean($_REQUEST[$name],$default);
}

function postParamBoolean($name,$default=false)
{	
	if(!isset($_POST[$name]))	return $default;
	return parseBoolean($_POST[$name],$default);
}

function parseBoolean($var,$default=false)
{	
	if(!isset($var))
		return $default;
	if($var=="0")
		return false;
	if(!strcasecmp($var,"false"))
		return false;
	if(!strcasecmp($var,"f"))
		return false;
	if(empty($var))
		return $default;

	return true;
}

function getParamNumOrBool($name,$default=0)
{
	$result=getParam($name,$default);
	if(is_numeric($result))
		return $result;
	return getParamBoolean($name,$default);
}

function getLocalUrl($relPath,$file="")
{
	$localUrl = combine($relPath, $file);
	$localUrl = realpath($localUrl);
	$localUrl = str_replace("\\", "/", $localUrl);
	if(is_dir($localUrl))	$localUrl .= "/";
	//return $localUrl;
	return "file:///$localUrl";
}

function getShareUrl($sharePath, $relPath, $file="")
{
	$localUrl = combine($sharePath, $relPath, $file);
	$localUrl = realpath($localUrl);
	$localUrl = str_replace("\\", "/", $localUrl);
	return "file:///" . $localUrl;
}

function setContentType($ct="text", $type="")
{
	$contentType = combine($ct, $type);
	if(isDebugMode()) 
		debug("Content-Type", $contentType);
	else
		header("Content-Type: $contentType");
}

function setHeader($header, $value="")
{
	if(isDebugMode()) 
		debug($name, $value);
	else if($value)
		header("$header: $value");
	else
		header($header);
}


function sendFileToResponse($relPath,$file,$contentType="",$attachment=true)
{
	$filePath=combine($relPath,$file);
	if(!file_exists($filePath) || filesize($filePath)===0) return;
	
	$src_info = getimagesize($filePath);
	$img_type=$src_info["mime"];

	if($contentType)
		 setContentType($contentType);
	//return;
	setHeader("Content-Length", filesize($filePath));
	if($attachment)
	{
		$file=cleanupFilename($file);
		setHeader("Content-Disposition", "attachment; filename=$file");
	}
	$fp = fopen($filePath, 'rb'); 
	//stream the image directly from the generated file
	fpassthru($fp);
	fclose($fp);	
}

function printlastError()
{
	$lastError=error_get_last();
	if($lastError)
		echo jsValue($lastError);
}

//get URL
//return response body
//or store in file and return boolean
function curlGet($url, $file=null, $username="", $password="")
{
	$timeout = 5;
	$fp=null;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	if($file && is_string($file))
	{
		$fp = fopen($file, "w");
		if($fp)	curl_setopt($ch, CURLOPT_FILE, $fp);
	}
	else
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if($username && $password) //basic auth
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

	$response = curl_exec($ch);
	curl_close($ch);
	if($fp)	fclose($fp);
	return $response;
}

/*
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  
	curl_setopt($ch, CURLOPT_HEADER, true); 
CURLAUTH_ANY is an alias for CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
CURLAUTH_ANYSAFE is an alias for CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
*/

function curlPostData($url, $data, $username="", $password="")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if($username && $password) //basic auth
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");  

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function curlPostFile($url, $filePath, $username="", $password="", 	$data=array())
{
    $data["filePath"] = $filePath;
    $data["file"] = "@$filePath";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	if($username && $password) //basic auth
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");  

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}


function uploadFile($publish, $filePath, $destPath, $postData = array())
{
debug("uploadFile", $filePath);
	if(!file_exists($filePath)) return false;

	$postData["path"] = $destPath;
	$postData["debug"] = BtoS(isDebugMode());
debug("POST data",$postData);
	$url = combine($publish["url"], getConfig("_publish.script"));
	debug("uploadFile to $destPath", $url);
	return curlPostFile($url, $filePath, @$publish["username"], @$publish["password"], $postData);
}

// send headers to prevent caching
function preventCaching()
{
	if(isDebugMode()) return;
	
	$date = microtime(true) - 24*60*60;
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', $date).' GMT', true, 200);
    header('Content-transfer-encoding: binary');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false );
	header('Pragma: no-cache');  
}
 ?>