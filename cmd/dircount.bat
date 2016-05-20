@echo off
setlocal enableextensions
set count=0
cd %1
for %%x in (*) do set /a count+=1
echo %count%
endlocal