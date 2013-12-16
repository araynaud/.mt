ffmpeg -i %1 -b:v 800k -vf scale=%3:-1 -ab 128k -ac 2 %2_tmp.mp4
qt-faststart %2_tmp.mp4 %2.mp4
del %2_tmp.mp4
