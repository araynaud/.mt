for %%G in (m2t mpg mpeg mts avi mov ogv wmv) do FORFILES /P %1 -m *.%%G /C "cmd /c call ffmpeg2mp4 . @file @fname"
