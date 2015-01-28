#!/bin/bash
# directory inputFile outputFilename resizeHeight
echo params: $@

oext=mp4
if [ ${2: -4} = ".mp4" ]; then
	oext=m4v
fi
echo output extension: $oext

if [ "$4" != "" ]; then
	filter="-vf scale=-1:$4"
fi

outputFile=$3
if [ "$5" != "" ]; then
	ss="-ss $5"
	outputFile="${outputFile}_$5"
fi
if [ "$6" != "" ]; then
	to="-to $6"
	outputFile="${outputFile}_$6"
fi
outputFile="$outputFile.$oext"

#ffmpeg_options=-c copy
ffmpeg_options="$ss $to $filter -b:v 800k -ab 128k -ac 2 -strict -2 -movflags faststart"

ffmpeg -i "$1/$2" $ffmpeg_options -y "$1/$outputFile"

echo ffmpeg -i "$1/$2" $ffmpeg_options -y "$1/$outputFile"
echo output: $outputFile