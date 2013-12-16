<?php 
//Get EXIF data for an image
function getExifDateTaken($filename, $exif=NULL)
{
  if(!$exif)
      $exif=getExifData($filename);
  if(!$exif)  return "";

  $date = arrayGetCoalesce($exif, "DateTimeOriginal", "DateTime", "EXIF.DateTimeOriginal", "IFD0.DateTime");
  debug("getExifDateTaken", $date);
  if(!$date)
    return "";

  //replace :: in date
  $pos = strpos($date,':');
  $date[$pos]='-';
  if($pos>4) $date=deleteChars($date,4,$pos);

  $pos = strpos($date,':');
  $date[$pos]='-';
  if($pos>7) $date=deleteChars($date,7,$pos);

  debug($pos, $date);
  return $date;
}

function isExifLibAvailable()
{
  return is_callable("exif_imagetype");
}

function getExifData($filename, $arrays=false)
{
  if(!fileIsImage($filename) || !isExifLibAvailable())
    return array();
    
  global $config;
  $imagetype = @exif_imagetype($filename);  
  if(!in_array($imagetype, $config["EXIF_SUPPORTED_TYPES"]))
    return array();
  //Get EXIF data for an image
  $exif = @exif_read_data($filename, null, $arrays, false);

  //if no exif in the image, get it from csv file
  if(!arrayGetCoalesce($exif, "DateTimeOriginal", "DateTime", "EXIF.DateTimeOriginal", "IFD0.DateTime"))
    $exif=loadImageInfo($filename);

  unset($exif["COMPUTED"]);
  return $exif;
}

function getExifThumbnail($filename)
{
  debug("getExifThumbnail",$filename);
  if(!fileIsImage($filename) || !isExifLibAvailable())
    return;
    
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