rem directory inputFile outputFilename resizeHeight text
set tmpFile=%1\\.tmp_%3.m4v
set font=/dev/MediaThingy/fonts/impact.ttf
set text=La pate a choux is easy!
ffmpeg -i %1\\%2 -vf scale=-1:%4 -b:v 800k -ab 128k -ac 2 -movflags faststart -vf drawtext="fontfile=%font%:text='%text%':fontsize=20:fontcolor=white:x=10:y=10" %3.m4v
