@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../oliverde8/asynchronous-jobs/bin/AsynchronousJobsRun.php
php "%BIN_TARGET%" %*
