IF NOT (%3) == () set ss=-ss %3
IF NOT (%4) == () set to=-to %4

set letter=D
IF NOT (%1) == () set letter=%1

set title=1
IF NOT (%2) == () set title=%2

set input="concat:%letter%:\VIDEO_TS\VTS_0%title%_1.VOB|%letter%:\VIDEO_TS\VTS_0%title%_2.VOB" 
SET TMP_DIR=.\tmp
mkdir %TMP_DIR%
ffmpeg %ss% -i %input% -an %to% -vf yadif=0:0:0,scale=640:480 -y %TMP_DIR%\DVD.%%05d.jpg
