ffmpeg -i %1\%2 -vf scale=-1:%4 -b 800k -ab 128k -ac 2 %1\.%3_tmp.mp4
qt-faststart %1\.%3_tmp.mp4 %1\%3.mp4
del %1\.%3_tmp.mp4
