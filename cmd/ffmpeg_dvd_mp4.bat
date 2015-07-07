rem ffmpeg -i concat:"/media/dvd/VIDEO_TS/VTS_01_1.VOB|/media/dvd/VIDEO_TS/VTS_01_2.VOB" -acodec libfaac -aq 100 -ac 2 -vcodec libx264 -vpre slow -crf 24 -threads 0 output.mp4

rem ffmpeg -i %1 -aq 100 -ac 2 -vcodec libx264 -crf 24 -threads 0 %2
ffmpeg -i %1 -an -b:v 800k -vf yadif -movflags faststart -ss %3 %2