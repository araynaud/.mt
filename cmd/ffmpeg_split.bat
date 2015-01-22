rem input, output, start time, length
SETLOCAL
IF NOT (%3) == () set ss=-ss %3
IF NOT (%4) == () set length=-t %4
echo from %ss% length %length%
ffmpeg -i %1 -acodec copy -vcodec copy %ss% %length% -y %2
