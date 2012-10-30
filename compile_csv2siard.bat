@ECHO OFF
SETLOCAL

REM settings -------------------------------------------------------------------
SET UNIX_HOME=C:\Software\PCUnixUtils
SET PATH=%UNIX_HOME%;%PATH%

REM compile --------------------------------------------------------------------
DEL csv2siard.exe odbcheck.exe main.php
GREP -v "dl(" csv2siard.php > main.php
SLEEP 5
BAMCOMPILE.EXE csv2siard.bcp
DEL main.php

BAMCOMPILE.EXE odbcheck.bcp
SLEEP 5

REM check syntax ---------------------------------------------------------------
CALL csv2siard.exe
IF %ERRORLEVEL% GTR 1 (
	EXIT /B
)
CALL odbcheck.exe
IF %ERRORLEVEL% NEQ  100 (
	EXIT /B
)
REM test function --------------------------------------------------------------
@ECHO ON
ECHO.
@ECHO --------------------------------------------------------------------------
CALL odbcheck.exe odbcsql\anl.sql odbcsql\odbcsql.prefs
ECHO.@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe table2-model.xml csvdata test.siard
unzip -t test.siard
ECHO.
@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe :NO_DB_MODEL csvdata test.siard
ECHO.
@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe :NO_DB_MODEL :ODBC test.siard odbcsql\odbcsql.prefs
ECHO.
@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe datatype-model.xml datatype test.siard datatype\datatype.prefs
ECHO.
@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe gv-model-nf.xml odbcsql test.siard odbcsql\odbcsql.prefs
ECHO.
@ECHO --------------------------------------------------------------------------
@DEL /Q *.siard
CALL csv2siard.exe gv-model-v9.xml csvdata test.siard

@ECHO OFF
if %ERRORLEVEL% == 0 (
	REM CALL C:\Software\jre6\bin\javaw.exe -jar "C:\Software\siardsuite_1.20\bin\SiardEdit.jar"
	REM CALL "C:\Software\jre6\bin\javaw.exe" -jar "C:\Software\siardsuite_1.44\bin\SiardEdit.jar"
)
@ECHO --------------------------------------------------------------------------
java.exe -Xmx128m -jar siard-val.jar test.siard C:\TEMP
IF %ERRORLEVEL% NEQ  0 (
	notepad.exe  C:\TEMP\test.siard.validationlog.log
)
