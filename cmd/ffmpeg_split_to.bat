rem input, output, start time, end time
SETLOCAL
IF NOT (%3) == () set ss=-ss %3
IF NOT (%4) == () set to=-to %4
echo from %ss% to %to%
ffmpeg -i %1 -acodec copy -vcodec copy %ss% %to% -y %2
