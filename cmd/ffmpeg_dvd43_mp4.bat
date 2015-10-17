rem %1 DVD drive Letter  / %2 title # / %3 output

set letter=D:
IF NOT (%1) == () set letter=%1

set title=1
IF NOT (%2) == () set title=%2

set input="%letter%\VIDEO_TS\VTS_0%title%_1.VOB" 
ffmpeg -i %input% -b:v 1000k -vf yadif=0:0:0,scale=640:480 -aspect 4:3 -movflags faststart %3