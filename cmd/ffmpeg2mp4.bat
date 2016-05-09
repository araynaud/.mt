rem directory inputFile outputFilename resizeHeight  
rem add start end
setlocal enabledelayedexpansion
rem @echo off

set oext=mp4
if(%~x2) == (.mp4) set oext=m4v

set filter=
IF NOT (%4) == () set filter= -vf yadif,scale=-1:%4
set ss=
IF NOT (%5) == () set ss=-ss %5
set to=
IF NOT (%5) == () set to=-to %6

echo from %ss% to %to%

rem set ffmpeg_options=-c copy
set ffmpeg_options=%filter% -b:v 1200k -ab 128k -ac 2 -movflags faststart 
set ffmpeg_options=%ffmpeg_options% %ss% %to%

set tmpFile=%1\\.tmp_%3.%oext%
set outputFile=%3
IF NOT (%5) == () set outputFile=%outputFile%_%5
IF NOT (%6) == () set outputFile=%outputFile%_%6
set outputFile=%outputFile%.%oext%

ffmpeg -i %1\\%2 %ffmpeg_options% %tmpFile%
del %1\\%outputFile%
ren %tmpFile% %outputFile%
