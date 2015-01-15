FORFILES /P %1 /M *.mov /C "cmd /c call ffmpeg_hd_mp4_nosound %1 @file @fname"
FORFILES /P %1 /M *.mts /C "cmd /c call ffmpeg_hd_mp4_nosound %1 @file @fname"
FORFILES /P %1 /M *.m2t /C "cmd /c call ffmpeg_hd_mp4_nosound %1 @file @fname"
FORFILES /P %1 /M *.mpg /C "cmd /c call ffmpeg_hd_mp4_nosound %1 @file @fname"

