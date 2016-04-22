SET TMP_DIR=.\tmp

set framerate=
IF NOT (%1) == () set framerate=-r %3

ffmpeg -i %1 -an -qscale 1 -vf yadif %TMP_DIR%\%1.%%07d.jpg 
rem dir %TMP_DIR% /O-N /B > %TMP_DIR%\list.txt
call rename_reverse.bat %TMP_DIR% %1
ffmpeg %framerate% -i %TMP_DIR%\%1.R%%07d.jpg %2

IF (%4) == (delete) del %TMP_DIR%\%1.R*.jpg