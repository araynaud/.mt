for %%G in (.mp4, .mts, .mov) do FORFILES /P %1 -m *%%G /C "cmd /c call ffmpeg2h264_m4v.bat . @file @fname"

rem FORFILES /P %1 /M *.mov /C "cmd /c call ffmpeg2h264_m4v.bat . @file @fname"
rem FORFILES /P %1 /M *.mp4 /C "cmd /c call ffmpeg2h264_m4v.bat . @file @fname"