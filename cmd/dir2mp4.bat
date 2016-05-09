set inputdir=.
IF NOT (%1) == () set inputdir=%1
FORFILES /P %inputdir% -m *.mp4 /C "cmd /c call ffmpeg2mp4 . @file @fname"

for %%G in (m2t mpg mpeg mts avi mov ogv wmv vob) do FORFILES /P %inputdir% -m *.%%G /C "cmd /c call ffmpeg2mp4 . @file @fname 720"
