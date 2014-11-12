ffmpeg -i 5-0-40.mp4 -i 6-40-82.mp4 -filter_complex concat=n=2:v=1:a=1 56.mp4

ffmpeg -i 20140504163400.m4v -i 20140504184428.mp4 -i 20140504180307.mp4 -i 20140504181534.mp4 -filter_complex concat=n=4:v=1:a=1 concat.mp4

ffmpeg -i concat:"20140504163400.m4v|20140504184428.mp4|20140504180307.mp4|20140504181534.mp4" -codec copy concat2.mp4
