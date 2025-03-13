@echo off
@chcp 65001
REM read configuration file
FOR /F "tokens=2 delims==" %%a IN ('find "phpPath" ^<run.ini') DO SET phpPath=%%a

IF _%phpPath%==_ SET phpPath="php.exe"

%phpPath% setup.php

if %errorlevel% NEQ 0 pause