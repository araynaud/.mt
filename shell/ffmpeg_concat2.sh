#!/bin/bash
# directory inputFile outputFilename input files 

echo args: $@
echo argc: $#
output=$1
shift
rm list.txt
while test ${#} -gt 0
do
	echo file "$1" >> list.txt
	shift
done

echo output: $output
echo ffmpeg -f concat -i list.txt -movflags faststart $output

ffmpeg -f concat -i list.txt -c copy -movflags faststart $output

rm list.txt
