@echo off
ffprobe -print_format compact -show_frames %1 | findstr "pict_type=I"