set inputdir=.
IF NOT (%1) == () set inputdir=%1
FORFILES /P %inputdir% -m *.mp4 /C "cmd /c call extm4a @file"
