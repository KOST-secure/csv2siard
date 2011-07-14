<?php 
error_reporting(E_ALL);
// create database model from scratch
function createDBModel(){
global $prg_option, $wdir, $prgdir, $torque_schema, $static_torque_schema;
$order_of_datatype = array ('INTEGER' => 0, 'DECIMAL' => 1, 'FLOAT' => 2, 'DATE' => 3, 'VARCHAR' => 4);

// Create CSV file list
	$file_arr = array();
	$reg = '#'.Wildcard2Regex($prg_option['FILE_MASK']).'#i';
	if ( $dirhandle = opendir($prg_option['CSV_FOLDER'])) {
		while (false !== ($file = readdir($dirhandle))) {
			if (preg_match($reg, $file) > 0 and ($file != "." && $file != "..") ) {
				$name = preg_replace($reg, '${1}${2}${3}${4}${5}',$file);
				if ($name != '') {
					$csvfile = $prg_option['CSV_FOLDER'].'/'.$file;
					// detect mime type with GNU file-5.03
					$commandline = 'CALL "'.$prgdir.'/file.exe" --mime-type -bm "'.$prgdir.'/magic.mgc" '.'"'.$csvfile.'"';
					$mime_type = exec($commandline);
					if ($mime_type == 'text/plain') {
						$file_arr[$name] = $csvfile;
					}
					else {
						echo "Incorrect CSV file: ($mime_type) $csvfile\n";
					}
				}
			}
		}
	}
	closedir($dirhandle);
	asort($file_arr);
	if (count($file_arr) == 0) {
		echo "No CSV files found with file mask '$prg_option[FILE_MASK]' in $prg_option[CSV_FOLDER]\n"; exit(2);
	}
	
	// Create column list for each file
	$csv_arr = array();
	reset($file_arr);
	// Read each CSV file
	while (list($name, $file) = each($file_arr)) {
		$csvhandle = fopen($file, "r");
		if(!$csvhandle) {
			echo "Could not read CSV file $file\n"; exit(2);
		}
		$rowcount = 0;
		$colarr = array();
		while (($buf = fgetcsv($csvhandle, $prg_option['MAX_ROWSIZE'], $prg_option['DELIMITED'], $prg_option['QUOTE'])) !== false) {
			if (fmod($rowcount, $prg_option['PI_COUNT']) == 0) { echo '.'; }
			// Read first line to detect columns
			if ($rowcount == 0) {
				$colcnt = 0;
				foreach ($buf as $b) {
					if ($b != '') {
						$type['name'] = ($prg_option['COLUMN_NAMES']) ? $b : "column$colcnt";
						$type['type'] = 'INTEGER'; // preset with INTEGER
						$type['size'] = 0;
						$colarr[] = $type;
						$colcnt++;
					}
				}
			} 
			// Read entire file to detect column type
			if (!($prg_option['COLUMN_NAMES'] and $rowcount == 0)) {
				$colcnt = 0;
				foreach ($buf as $b) {
					if ($b != '') {
						if (strlen($b) > $colarr[$colcnt]['size']) {
							$colarr[$colcnt]['size'] = strlen($b);
						}
						$bt = guessDataType($b);
						if ($order_of_datatype[$bt] > $order_of_datatype[$colarr[$colcnt]['type']]) {
							$colarr[$colcnt]['type'] = $bt;
						}
						$colcnt++;
					}
				}
			}
			$rowcount++;
		}
		$csv_arr[$name] = $colarr;
		fclose($csvhandle);
	}

	// create database description according to torque.v4 XML model
	$dbname = basename($prg_option['CSV_FOLDER']);
	
	$xmldata = "<?xml version=\"1.0\" encoding=\"utf-8\"?".">\n";
	$xmldata = $xmldata . "<database name=\"$dbname\" xmlns=\"http://db.apache.org/torque/4.0/templates/database\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://db.apache.org/torque/4.0/templates/database database-torque-4-0.xsd\">\n";
	reset($csv_arr);
	while (list($name, $columns) = each($csv_arr)) {
		$xmldata = $xmldata . "\t<table name=\"$name\">\n";
		reset($columns);
		while (list($name, $attributes) = each($columns)) {
			if ($attributes['type'] == 'VARCHAR') {
				$size = ($attributes['size'] == '') ? '' : " size=\"$attributes[size]\"";
				$xmldata = $xmldata . "\t\t<column name=\"$attributes[name]\" type=\"$attributes[type]\"$size/>\n";
			} else {
				$xmldata = $xmldata . "\t\t<column name=\"$attributes[name]\" type=\"$attributes[type]\"/>\n";			
			}
		}
		$xmldata = $xmldata . "\t</table>\n";
	}
	$xmldata = $xmldata . "</database>\n";

	// write database description no_db_model.xml
	$dbmodel = "$wdir/no_db_model.xml";
	if (!file_put_contents("$dbmodel", utf8_encode($xmldata))) {
		echo "Could not write database description $dbmodel\n"; $prg_option['ERR'] = 8; return;
	}

	$prg_option['DB_MODEL'] = "$dbmodel";
	
	// validate database description no_db_model.xml according to torque v4.0
	file_put_contents("$prg_option[TMPDIR]/$torque_schema", $static_torque_schema);
	if (!validateXML("$prg_option[TMPDIR]/$torque_schema", $dbmodel, "'$dbmodel' is not a valid database schema according to Torque v4.0")) {
		exit(16);
	}
	unlink("$prg_option[TMPDIR]/$torque_schema");

	// write console message
	echo "\nNew XML database model written: $wdir/no_db_model.xml\n";
	reset($file_arr);
	while (list($key, $val) = each($file_arr)) {
		$val = ansi2ascii(utf8_decode($val));
		echo "  [$key] => $val\n";
	}
}

// -----------------------------------------------------------------------------
// guess data type of string $buf, returns data type
function guessDataType($buf) {
	// == empty string
	if ($buf == '' or $buf == ' ') {
		return('VARCHAR');
	}
	// == INTEGER
	if (ctype_digit($buf)) {
		return('INTEGER');
	}
	// == DECIMAL
	$b = strtr ($buf, ',', '.');
	if (is_numeric ($buf)) {
		if (stristr($buf, 'E') or stristr($buf, '-')) {
			return('FLOAT');
		} 
		else {
			return('DECIMAL');
		
		}
	}
	// == DATE
	$bd = convert2XMLdate($buf);
	if ($bd and $bd['type'] != 'UNIX native date format' and $bd['date'] != '') {
		return('DATE');
	}
	// == anything else is VARCHAR
	return('VARCHAR');
}
?>
