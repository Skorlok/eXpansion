@echo off

REM read configuration file
FOR /F "tokens=2 delims==" %%a IN ('find "phpPath" ^<run.ini') DO SET phpPath=%%a

IF _%phpPath%==_ SET phpPath="php.exe"

ECHO You are updating your eXpansion installation !!.
set /p continue=Are you sure you wish to continue [y/n]?:

if %continue% == y (goto :update) else (goto :eof)

:update
%phpPath% composer.phar update --no-interaction %*
(goto :end)

:end
pause
