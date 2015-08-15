rem %1 DVD drive Letter  / %2 title # / %3 output

set letter=D
IF NOT (%1) == () set letter=%1

set title=1
IF NOT (%2) == () set title=%2

ffmpeg -i "concat:%letter%:\VIDEO_TS\VTS_01_1.VOB|%letter%:\VIDEO_TS\VTS_01_2.VOB" -an -c copy %3