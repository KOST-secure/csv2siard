<?
// Report all PHP errors
error_reporting(E_ALL);

// -----------------------------------------------------------------------------
// read a ODCB table and write a SIARD table
function odbc2SIARDTable(&$table) {
global $prg_option, $prgdir;

	$tablename = $table['_a']['name'];

	// check for CSV file and open it for reading
	//$csvfile = $prg_option['CSV_FOLDER'].'/'.preg_replace('/([^\*]*)\*([^\*]*)/i', '${1}'.$tablename.'${2}', $prg_option['FILE_MASK']);
	$reg = '#^'.Wildcard2Regex($prg_option['FILE_MASK']).'$#i';
	if ( $dirhandle = opendir($prg_option['CSV_FOLDER'])) {
		while (false !== ($file = readdir($dirhandle))) {
			if (preg_match($reg, $file) > 0 and ($file != "." && $file != "..") ) {
				$name = preg_replace($reg, '${1}${2}${3}${4}${5}',$file);
				if ($name == $tablename) {
					$csvfile = $prg_option['CSV_FOLDER'].'/'.$file;
				}
			}
		}
		closedir($dirhandle);
	}
	if(!isset($csvfile) or !is_file($csvfile)) {
		echo "ODBC specification file $tablename not found\n"; $prg_option['ERR'] = 2; return;
	}
	setTableOption($table, 'localfile', "123".xml_encode($csvfile));
	
	// open ODCB table
	echo "Process table $tablename ";
	$odbc_handle = @odbc_connect($prg_option['ODBC_DSN'], $prg_option['ODBC_USER'], $prg_option['ODBC_PASSWORD']);
	if (!$odbc_handle) {
		echo "Could not open ODBC connection '$prg_option[ODBC_DSN]' for user '$prg_option[ODBC_USER]'\n";
		if ($prg_option['VERBOSITY']) { echo odbc_errormsg()."\n"; }
		$prg_option['ERR'] = 2;
		return;
	}
	// execute a dummy odbc query to get typ of ODCB connection out of error message
	@odbc_exec($odbc_handle, 'SELECT * from ODCB');
	// set type and connection info
	$prg_option['DB_TYPE'] = trim(preg_replace('/(\[.+\])(\[.+\]).+/','${2}', odbc_errormsg($odbc_handle)), '[]');
	$prg_option['CONNECTION'] = 'odbc:'.$prg_option['ODBC_DSN'].' - query form file://'.xml_encode(utf8_encode($prg_option['CSV_FOLDER']));

	// execute sql command to select table content
	$sql = trim(preg_replace('/\s[\s]+/',' ',strtr((file_get_contents($csvfile)),"\x0A\x0D" , "  ")), '; ');
	$recordset = @odbc_exec($odbc_handle, $sql);
	if (!$recordset) {
		echo "Error in SQL command '$sql'\n";
		if ($prg_option['VERBOSITY']) { echo odbc_errormsg()."\n"; }
		$prg_option['ERR'] = 2;
		odbc_close($odbc_handle);
		return;
	}
	
	// open SIARD table XML file for writing
	$tablefolder = getTableOption($table, 'folder');
	$siardfile = "$prg_option[SIARD_DIR]/content/$prg_option[SIARD_SCHEMA]/$tablefolder/$tablefolder.xml";
	$siard_handle = fopen($siardfile, "w");
	if(!$siard_handle) {
		echo "Could not write SIARD table XML file $siardfile\n"; $prg_option['ERR'] = 8; odbc_close($odbc_handle); return;
	}
	// write SIARD file XML header
	writeSIARDHeader($siard_handle, $tablefolder);
	
	// read and process CSV file
	reset($table);
	$rowcount = 1;
	$columcount = (array_key_exists('_a', $table['_c']['column'])) ? 1 : count($table['_c']['column']);
	
	while (odbc_fetch_into($recordset, $buf)) {
		if(count($buf) != $columcount) {
			echo "\nIncorrect columne count in table $csvfile"; $prg_option['ERR'] = 4;
			break;
		}
		// write SIARD table
		writeSIARDColumn($siard_handle, $buf, $columcount, $rowcount, $table);
		
		if (fmod($rowcount, $prg_option['PI_COUNT']*10) == 0) { echo chr(46); }
		$rowcount++;
	}

	// write SIARD file XML footer
	writeSIARDFooter($siard_handle);
	
	// update table row counter
	setTableOption($table, 'rowcount', $rowcount-1);

	echo "\n";
	odbc_close($odbc_handle);
	fclose($siard_handle);
}

?>