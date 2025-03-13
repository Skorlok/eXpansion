@echo off

REM read configuration file
FOR /F "tokens=2 delims==" %%a IN ('find "phpPath" ^<run.ini') DO SET phpPath=%%a

IF _%phpPath%==_ SET phpPath="php.exe"

ECHO You are updating your eXpansion installation !!.
set /p continue=Are you sure you wish to continue [y/n]?:

if %continue% == y (goto :update) else (goto :eof)

REM The update procedure with 2 options.
:update
set /p dev=Do you wish to install for development [y/n]?:
if %dev% == y (goto :dev) else (goto :stable)

REM In case of dev we launh with prefer-source options to have git access
:dev
%phpPath% composer.phar update --prefer-source --no-interaction %*
(goto :end)

REM Other case just get the code no need for heavier git.
:stable
%phpPath% composer.phar update --prefer-dist --no-interaction %*
(goto :end)

:end
pause