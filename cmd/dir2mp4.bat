FORFILES /P %1 /M *.mov /C "cmd /c call ffmpeg2mp4 . @file @fname"
FORFILES /P %1 /M *.mts /C "cmd /c call ffmpeg2mp4 . @file @fname"
FORFILES /P %1 /M *.m2t /C "cmd /c call ffmpeg2mp4 . @file @fname"
FORFILES /P %1 /M *.mpg /C "cmd /c call ffmpeg2mp4 . @file @fname"
FORFILES /P %1 /M *.avi /C "cmd /c call ffmpeg2mp4 . @file @fname"
rem FORFILES /P %1\hd /M *.mp4 /C "cmd /c call ffmpeg2mp4 %1 @file @fname"

