rem directory inputFile outputFilename
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -vf scale=-1:540 -b 800k -ab 128k -ac 2 %tmpFile%
qt-faststart %tmpFile% %1\\%3.mp4
del %tmpFile%