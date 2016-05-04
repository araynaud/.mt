SETLOCAL

IF NOT (%2) == () set ss=-ss %2

set output=%3
IF (%3) == () set output=%1_%2.jpg

IF NOT (%4) == () set vf=-vf scale=-1:%4

echo output: %output%

ffmpeg -i %1 -an %ss% -t 1 -r 1 -y %vf% %output%