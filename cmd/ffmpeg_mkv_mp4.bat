rem directory inputFile outputFilename
ffmpeg -i %1\\%2 -ab 128k -ac 2 -vcodec copy -movflags faststart %1\\%3.mp4
