IF NOT (%3) == () set ss=-ss %3
IF NOT (%4) == () set to=-to %4

ffmpeg -i %1 -acodec copy -vcodec copy %ss% %to% -movflags faststart -y .tmp_%1.mp4
ffmpeg -i .tmp_%1.mp4 -vf "setpts=(1/2)*PTS, scale=400:-1" -y %2.gif
del .tmp_%1.mp4