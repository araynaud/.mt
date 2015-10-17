rem directory inputFile outputFilename
set tmpFile=%1\\.tmp_%3.mp4
ffmpeg -i %1\\%2 -acodec libvo_aacenc -vcodec copy -movflags faststart %1\\%3.mp4
