for %%G in (.mp4, .mts, .mov) do FORFILES /P %2 -m *%%G /C "cmd /c call %1 . @file @fname"
