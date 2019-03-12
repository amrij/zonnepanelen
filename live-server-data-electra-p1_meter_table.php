<?php
//
// versie: 1.1
// auteur: Jos van der Zande  based on model from André Rijkeboer
//
// datum:  12-03-2018
// omschrijving: ophalen van de P1 en SolarEdge gegeven om ze samen in 1 grafiek te laten zien
// Extra tabel definitie voor de solaredge database:
// 		USE solaredge;
// 		CREATE TABLE P1_Meter (
// 			timestamp   INT      UNSIGNED NOT NULL,
// 			v1          FLOAT  COMMENT 'Verbruik Laag tarief',
// 			v2          FLOAT  COMMENT 'Verbruik Hoog tarief',
// 			r1          FLOAT  COMMENT 'Teruglevering Laag tarief',
// 			r2          FLOAT  COMMENT 'Teruglevering Hoog tarief',
// 			PRIMARY KEY (timestamp),
// 			INDEX       (timestamp)
// 		);
//
// De verwachte JSON output is voor "period=c"  (current data)   aantal isn't used
//[{
// "CounterDelivToday" : "1.349 kWh",
// "CounterToday" : "3.893 kWh",
// "Usage" : "167 Watt",
// "UsageDeliv" : "0 Watt",
//}]

// De verwachte JSON output is voor "period=d"
// [
// {"idate":"2019-02-10","serie":"2019-02-10","prod":0,"v1":10.94,"v2":0,"r1":0,"r2":0},
// {"idate":"2019-02-11","serie":"2019-02-11","prod":0,"v1":3.68,"v2":9.92,"r1":0,"r2":0},
// {"idate":"2019-02-12","serie":"2019-02-12","prod":0,"v1":3.45,"v2":8.49,"r1":0,"r2":0}
// ]
// De verwachte JSON output is voor "period=m"
// [
// {"idate":"2019-01-01","serie":"2019-01","prod":0,"v1":186.47,"v2":181.2,"r1":0,"r2":0},
// {"idate":"2019-02-01","serie":"2019-02","prod":137.72,"v1":163.95,"v2":154.13,"r1":36.64,"r2":71.46},
// {"idate":"2019-03-01","serie":"2019-03","prod":128.23,"v1":63.63,"v2":34.71,"r1":15.8,"r2":72.11}
// ]

$limit = $_GET['aantal'];
if($limit == ''){
	$limit = '30';
}
$period = $_GET['period'];

if( $period == '' || $period == 'd' ) {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
} elseif ($period == 'm') {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m")';
} else {
	$datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
}

//~ $d1 = $_GET['date'];
$d1 = '';
if($d1 == ''){
	$d1 = date("d-m-Y H:i:s", time());
}
$d3 = date("Y-m-d", strtotime($d1));
$d2 = time();
$date = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$total = array();
$diff = array();
include('config.php');

// Get current info for P1_ElectriciteitsMeter from domoticz
if ($period == 'c' ){
	//Get current info for P1_ElectriciteitsMeter from domoticz
	$response = file_get_contents('http://'.$domohost.'&/json.htm?type=devices&rid='.$domoidx);
	$domo_rest = json_decode($response,true);
	$diff['ServerTime'] = $domo_rest["ServerTime"];
	$diff['CounterDelivToday'] = $domo_rest["result"][0]['CounterDelivToday'];
	$diff['CounterToday'] = $domo_rest["result"][0]['CounterToday'];
	$diff['Usage'] = $domo_rest["result"][0]['Usage'];
	$diff['UsageDeliv'] = $domo_rest["result"][0]['UsageDeliv'];
	array_push($total, $diff);
} else {

	//open MySQL database
	$mysqli = new mysqli($host, $user, $passwd, $db, $port);
	// haal gegevens van de panelen op
	$diff = array();
	$date_i = $date-365*86400;
	$p1revrow = ["se_day" => 0];
	if ($inverter == 1){
		// haal de gegevens van de enkel fase inverter op
		foreach($mysqli->query('SELECT * FROM ( ' .
								'SELECT '.$datefilter.' as oDate, DATE(t2.d) as iDate, sum(IFNULL(t1.tzon,0)) as prod, sum(t2.sv1) as v1, sum(t2.sv2) as v2, sum(t2.sr1) as r1, sum(t2.sr2) as r2 ' .
								'	 FROM      (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, sum(v1) as sv1, sum(v2) as sv2, sum(r1) as sr1, sum(r2) as sr2 ' .
								'			   FROM   P1_Meter ' .
								'			   GROUP BY d ' .
								'			   ) t2 ' .
								'	 left join (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, sum(de_day)/1000 as tzon ' .
								'			   FROM   solaredge.telemetry_inverter ' .
								'			   GROUP BY d  ' .
								'			   ) t1 ' .
								' ON t1.d = t2.d  ' .
								' GROUP BY oDate ' .
								' ORDER by t2.d desc ' .
								' LIMIT '.$limit.') output' .
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
	}else{
		// haal de gegevens van de 3 fase inverter op
	}
	// Sluit DB
	$thread_id = $mysqli->thread_id;
	$mysqli->kill($thread_id);
	$mysqli->close();
}
//Output totale resultaat als JSON
echo json_encode($total);
?>