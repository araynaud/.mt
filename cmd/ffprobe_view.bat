@echo off
ffprobe -show_streams -show_format -of json %1 2> nul
timeout /t -1
