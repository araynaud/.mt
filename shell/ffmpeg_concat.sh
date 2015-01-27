#!/bin/bash
# directory inputFile outputFilename input files 

echo args: $@
echo argc: $#
output=$1

input=""
shift
sep=""
while test ${#} -gt 0
do
	echo $1
	input="$input \"$1\""

	ffmpeg -i "$1" -c copy -bsf:v h264_mp4toannexb -f mpegts "$1.ts"
	filenames=$filenames$sep"$1.ts"
	sep="|"
	shift
done

echo output: $output
echo input: $input
echo filenames: $filenames

ffmpeg -i "concat:$filenames" -c copy -bsf:a aac_adtstoasc -movflags faststart $output
echo input: "$filenames"
rm *.ts
