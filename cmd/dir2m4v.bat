for %%G in (mp4 mts mov) do FORFILES /P %1 -m *.%%G /C "cmd /c call ffmpeg_mp4_m4v . @file @fname 540"
