#!/bin/bash
# directory inputFile outputFilename resizeHeight
echo params: $1 $2 $3 $4
if [ "$4" = "" ]; then
	echo "no resize"
	filter=""
else
	filter="-vf scale=-1:$4"
	echo "resize Height: $4"
fi

ffmpeg -i "$1/$2" $filter -b:v 800k -ab 128k -ac 2 -strict -2 -movflags faststart -y "$1/$3.m4v"
