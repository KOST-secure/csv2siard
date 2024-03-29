@ECHO OFF
SETLOCAL
REM SET TMP=E:\bamcompile
SET TEMP=%TMP%

REM settings -------------------------------------------------------------------
SET UNIX_HOME=C:\Software\PCUnixUtils
SET PATH=%UNIX_HOME%;%PATH%

REM compile --------------------------------------------------------------------
rm.exe -f csv2siard.exe odbcheck.exe

rm.exe -f %TMP%\* 2> null
rm.exe -f out.php main.php null
GREP -v "dl(" csv2siard.php | GREP -v "include " > out.php
cat c2sconfig.php c2screate.php c2sconvert.php c2sfunction.php c2sxml.php c2snodbmodel.php c2schema.php c2stimedate.php zip.php c2odbc.php c2snodbodbc.php out.php >main.php
BAMCOMPILE.EXE -d -e:php_xslt.dll main.php csv2siard.exe

rm.exe -f %TMP%\* 2> null
rm.exe -f out.php main.php null
GREP -v "dl(" odbcheck.php | GREP -v "include " > out.php
cat c2sconfig.php c2sfunction.php c2sxml.php c2odbc.php out.php >main.php
BAMCOMPILE.EXE -d -e:php_xslt.dll main.php odbcheck.exe

rm.exe -f %TMP%\* 2> null
rm.exe -f out.php main.php null
GREP -v "dl(" csvschema.php | GREP -v "include " > out.php
cat c2sconfig.php c2sfunction.php c2sxml.php c2snodbmodel.php c2schema.php c2stimedate.php c2odbc.php c2snodbodbc.php out.php >main.php
BAMCOMPILE.EXE -d -e:php_xslt.dll main.php csvschema.exe

rm.exe -f out.php main.php null

REM check syntax ---------------------------------------------------------------
@ECHO ON
CALL csv2siard.exe
@IF %ERRORLEVEL% GTR 1 (
	@EXIT /B
)
CALL odbcheck.exe
@IF %ERRORLEVEL% GTR 1 (
	@EXIT /B
)
CALL csvschema.exe
@IF %ERRORLEVEL% GTR 1 (
	@EXIT /B
)
REM test function --------------------------------------------------------------
@ECHO.
CALL odbcheck.exe odbcsql\anl.sql odbcsql\odbcsql.prefs
@IF %ERRORLEVEL% NEQ 0 (
	PAUSE
	@EXIT /B
)
@ECHO.

@ECHO --------------------------------------------------------------------------
@ECHO.
CALL odbcheck.exe "SELECT * FROM gv_list.csv;" odbcsql\odbcsql.prefs | tail -n 21 | sort | wc
CALL odbcheck.exe "SELECT * FROM gv_list.csv;" odbcsql\odbcsql.prefs | tail -n 21 | sort -u | wc
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe table2-model.xml csvdata test.siard
@IF %ERRORLEVEL% NEQ 0 (
	PAUSE
	@EXIT /B
)
unzip -t test.siard
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard log.txt
CALL csv2siard.exe gv-model-v9.xml csvdata test.siard :LOG_FILE=log.txt
pr.exe -n -l 1 log.txt
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe :NO_DB_MODEL csvdata test.siard
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe :NO_DB_MODEL :ODBC test.siard odbcsql\odbcsql.prefs
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe datatype-model.xml datatype test.siard datatype\datatype.prefs
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe gv-model-nf.xml odbcsql test.siard odbcsql\odbcsql.prefs
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
CALL csv2siard.exe datatype\utf8_model.xml :ODBC test.siard datatype\datatype_utf8.prefs
@ECHO.

@ECHO --------------------------------------------------------------------------
@rm.exe -f /Q *.siard
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
