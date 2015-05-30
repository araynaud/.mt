set inputdir=.
IF NOT (%4) == () set inputdir=%1

for %%G in (m2t mpg mpeg mts avi mov ogv wmv) do FORFILES /P %inputdir% -m *.%%G /C "cmd /c call ffmpeg2mp4 . @file @fname"
