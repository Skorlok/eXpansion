@echo off

REM read configuration file
FOR /F "tokens=2 delims==" %%a IN ('find "phpPath" ^<..\..\..\run.ini') DO SET phpPath=%%a

IF _%phpPath%==_ SET phpPath="C:\server\php5.6.40\php.exe"

%phpPath% pyplanetimport.php

pause