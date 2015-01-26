#!/bin/bash
#input, output, start time, length

if [ "$3" = "" ]; then
	ss=""
else
	ss="-to $3"
fi

if [ "$4" = "" ]; then
	length=""
else
	length="-t $4"
fi

echo from $ss length: $length
ffmpeg -i "$1" -acodec copy -vcodec copy $ss $length -movflags faststart -y "$2"
