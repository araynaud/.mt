rem directory inputFile outputFilename resizeHeight
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -ab 128k -ac 2 -vcodec copy %tmpFile%
qt-faststart %tmpFile% %1\\%3.mp4
del %tmpFile%
