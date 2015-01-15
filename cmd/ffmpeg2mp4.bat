rem directory inputFile outputFilename resizeHeight
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -vf scale=-1:%4 -b:v 800k -ab 128k -ac 2 -movflags faststart %tmpFile%
ren %tmpFile% %3.mp4
