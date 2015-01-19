rem directory inputFile outputFilename resizeHeight
set filter= -vf scale=-1:%4
IF (%4) == () set filter=
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 %filter% -b:v 800k -ab 128k -ac 2 -movflags faststart %tmpFile%
del %3.mp4
ren %tmpFile% %3.mp4
