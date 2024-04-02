@echo off
setlocal

call composer install --prefer-dist --no-progress --no-dev

mkdir moloni

echo Moving files
copy "index.php" "moloni\index.php" >nul
copy "moloni.php" "moloni\moloni.php" >nul
copy "hooks.php" "moloni\hooks.php" >nul
copy "LICENSE.md" "moloni\LICENSE.md" >nul

echo Moving Public...
xcopy "public" "moloni\public" /E /C /I /H /Y > nul

echo Moving Templates...
xcopy "templates" "moloni\templates" /E /C /I /H /Y > nul

echo Moving Source...
xcopy "src" "moloni\src" /E /C /I /H /Y > nul

echo Moving vendor...
xcopy "vendor" "moloni\vendor" /E /C /I /H /Y > nul

echo Ziping files...
tar -a -c -f "moloni.zip" "moloni"

echo Cleaning up trash...
del /f /s /q moloni > nul
rmdir moloni /s /q

echo Process is done
start ./moloni.zip
pause
endlocal
