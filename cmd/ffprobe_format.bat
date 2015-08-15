@echo off
ffprobe -print_format compact -show_format %1 
ffprobe -print_format compact -show_streams %1
