IF NOT (%2) == () set ss=-ss %2
IF NOT (%3) == () set to=-to %3

SET TMP_DIR=.\tmp
mkdir %TMP_DIR%
ffmpeg -i %1 -an %ss% %to% -y %TMP_DIR%\%1.%%05d.jpg
