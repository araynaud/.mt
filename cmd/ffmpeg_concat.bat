setlocal enabledelayedexpansion
@echo off
set argC=0
for %%x in (%*) do Set /A argC+=1
echo argc: %argC%

set output=%1
:loop1
	shift
	if "%1"=="" goto after_loop
	echo one:%1
	set input=%input% %1
	goto loop1
:after_loop

for %%a in (%input%) do (
	ffmpeg -i %%a -c copy -bsf:v h264_mp4toannexb -f mpegts %%a.ts
	SET filenames=!filenames!^|%%a.ts
)
ffmpeg -i "concat:%filenames:~1%" -c copy -bsf:a aac_adtstoasc -movflags faststart %output%
echo input: "%filenames:~1%"
echo output: %output%
del *.ts
