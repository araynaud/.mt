set inputdir=.
IF NOT (%1) == () set inputdir=%1
for %%G in (mkv m2t mpg mpeg mts mov ogv vob) do FORFILES /P %inputdir% -m *.%%G /C "cmd /c call ffmpeg_hd_mp4 . @file @fname"
