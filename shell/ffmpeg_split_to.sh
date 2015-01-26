#!/bin/bash
#input, output, start time, end time

if [ "$3" = "" ]; then
	ss=""
else
	ss="-ss $3"
fi

if [ "$4" = "" ]; then
	to=""
else
	to="-to $4"
fi

echo from $ss to $to
ffmpeg -i "$1" -acodec copy -vcodec copy $ss $to -movflags faststart -y "$2"
