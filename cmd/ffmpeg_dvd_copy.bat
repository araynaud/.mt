rem %1 DVD drive Letter  / %2 title # / %3 output

set letter=D
IF NOT (%1) == () set letter=%1

set title=1
IF NOT (%2) == () set title=%2

set src="concat:%letter%:\VIDEO_TS\VTS_0%title%_1.VOB|%letter%:\VIDEO_TS\VTS_0%title%_2.VOB|%letter%:\VIDEO_TS\VTS_0%title%_3.VOB|%letter%:\VIDEO_TS\VTS_0%title%_4.VOB|%letter%:\VIDEO_TS\VTS_0%title%_5.VOB"
ffmpeg -i %src% -c copy %3.mpg

call ffmpeg_dvd43_mp4.bat %src% %3.mp4