@ECHO OFF

set argC=0
for %%x in (%*) do Set /A argC+=1
echo argC: %argC%

set output=%1
SHIFT

set ii=1
set /a last=2 * %argC% - 3
set jj=%last%
:Loop
	IF "%1"=="" GOTO Continue
	echo Here your batch file handles %1 %ii% %jj%
	copy .ss\%1 .ss\.tmpgif_%ii%.jpg
	copy .ss\%1 .ss\.tmpgif_%jj%.jpg
	set /a ii=%ii% + 1	
	set /a jj=%jj% - 1	
	SHIFT
GOTO Loop
:Continue

del .ss\.tmpgif_%last%.jpg
ffmpeg -f image2 -framerate 8 -i .ss\.tmpgif_%%d.jpg -vf scale=-1:300 -y %output%.gif

del .ss\.tmpgif_*.jpg