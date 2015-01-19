for %%G in (mp4 mov) do FORFILES /P %1 -m *.%%G /C "cmd /c call ffmpeg_mp4_m4v_audio_copy . @file @fname"
