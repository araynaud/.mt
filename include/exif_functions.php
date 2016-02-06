<?php 
//Get EXIF data for an image
function getExifDateTaken($filename, $exif=NULL)
{
  if(!$exif)
      $exif=getImageMetadata($filename);
  if(!$exif)  return "";

  $date = arrayGetCoalesce($exif, "DateTime", "IFD0.DateTime");
  debug("getExifDateTaken DateTime " . strlen($date), "'$date'");
  if(strlen($date) < 18) $date ="";

  if(!$date)
    $date = arrayGetCoalesce($exif, "DateTimeOriginal", "EXIF.DateTimeOriginal");
  debug("getExifDateTaken DateTimeOriginal " . strlen($date), $date);
  if(strlen($date) < 18) $date="";

  if(!$date && $iptcDate = getIptcDate($exif))
    return $iptcDate;

  if(!$date)
      return "";

  //replace :: in date
  if(contains($date,"/")) return strtotime($date);

  $pos = strpos($date,':');
  if($pos===false)  return $date;

  $date[$pos]='-';
  if($pos>4) $date=deleteChars($date,4,$pos);

  $pos = strpos($date,':');
  $date[$pos]='-';
  if($pos>7) 
      $date=deleteChars($date,7,$pos);
  debug("getExifDateTaken", $date);
  return $date;
}

function isExifLibAvailable()
{
  return is_callable("exif_imagetype");
}

function getExifData($filename, $arrays=false, $computed=false)
{
	if(!isExifLibAvailable())	return array();

//	global $config;
	$imagetype = @exif_imagetype($filename);	
	$types = getConfig("EXIF_SUPPORTED_TYPES");
	if(!$imagetype || !in_array($imagetype, $types))		return array();
	//Get EXIF data for an image
	$exif = @exif_read_data($filename, null, $arrays, false);
	if($exif && !$computed)
		unset($exif["COMPUTED"]);
	return $exif;
}

function getExifThumbnail($filename)
{
  debug("getExifThumbnail",$filename);
  if(!fileIsImage($filename) || !isExifLibAvailable())    return;
    
  global $config;
  $imagetype = @exif_imagetype($filename);  
  if(!in_array($imagetype,$config["EXIF_SUPPORTED_TYPES"]))
    return "";
  //Get EXIF data for an image
  return exif_thumbnail($filename);
}


function displayExifData($exif)
{
    foreach ($exif as $key => $section)
    {
        if(!is_array($section))
        {
            echo "$key: $section\n";
            continue;
        }
        echo "$key\n";
        foreach ($section as $name => $val)
        if($name!="MakerNote")
        echo "$name: $val\n";
        echo "\n";
    }
}

// Extract metadata from uploaded image
function getImageMetadata($filename)
{
debug("getImageMetadata", $filename);
    $exif = getExifData($filename); //, true, true);
debug("getImageMetadata getExifData", $exif);
  
//    $exif['format'] = getImageSizeInfo($filename);
    $size = getimagesize($filename, $iptc);

    if(!$iptc) return $exif;

    $exif['IPTC'] = parseIptcTags($iptc);
    return $exif;
}

/*

IPTC.APP13.1#090;%G
IPTC.APP13.2#005;BAK20100907_3124
IPTC.APP13.2#025;Brian Knapp
IPTC.APP13.2#055;20100907
IPTC.APP13.2#060;144209
IPTC.APP13.2#120;Mid-morning snack: chocolate chips

*/

function parseIptcTags($iptcInfo)
{
    $iptcHeaders = getConfig("_IPTC.headers");
    $tags = array();
    foreach ($iptcInfo as $key => $value)
    { 
        if(!$value) continue;
        $iptc = iptcparse($value);
//debug("IPTC $key", $iptc);        
        if(!$iptc || !is_array($iptc)) continue;
        foreach ($iptc as $key2 => $arr)
        {
            $tk = arrayGet($iptcHeaders, $key2, $key2);       
//debug("IPTC $key.$tk", $arr);
            if($tk) $tags[$tk] = arraySingleToScalar($arr);
        }
    }

    return $tags;
}


function getIptcDate($exif)
{
    debug("getIptcDate", arrayGet($exif, "IPTC.CreationDate"));
    if(!$date = arrayGet($exif, "IPTC.CreationDate")) 
        return "";

    $date = strInsert($date, "-", 4);
    $date = strInsert($date, "-", 7);

  //20100907 232516 => 2010-09-07 23:25:16-0700
    if($time = arrayGet($exif, "IPTC.CreationTime"))
    {
      $time = substringBefore($time, "-");
      $time = strInsert($time, ":", 4);
      $time = strInsert($time, ":", 2); 

      $date .= " $time";
    }
    return $date;
}

/*
According to http://en.wikipedia.org/wiki/Geotagging,
( [0] => 46/1 [1] => 5403/100 [2] => 0/1 ) should mean 46/1 degrees, 5403/100 minutes, 0/1 seconds, i.e. 46°54.03′0″N.
Normalizing the seconds gives 46°54'1.8"N.
*/

//Pass in GPS.GPSLatitude or GPS.GPSLongitude or something in that format
function getGps($exifCoord)
{
  $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
  $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
  $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

  //normalize
  $minutes += 60 * ($degrees - floor($degrees));
  $degrees = floor($degrees);

  $seconds += 60 * ($minutes - floor($minutes));
  $minutes = floor($minutes);

  //extra normalization, probably not necessary unless you get weird data
  if($seconds >= 60)
  {
    $minutes += floor($seconds/60.0);
    $seconds -= 60*floor($seconds/60.0);
  }

  if($minutes >= 60)
  {
    $degrees += floor($minutes/60.0);
    $minutes -= 60*floor($minutes/60.0);
  }

  return array('degrees' => $degrees, 'minutes' => $minutes, 'seconds' => $seconds);
}

function gps2Num($coordPart)
{
    $parts = explode('/', $coordPart);

    if(count($parts) <= 0)// jic
        return 0;
    if(count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}
?>