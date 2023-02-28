ffmpeg -i %1 -acodec copy %1.aac
call ffmpeg_split %1.aac %1.m4a
del %1.aac