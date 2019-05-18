<?php
//
// versie: 1.6.3 aangepast door André Rijkeboer
// auteur: Jos van der Zande based on model from André Rijkeboer
//
// datum:  18-05-2018 
// omschrijving: ophalen van de P1 en SolarEdge gegeven om ze samen in 1 grafiek te laten zien
//
//
// Extra tabel definitie voor de solaredge database:
// 		USE solaredge;
//			CREATE TABLE P1_Meter (
//			timestamp  INT      UNSIGNED NOT NULL,
//			mv1	FLOAT  COMMENT 'Meterstand Verbruik Laag tarief',
//			mv2 FLOAT  COMMENT 'Meterstand Verbruik Hoog tarief',
//			mr1 FLOAT  COMMENT 'Meterstand Teruglevering Laag tarief',
//			mr2 FLOAT  COMMENT 'Meterstand Teruglevering Hoog tarief',
//			dv	FLOAT  COMMENT 'Dag Verbruik',
//			dr  FLOAT  COMMENT 'Dag Teruglevering',
//			cv  FLOAT  COMMENT 'Current Power Verbruik',
//			cr  FLOAT  COMMENT 'Current Power Teruglevering',
//			PRIMARY KEY (timestamp),
//			INDEX       (timestamp)
//			);
//
// Als P1_Meter_Overzicht niet aanwezig is wordt deze gemaakt met de volgende definities
//			CREATE TABLE P1_Meter_Overzicht (
//			datum date NOT NULL COMMENT 'Datum',
//			prod float DEFAULT NULL COMMENT 'Solar productie',
//			v1 float DEFAULT NULL COMMENT 'Verbruik laag tarief',
//		  	v2 float DEFAULT NULL COMMENT 'Verbruik hoog tarief',
//		  	r1 float DEFAULT NULL COMMENT 'Retour laag tarief',
//		  	r2 float DEFAULT NULL COMMENT 'Retour hoog tarief',
//		  	PRIMARY KEY (datum),
//		  	KEY (datum)
//			);
//
//~ URL tbv live data p1 Meter: live-server-data-electra-domoticz.php/period=c
//~ ==========================================================================
//~ De verwachte JSON output is voor "period=c"  (current data)   aantal= wordt niet gebruikt
//~ [{
//~  "ServerTime" : "2019-03-13 11:48:40",
//~  "CounterDelivToday" : "1.349 kWh",
//~  "CounterToday" : "3.893 kWh",
//~  "Usage" : "167 Watt",
//~  "UsageDeliv" : "0 Watt",
//~ }]
//~ URL tbv dag grafiek p1 Meter: live-server-data-electra-domoticz.php/period=d&aantal=60
//~ ======================================================================================
//~ De verwachte JSON output is voor "period=d&aantal=xx"
//~ [
//~ {"idate":"2019-02-10","serie":"2019-02-10","prod":0,"v1":10.94,"v2":0,"r1":0,"r2":0},
//~ {"idate":"2019-02-11","serie":"2019-02-11","prod":0,"v1":3.68,"v2":9.92,"r1":0,"r2":0},
//~ {"idate":"2019-02-12","serie":"2019-02-12","prod":0,"v1":3.45,"v2":8.49,"r1":0,"r2":0}
//~ ]
//~ URL tbv maand grafiek p1 Meter: live-server-data-electra-domoticz.php/period=m&aantal=13
//~ ========================================================================================
//~ De verwachte JSON output is voor "period=m&aantal=xx"
//~ [
//~ {"idate":"2019-01-01","serie":"2019-01","prod":0,"v1":186.47,"v2":181.2,"r1":0,"r2":0},
//~ {"idate":"2019-02-01","serie":"2019-02","prod":137.72,"v1":163.95,"v2":154.13,"r1":36.64,"r2":71.46},
//~ {"idate":"2019-03-01","serie":"2019-03","prod":128.23,"v1":63.63,"v2":34.71,"r1":15.8,"r2":72.11}
//~ ]

$limit = $_GET['aantal'];
$period = $_GET['period'];
$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";
if($limit == ''){ $limit = '30';}
$SQLdatefilter = '"%Y-%m-%d"';
$SQLdatefilter1 = 'Y-m-d';
if( $period == '') { $period = 'c';}
if( $period == '' || $period == 'd' ) {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
	$limitf =  $limit * 86400;
} elseif ($period == 'm') {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m")';
	$SQLdatefilter1 = 'Y-m';
	$limitf = 31*$limit*86400;
} else {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
	$limitf =  $limit * 86400;
}
$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";
if($d1 == ''){$d1 = date("d-m-Y H:i:s", time());}

$time = strtotime(gmdate("d-m-Y 12:00:00",(new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp()));
$d3 = date("Y-m-d", strtotime($d1));
$d3a = date("d", strtotime($d1));
$d3b = date("Y-m", strtotime($d1));
if ($d3a == '01' and $period == 'm'){
	$limit -=1;
}
$winter = 2 - date("I",$today);
$d2 = time();
$date = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$yesterday1 = gmdate("Y-m-d",(new DateTime(sprintf("yesterday %s",date("Y-m-d 12:00:00", time()))))->getTimestamp());
$morgen = date("Y-m-d",(new DateTime(sprintf("tomorrow %s",date("Y-m-d 12:00:00", $time))))->getTimestamp());
$today = gmdate("Y-m-d H:i:s",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", $time))))->getTimestamp());
$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$yesterday = (new DateTime(sprintf("yesterday %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$total = array();
$diff = array();
include('config.php');
$inverterstr = 'telemetry_inverter';
if ($inverter == 3){
	$inverterstr = 'telemetry_inverter_3phase';
}

//open MySQL database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);

// controle of P1_Meter_overzicht bestaat
$val = $mysqli->query('select 1 from `P1_Meter_Overzicht` LIMIT 1');

if($val == FALSE)
{
	// creëer tabel P1_Meter_Overzicht 
	$sql = "CREATE TABLE `P1_Meter_Overzicht` (
		  `datum` date NOT NULL COMMENT 'Datum',
		  `prod` float DEFAULT NULL COMMENT 'Solar productie',
		  `v1` float DEFAULT NULL COMMENT 'Verbruik laag tarief',
		  `v2` float DEFAULT NULL COMMENT 'Verbruik hoog tarief',
		  `r1` float DEFAULT NULL COMMENT 'Retour laag tarief',
		  `r2` float DEFAULT NULL COMMENT 'Retour hoog tarief',
		  PRIMARY KEY (datum),
		  KEY (datum)
		)";

	if ($mysqli->query($sql) !== TRUE) {
		echo "Error creating table: " . $mysqli->error;
	}

}
// controleer of er iets in P1_Meter_Overzicht staat
$val = $mysqli->query('select UNIX_TIMESTAMP(datum) datum from `P1_Meter_Overzicht` order by datum desc LIMIT 1');
$row = mysqli_fetch_assoc($val);
if ($row){
	// zet de laaste gegevens in P1_Meter_Overzicht
	$van = $row[datum];
	$sql = 'INSERT INTO `P1_Meter_Overzicht`(`datum`, `prod`, `v1`, `v2`, `r1`, `r2`) SELECT * '.
	'FROM ( SELECT DATE(t2.d) as datum, sum(IFNULL(t1.tzon,0)) as prod, sum(t2.sv1) as v1, sum(t2.sv2) as v2, '.
	'sum(t2.sr1) as r1, sum(t2.sr2) as r2 FROM (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, '.
	'max(mv1)-min(mv1) as sv1, max(mv2)-min(mv2) as sv2, max(mr1)-min(mr1) as sr1, max(mr2)-min(mr2) as sr2 '.
	'FROM P1_Meter where timestamp >= '.$van.' and timestamp < '.$tomorrow.' GROUP BY d ) t2 left join (SELECT timestamp, '.
	'DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, (max(e_total)-min(e_total))/1000 as tzon '.
	'FROM ' . $inverterstr . ' where timestamp >= '.$van.' and timestamp < '.$tomorrow.' GROUP BY d ) t1 ON t1.d = t2.d  GROUP BY datum '.
	') output ORDER by Datum '.
	'ON DUPLICATE KEY UPDATE '.
	'prod = output.prod, v1 = output.v1, v2 = output.v2, r1 = output.r1, r2 = output.r2'; 
	if ($mysqli->query($sql) !== TRUE) {
		echo "Error update table: " . $mysqli->error;
	}
}else{
	// vul P1_Meter_Overzicht met de gegevens
	$sql = 'INSERT INTO `P1_Meter_Overzicht`(`datum`, `prod`, `v1`, `v2`, `r1`, `r2`) SELECT * '.
	'FROM ( SELECT DATE(t2.d) as datum, sum(IFNULL(t1.tzon,0)) as prod, sum(t2.sv1) as v1, sum(t2.sv2) as v2, '.
	'sum(t2.sr1) as r1, sum(t2.sr2) as r2 FROM (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, '.
	'max(mv1)-min(mv1) as sv1, max(mv2)-min(mv2) as sv2, max(mr1)-min(mr1) as sr1, max(mr2)-min(mr2) as sr2 '.
	'FROM P1_Meter where timestamp < '.$tomorrow.' GROUP BY d ) t2 left join (SELECT timestamp, '.
	'DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, (max(e_total)-min(e_total))/1000 as tzon '.
	'FROM ' . $inverterstr . ' where timestamp < '.$tomorrow.' GROUP BY d ) t1 ON t1.d = t2.d  GROUP BY datum '.
	') output ORDER by Datum'; 
	if ($mysqli->query($sql) !== TRUE) {
		echo "Error insert table: " . $mysqli->error;
	}
}
// Get current info for P1_ElectriciteitsMeter from solaredge database
if ($period == 'c' ){
	// ***************************************************************************************************************
	// Haal huidig energy verbruik/retour op van de P1_ElectriciteitsMeter .... ????
	// ***************************************************************************************************************
	$result = $mysqli->query("SELECT
	FROM_UNIXTIME(timestamp) as time,
	dv,
	dr,
	cv,
	cr
	From P1_Meter
	where timestamp >= " . $yesterday. " and timestamp < " . $tomorrow . "
	order by timestamp desc limit 1");
	$row = mysqli_fetch_assoc($result);	
	if ($row){
		$diff['ServerTime'] = $row['time'];
		$diff['CounterToday'] = $row['dv'];
		$diff['CounterDelivToday'] = $row['dr'];
		$diff['Usage'] = $row['cv'];
		$diff['UsageDeliv'] = $row['cr'];
	} else {
		$diff['ServerTime'] = date("Y-m-d H:i:s",$time);
		$diff['CounterToday'] = 0;
		$diff['CounterDelivToday'] = 0;
		$diff['Usage'] = 0;
		$diff['UsageDeliv'] = 0;
	}		
	array_push($total, $diff);
} else {
	//open MySQL database
	$mysqli = new mysqli($host, $user, $passwd, $db, $port);
	// haal gegevens van de panelen op
	$diff = array();
	$p1revrow = ["se_day" => 0];
	// haal de gegevens op
	foreach($mysqli->query('SELECT * FROM ( ' .
							'SELECT '.$datefilter.' as oDate, DATE(t2.d) as iDate, sum(t2.tzon) as prod, sum(t2.sv1) as v1, sum(t2.sv2) as v2, sum(t2.sr1) as r1, sum(t2.sr2) as r2 ' .
							'	 FROM      (SELECT DATE_FORMAT(datum, "%Y-%m-%d") as d, sum(v1) as sv1, sum(v2) as sv2, sum(r1) as sr1, sum(r2) as sr2, sum(prod) as tzon ' .
							'			   FROM   P1_Meter_Overzicht ' .
							'              where datum < "'.$morgen.'"'.
							'			   GROUP BY d ' .
							'			   ) t2 ' .
							' GROUP BY oDate ' .
							' ORDER by t2.d desc ' .
							' LIMIT '.$limit.' ) output' .
							' ORDER by oDate ;'   ) as $j => $row){
		$diff['idate'] = date($row['iDate']);
		$diff['serie'] = date($row['oDate']);
		$diff['prod'] = round($row["prod"],2);
		$diff['v1'] = round($row["v1"],2);
		$diff['v2'] = round($row["v2"],2);
		$diff['r1'] = round($row["r1"],2);
		$diff['r2'] = round($row["r2"],2);
	
		//voeg het resultaat toe aan de total-array
		array_push($total, $diff);
	}
	if (!$total){
		$diff['idate'] = date("Y-m-d",$time);
		$diff['serie'] = date($SQLdatefilter1,$time);
		$diff['prod'] = 0;
		$diff['v1'] = 0;
		$diff['v2'] = 0;
		$diff['r1'] = 0;
		$diff['r2'] = 0;
	
		//voeg het resultaat toe aan de total-array
		array_push($total, $diff);
	}
		
}
 
// Sluit DB
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();

// Output totale resultaat als JSON
echo json_encode($total);
?>
