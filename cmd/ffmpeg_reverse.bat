SET TMP_DIR=.\tmp
ffmpeg -i %1 -an -qscale 1 -vf yadif %TMP_DIR%\%1.%%07d.jpg 
rem dir %TMP_DIR% /O-N /B > %TMP_DIR%\list.txt
call rename_reverse.bat %TMP_DIR% %1
ffmpeg -i %TMP_DIR%\%1.R%%07d.jpg -r 30 %2
rem del %TMP_DIR%\%1.R*.jpg