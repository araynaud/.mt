set program_path=C:\Program Files\ffmpeg\bin
set ffmpeg="%program_path%\ffmpeg.exe"
set mp4box="%program_path%\mp4box.exe"
set qtfs="%program_path%\qt-faststart.exe"

FORFILES /P %1 /M *.mov /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"
FORFILES /P %1 /M *.mts /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"
FORFILES /P %1 /M *.m2t /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"
FORFILES /P %1 /M *.mpg /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"
FORFILES /P %1 /M *.avi /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"
rem FORFILES /P %1\hd /M *.mp4 /C "cmd /c call ffmpeg2h264.bat %1 @file @fname"

