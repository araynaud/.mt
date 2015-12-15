rem directory inputFile outputFilename resizeHeight
ffmpeg -i %1\\%2 -acodec copy -vcodec copy -movflags faststart %1\\%3.mp4