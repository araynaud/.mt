setlocal enabledelayedexpansion
@echo off
set argC=0
for %%x in (%*) do Set /A argC+=1
echo argc: %argC%
del list.txt

set output=%1
:loop1
	shift
	if "%1"=="" goto after_loop
	echo file %1 >> list.txt
	goto loop1
:after_loop
echo output: %output%

set ffmpeg_options=-c copy
set ffmpeg_options=-vf scale=-1:540 -b:v 800k -ab 128k -ac 2 -movflags faststart 
ffmpeg -f concat -i list.txt %ffmpeg_options% %output%

rem del list.txt
