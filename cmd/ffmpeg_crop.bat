SETLOCAL
set crop=%3:%4
IF NOT (%5) == () set crop=%crop%:%5
IF NOT (%6) == () set crop=%crop%:%6
echo crop %crop%
ffmpeg -i %1 -vf crop=%crop% -acodec copy -movflags faststart %2.mp4