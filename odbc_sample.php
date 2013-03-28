<?

//SQL_CUR_USE_IF_NEEDED,SQL_CUR_USE_ODBC ,SQL_CUR_USE_DRIVER SQL_CUR_DEFAULT 
//$conn = odbc_connect("Driver={Microsoft Access Text Driver (*.txt, *.csv)};Dbq=./odbcdata", '', '', SQL_CUR_USE_IF_NEEDED );
$conn = odbc_connect("Driver={Microsoft Access Text Driver (*.txt, *.csv)};Dbq=./odbcdata", '', '', SQL_CUR_USE_ODBC );
//$conn = odbc_connect("Driver={Microsoft Access Text Driver (*.txt, *.csv)};Dbq=./odbcdata", '', '', SQL_CUR_USE_DRIVER );
//$conn = odbc_connect("Driver={Microsoft Access Text Driver (*.txt, *.csv)};Dbq=./odbcdata", '', '', SQL_CUR_DEFAULT );

if (!$conn) {
        exit("Connection Failed: " . $conn);
}

//$sql = "SELECT  TOP 20 id, police_nr, baujahr FROM gv_gebaeude.csv ORDER BY id";
//$sql = "SELECT * FROM gv_gebaeude.csv WHERE gemeinde = 'Aadorf *' ORDER BY id";
$sql = "SELECT * FROM gv_list.csv";
$rs = odbc_exec($conn, $sql);
if (!$rs) {
        exit("Error in SQL");
}

echo "id; police_nr; baujahr;\n";

while (odbc_fetch_row($rs)) {
        $id=odbc_result($rs,"id");
        $police_nr=odbc_result($rs,"police_nr");
        $baujahr=odbc_result($rs,"baujahr");
        echo "$id; $police_nr; $baujahr\n";
}
odbc_close($conn);

?>