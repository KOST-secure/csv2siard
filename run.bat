@ECHO OFF
DEL /Q *.siard
echo --------- %time%
php.exe csv2siard.php gv-model-v9.xml csvdata test.siard csvdata\gvtg.prefs
DEL /Q *.siard
php.exe csv2siard.php sqldata\gv-model-v9.xml sqldata test.siard sqldata\gvtg.prefs
REM php.exe csv2siard.php no_db_model csvtest test.siard csvtest\csvtest.prefs
REM php.exe csv2siard.php datatype-model.xml csvtest test.siard csvtest\csvtest.prefs
REM php.exe csv2siard.php gv-model-v9.xml csvdata test.siard csvdata\gvtg.prefs

REM csv2siard.exe gv-model-v9.xml P:\KOST\Pilotloesungen\Gebaeudeversicherung\4_GV-Viewer\csvdata P:\KOST\Pilotloesungen\Gebaeudeversicherung\4_GV-Viewer\test.siard
REM csv2siard.exe gv-model-v9.xml P:\KOST\Pilotloesungen\Gebaeudeversicherung\9_Testdaten\csvdata_TG(vertraulich) P:\KOST\Pilotloesungen\Gebaeudeversicherung\4_GV-Viewer\gvtg(vertraulich).siard
REM csv2siard.exe NO_DB_MODEL P:\KOST\Pilotloesungen\Gebaeudeversicherung\9_Testdaten\csvdata_TG(vertraulich) gvtg(vertraulich).siard
REM csv2siard.exe gv-model-v9.xml P:\KOST\Pilotloesungen\Gebaeudeversicherung\9_Testdaten\csvdata_TG(vertraulich) gvtg(vertraulich).siard
REM csv2siard.exe gv-model-v9.xml csvdata test.siard
REM php.exe csv2siard.php datatype-model.xml csvtest test.siard csvtest/csvtest.prefs
REM php.exe csv2siard.php NO_DB_MODEL csvtest test.siard csvtest/csvtest.prefs

REM php.exe csv2siard.php NO_DB_MODEL csvtest test.siard
REM php.exe csv2siard.php datatype-model.xml csvtest test.siard
REM php.exe csv2siard.php gv-model-v9.xml csvdata test.siard
REM php.exe csv2siard.php NO_DB_MODEL csvdata test.siard
REM csv2siard.exe gv-model-v9.xml ../9_Testdaten/csvdata_TG test.siard
REM csv2siard.exe NO_DB_MODEL ../9_Testdaten/csvdata_TG test.siard

@ECHO OFF
if %ERRORLEVEL% == 0 (
	REM SIARD v.1.20 kann in MS Access importieren (Fehler in v.1.26)
	REM CALL "C:\Software\jre6\bin\javaw.exe" -jar "C:\Software\siardsuite_1.20\bin\SiardEdit.jar"
)
exit /b
