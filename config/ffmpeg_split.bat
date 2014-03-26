rem input, output, start time, length
ffmpeg -i %1 -acodec copy -vcodec copy -ss %3 -t %4 %2