set inputdir=.
IF NOT (%1) == () set inputdir=%1
for %%G in (mp4 m2t mpg mpeg mts avi mov ogv wmv) do FORFILES /P %inputdir% -m *.%%G /C "cmd /c call ffmpeg2mp4 . @file @fname"
