rem directory inputFile outputFilename resizeHeight
ffmpeg -i %1\\%2 -ab 128k -ac 2 -vol 1024 %1\\%3.mp3
