set video=%1

set audio= -an 
set output=%2
IF NOT (%3) == () set audio=-i %2
IF NOT (%3) == () set output=%3

set offset=
IF NOT (%4) == () set offset= -itsoffset %4


echo video: %video%  audio: %audio% offset: %offset%
echo %output%

ffmpeg %offset% -i %video% %audio% -map 0:v -map 1:a -c:v copy -c:a aac -shortest -movflags faststart  %output%
rem -strict experimental -shortest -movflags faststart %output%