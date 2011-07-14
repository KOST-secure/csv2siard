<?
// Report all PHP errors
error_reporting(E_ALL);
// -----------------------------------------------------------------------------
// read XML database model into multi-dimensional array
function loadDatabaseModell(&$dbmod) {
global $prg_option;

	$dbmod = xml2ary(file_get_contents($prg_option['DB_SCHEMA']));
	return;
}
// -----------------------------------------------------------------------------
// create SIARD file header and content in TMP directory
function creatSIARDFolder(&$dbmod) {
global $prg_option, $prgdir, $siard_schema, $siard2html;
$defaultschema = $prg_option['SIARD_SCHEMA'];
$folderstructur ="
    ├───header
    │       metadata.xsd
    │       metadata.xsl
    │       metadata.xml
    └───content
        └───schema0
            ├───table0
            │       table0.xsd
            │       table0.xml
            └───table1
                    table1.xsd
                    table1.xml
";
	// Create temporary SIARD folder
	$prg_option['SIARD_DIR'] = $prg_option['TMPDIR'].'/'.basename($prg_option['SIARD_FILE']);
	rrmdir("$prg_option[SIARD_DIR]");

	// Create SIARD header
	mkdirPHP4("$prg_option[SIARD_DIR]/header", 0777, true);
	// for convenience digestType: "(|(MD5|SHA-1).*)" => "(MD5.+|SHA-1.+)*"
	copy ("$prgdir/$siard_schema", "$prg_option[SIARD_DIR]/header/metadata.xsd");
	copy ("$prgdir/$siard2html", "$prg_option[SIARD_DIR]/header/metadata.xsl");

	// Create SIARD content and folder
	mkdirPHP4("$prg_option[SIARD_DIR]/content/$defaultschema", 0777, true);
	
	$dbt = &$dbmod['database']['_c']['table'];
	reset($dbt);
	$tbc = 0;
	while (list($dbno, $tables) = each($dbt)) {
		mkdirPHP4("$prg_option[SIARD_DIR]/content/$defaultschema/table$tbc", 0777, true);
		setTableOption($dbt[$dbno],"folder", "table$tbc");
		$tbc++;
	}
	return;
}
// -----------------------------------------------------------------------------
// read a CSV file and write a SIARD table
function creatSIARDTable(&$table) {
global $prg_option;

	$tablename = $table['_a']['name'];
	echo "Process table $tablename .";

	// check for CSV file and open it for reading
	$csvfile = $prg_option['CSV_FOLDER'].'/'.preg_replace('/([^\*]*)\*([^\*]*)/i', '${1}'.$tablename.'${2}', $prg_option['FILE_MASK']);
	setTableOption($table, 'file', $csvfile);
	if(!is_file($csvfile)) {
		echo "CSV file $csvfile not found\n"; $prg_option['ERR'] = true; return;
	}
	$csvhandle = fopen($csvfile, "r");
	if(!$csvhandle) {
		echo "Could not read CSV file $csvfile\n"; $prg_option['ERR'] = true; return;
	}
	// open SIARD file for writing
	$tablefolder = getTableOption($table, 'folder');
	$siardfile = "$prg_option[SIARD_DIR]/content/$prg_option[SIARD_SCHEMA]/$tablefolder/$tablefolder.xml";
	$siardhandle = fopen($siardfile, "w");
	if(!$siardhandle) {
		echo "Could not open SIARD xml file $siardfile\n"; $prg_option['ERR'] = true; return;
	}
	// write SIARD file XML header
	writeSIARDHeader($siardhandle, $tablefolder);
	
	// read and process CSV file
	$rowcount = 1;
	$columcount = count($table['_c']['column']);
	while (($buf = fgetcsv($csvhandle, 100000, $prg_option['DELIMITED'], $prg_option['QUOTE'])) !== false) {
		if(count($buf) < $columcount) {
			echo "Incorrect CSV on line $rowcount in file $csvfile\n"; $prg_option['ERR'] = true;
		}
		$b = array_chunk($buf, $columcount); $buffer = $b[0];
		// first row contains column names
		if ($rowcount == 1 and $prg_option['COLUMN_NAMES']) {
			processCSVColumnNames($buffer, $csvfile, $tablename, $table);
		}
		else {
			writeSIARDColumn($siardhandle, $buffer, $columcount);
		}
		if (fmod($rowcount, $prg_option['PI_COUNT']) == 0) { echo '.'; }
		$rowcount++;
	}

	// write SIARD file XML footer
	writeSIARDFooter($siardhandle);
	
	// update table row counter
	$rowcount = ($prg_option['COLUMN_NAMES']) ? $rowcount-2 : $rowcount-1;
	setTableOption($table, 'rowcount', $rowcount);

	echo "\n";
	fclose($csvhandle);
	fclose($siardhandle);
}
// -----------------------------------------------------------------------------
// write a SIARD Schema file
function creatSIARDSchema(&$table) {
global $prg_option;

	// open SIARD file for writing
	$tablefolder = getTableOption($table, 'folder');
	$siardschema = "$prg_option[SIARD_DIR]/content/$prg_option[SIARD_SCHEMA]/$tablefolder/$tablefolder.xsd";
	$siardhandle = fopen($siardschema, "w");
	if(!$siardhandle) {
		echo "Could not open SIARD schema file $siardfile\n"; $prg_option['ERR'] = true; return;
	}
	
	// write SIARD schema header
	writeSchemaHeader($siardhandle, $tablefolder);

	// write SIARD schema content
	writeSchemaContent($siardhandle, $table);

	// write SIARD schema footer
	writeSchemaFooter($siardhandle);

	fclose($siardhandle);
}

// -----------------------------------------------------------------------------
// validate a SIARD XML file with xmllint
function validateSIARDTable(&$table) {
global $prgdir, $prg_option;

	$tablefolder = getTableOption($table, 'folder');
	$siardfile = "$prg_option[SIARD_DIR]/content/$prg_option[SIARD_SCHEMA]/$tablefolder/$tablefolder.xml";
	$siardschema = "$prg_option[SIARD_DIR]/content/$prg_option[SIARD_SCHEMA]/$tablefolder/$tablefolder.xsd";
	
	exec("$prgdir/xmllint.exe -noout -schema $siardschema $siardfile 2>$tablefolder.out", $result, $retval);
	if ($retval) {
		$result = file_get_contents("$tablefolder.out");
		$result_array = explode("\n", $result, 2);
		echo "$tablefolder.xml is not a valid XML file:\n$result_array[0]\n";
	}
	unlink("$tablefolder.out");
}
// -----------------------------------------------------------------------------
// write  SIARD XML metadata file
function createSIARDMetadata(&$dbmod) {
global $prgdir, $prg_option;

	$siardmetadata = "$prg_option[SIARD_DIR]/header/metadata.xml";
	$siardschema = "$prg_option[SIARD_DIR]/header/metadata.xsd";
	$xmldump = "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n" . ary2xml($dbmod);
	file_put_contents($siardmetadata, $xmldump);
	return;
	
	exec("$prgdir/xmllint.exe -noout -schema $siardschema $siardheader 2>metadata.out", $result, $retval);
	if ($retval) {
		$result = file_get_contents("metadata.out");
		$result_array = explode("\n", $result, 2);
		echo "metadata.xml is not a valid XML file:\n$result_array[0]\n";
	}
	unlink("metadata.out");
}
?>
