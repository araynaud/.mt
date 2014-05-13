rem directory inputFile outputFilename factor
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -an  -vf setpts=(1/%4)*PTS %tmpFile%
qt-faststart %tmpFile% %1\\%3x%4.mp4
del %tmpFile%
