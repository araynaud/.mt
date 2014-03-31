ffmpeg -i %1 -b:v 800k -r 20 .%2_tmp.mp4
qt-faststart .%2_tmp.mp4 %2.mp4
del .%2_tmp.mp4
