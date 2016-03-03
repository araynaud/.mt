@echo off
rem ffmpeg -i %1 -vf select='gt(scene\,0.4)',scale=160:90,tile -frames:v 1 %1.png
ffprobe -show_frames -of json -f lavfi "movie=%1,select=gt(scene\,0.4)" 
rem > %1.scenes.json