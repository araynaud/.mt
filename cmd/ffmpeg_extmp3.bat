ffmpeg -i %1 %1_tmp.mp3
ffmpeg -i %1_tmp.mp3 -acodec copy %1.mp3
del %1_tmp.mp3