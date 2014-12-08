rem rename_reverse dir filename
SET TMP_DIR=%1
SET FILENAME=%2
rem count files
dir /b *.jpg | find /c "."
for /f %%f in ('dir /b %TMP_DIR%\\%FILENAME%.*.jpg ^| find /c "."') do set NB=%%f
echo %NB% files.

SET NB=0
SET J=0
echo file loop
FOR /F %%F IN ('dir %TMP_DIR%\%FILENAME%.*.jpg /O-N /B') DO call :RenameRev %%F
goto :eof

:RenameRev
SET /a J=%J%+1
set cnt=0000000%J%
set cnt=%cnt:~-7%
ren %TMP_DIR%\%1 %FILENAME%.R%cnt%.jpg
goto :eof

:End