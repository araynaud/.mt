rem directory inputFile outputFilename resizeHeight
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -b 800k -r 20 %1\\%tmpFile%
qt-faststart %tmpFile% %1\\%3.mp4
del %tmpFile%
