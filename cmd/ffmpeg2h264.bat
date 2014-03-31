%ffmpeg% -i %1\\%2 -vf scale=-1:540 -b 1000k -ab 128k -ac 2 %1\\%3_tmp.mp4
%qtfs% %1\\%3_tmp.mp4 %1\\%3.mp4
del %1\\%3_tmp.mp4
