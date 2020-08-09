<?php
//
// versie: 1.80.1
// auteur: André Rijkeboer
//
// datum:  9-08-2020
// omschrijving: ophalen van de P1meter informatie uit P1-monitor en SolarEdge gegeven om ze samen in 1 grafiek te laten zien
//
//~ URL tbv live data p1 Meter: live-server-data-electra-P1-monitor.php?period=c
//~ ==========================================================================
//~ De verwachte JSON output is voor "period=c"  (current data)   aantal= wordt niet gebruikt
//~ [{
//~  "ServerTime" : "2019-03-13 11:48:40",
//~  "CounterDelivToday" : "1.349 kWh",
//~  "CounterToday" : "3.893 kWh",
//~  "Usage" : "167 Watt",
//~  "UsageDeliv" : "0 Watt",
//~ }]

//~ URL tbv dag grafiek p1 Meter: live-server-data-electra-P1-monitor.php?period=d&aantal=60
//~ ======================================================================================
//~ De verwachte JSON output is voor "period=d&aantal=xx"
//~ [
//~ {"idate":"2019-02-10","serie":"2019-02-10","prod":0,"v1":10.94,"v2":0,"r1":0,"r2":0},
//~ {"idate":"2019-02-11","serie":"2019-02-11","prod":0,"v1":3.68,"v2":9.92,"r1":0,"r2":0},
//~ {"idate":"2019-02-12","serie":"2019-02-12","prod":0,"v1":3.45,"v2":8.49,"r1":0,"r2":0}
//~ ]

//~ URL tbv maand grafiek p1 Meter: live-server-data-electra-P1-monitor.php?period=m&aantal=13
//~ ========================================================================================
//~ De verwachte JSON output is voor "period=m&aantal=xx"
//~ [
//~ {"idate":"2019-01-01","serie":"2019-01","prod":0,"v1":186.47,"v2":181.2,"r1":0,"r2":0},
//~ {"idate":"2019-02-01","serie":"2019-02","prod":137.72,"v1":163.95,"v2":154.13,"r1":36.64,"r2":71.46},
//~ {"idate":"2019-03-01","serie":"2019-03","prod":128.23,"v1":63.63,"v2":34.71,"r1":15.8,"r2":72.11}
//~ ]

include('config.php');

$limit = array_key_exists('aantal', $_GET) ? $_GET['aantal'] : "";
$period = array_key_exists('period', $_GET) ? $_GET['period'] : "";
$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";

if($d1 == '') { $d1 = date("d-m-Y H:i:s", time()); }
if($limit == '') { $limit = '30'; }
$vandaag = date("Y-m-d H:i:s", strtotime($d1));

$d1 = $vandaag;
$date = (new DateTime("today " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();


if ($period == 'm') {
	$SQLdatefilter = '"%Y-%m"';
	$JSON_SUM = "Y-m";
	$JSON_period = "month";
} else {
	$SQLdatefilter = '"%Y-%m-%d"';
	$JSON_SUM = "Y-m-d";
	$JSON_period = "day";
}
$tomorrow = (new DateTime("tomorrow " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
$vandaag = date("Y-m-d 00:00:00", strtotime($d1));
$p1data = array();
$p1rec = array();
$diff = array();
$total = array();

// Get current info for P1_ElectriciteitsMeter from P1-monitor
if ($period == 'c' ) {
	//Get start for the day info for P1_ElectriciteitsMeter from P1-monitor
	$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object&sort=asc&starttime='.str_replace(" ","%20",$vandaag));
	$total = array();
	$diff = array();
	$p1_rest = json_decode($response,true);
	$startP1 = $p1_rest[0]["CONSUMPTION_KWH_LOW"] + $p1_rest[0]["CONSUMPTION_KWH_HIGH"];
	$startP1deliv = $p1_rest[0]["PRODUCTION_KWH_LOW"] + $p1_rest[0]["PRODUCTION_KWH_HIGH"];
	//Get current info for P1_ElectriciteitsMeter from P1-monitor
	$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object');
	$p1_rest = json_decode($response,true);
	$diff['ServerTime'] = $p1_rest[0]["TIMESTAMP_lOCAL"];
	$diff['CounterDelivToday'] = $p1_rest[0]["PRODUCTION_KWH_LOW"] + $p1_rest[0]["PRODUCTION_KWH_HIGH"] - $startP1deliv;
	$diff['CounterToday'] = $p1_rest[0]["CONSUMPTION_KWH_LOW"] + $p1_rest[0]["CONSUMPTION_KWH_HIGH"]- $startP1;
	$diff['Usage'] = $p1_rest[0]["CONSUMPTION_W"];
	$diff['UsageDeliv'] = $p1_rest[0]["PRODUCTION_W"];
	array_push($total, $diff);
} else {
	// ============================================================================================
	if ($period == 'd' ) {
		$found = 0;
		$dagh = ((new DateTime(date("Y-m-d 00:00:00", strtotime($d1))))->modify('-'.($limit-1).' day'))->format('Y-m-d H:i:s');
		$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object&sort=asc&starttime='.str_replace(" ","%20",$dagh));
		$td = date("Y-m-d", strtotime($dagh));
		if ($response){ 
			$p1_rest = json_decode($response,true);
			$tv1 = $p1_rest[0]["CONSUMPTION_KWH_LOW"];
			$tv2 = $p1_rest[0]["CONSUMPTION_KWH_HIGH"];
			$tr1 = $p1_rest[0]["PRODUCTION_KWH_LOW"];
			$tr2 = $p1_rest[0]["PRODUCTION_KWH_HIGH"];
			$found = 1;
		}
 		for ($dag = 1; $dag <=$limit; $dag++){
			$dagh = ((new DateTime(date("Y-m-d 00:00:00", strtotime($d1)+86400)))->modify('-'.($limit-$dag).' day'))->format('Y-m-d H:i:s');
			if (strtotime($dagh) > time()){
				$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object');
				$dagh = date("Y-m-d H:i:s", time());
			}else{
				$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object&sort=asc&starttime='.str_replace(" ","%20",$dagh));
			}
			if ($response){ 
				$p1_rest = json_decode($response,true);
				if (date("Y-m-d", strtotime($dagh)) == date("Y-m-d", strtotime($p1_rest[0]["TIMESTAMP_lOCAL"]))){
					if ($found == 1){
						$p1rec['d']= $td;
						$p1rec['v1']= $p1_rest[0]["CONSUMPTION_KWH_LOW"] - $tv1;
						$p1rec['v2']= $p1_rest[0]["CONSUMPTION_KWH_HIGH"] - $tv2;
						$p1rec['r1']= $p1_rest[0]["PRODUCTION_KWH_LOW"] - $tr1;
						$p1rec['r2']= $p1_rest[0]["PRODUCTION_KWH_HIGH"] - $tr2;
						array_push($p1data, $p1rec);
						}
					$found = 1;
					$tv1 = $p1_rest[0]["CONSUMPTION_KWH_LOW"];
					$tv2 = $p1_rest[0]["CONSUMPTION_KWH_HIGH"];
					$tr1 = $p1_rest[0]["PRODUCTION_KWH_LOW"];
					$tr2 = $p1_rest[0]["PRODUCTION_KWH_HIGH"];
				} 
			}
			$td = date("Y-m-d", strtotime($dagh));
 		}
	} else {
		$found = 0;
		$dagh = ((new DateTime(date("Y-m-1 00:00:00", strtotime($d1))))->modify('-'.($limit-1).' month'))->format('Y-m-d H:i:s');
		$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object&sort=asc&starttime='.str_replace(" ","%20",$dagh));
		$td = date("Y-m-d", strtotime($dagh));
		if ($response){ 
			$p1_rest = json_decode($response,true);
			$tv1 = $p1_rest[0]["CONSUMPTION_KWH_LOW"];
			$tv2 = $p1_rest[0]["CONSUMPTION_KWH_HIGH"];
			$tr1 = $p1_rest[0]["PRODUCTION_KWH_LOW"];
			$tr2 = $p1_rest[0]["PRODUCTION_KWH_HIGH"];
			$found = 1;
		}
		for ($maand = 1; $maand <= $limit; $maand++){
			$dagh = ((new DateTime(date("Y-m-1 00:00:00", strtotime($d1)+31*68400)))->modify('-'.($limit-$maand).' month'))->format('Y-m-d H:i:s');
			if (strtotime($dagh) > time()){
				$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object');
				$dagh = date("Y-m-d H:i:s", time());
			}else{
				$response = file_get_contents('http://'.$P1monitorhost.'/api/v1/smartmeter?limit=1&json=object&sort=asc&starttime='.str_replace(" ","%20",$dagh));
			}
			if ($response){ 
				$p1_rest = json_decode($response,true);
				if (date("Y-m-d", strtotime($dagh)) == date("Y-m-d", strtotime($p1_rest[0]["TIMESTAMP_lOCAL"]))){
					if ($found == 1){
						$p1rec['d']= $td;
						$p1rec['v1']= $p1_rest[0]["CONSUMPTION_KWH_LOW"] - $tv1;
						$p1rec['v2']= $p1_rest[0]["CONSUMPTION_KWH_HIGH"] - $tv2;
						$p1rec['r1']= $p1_rest[0]["PRODUCTION_KWH_LOW"] - $tr1;
						$p1rec['r2']= $p1_rest[0]["PRODUCTION_KWH_HIGH"] - $tr2;
						array_push($p1data, $p1rec);
						}
					$found = 1;
					$tv1 = $p1_rest[0]["CONSUMPTION_KWH_LOW"];
					$tv2 = $p1_rest[0]["CONSUMPTION_KWH_HIGH"];
					$tr1 = $p1_rest[0]["PRODUCTION_KWH_LOW"];
					$tr2 = $p1_rest[0]["PRODUCTION_KWH_HIGH"];
				} 
			}
			$td = date("Y-m-d", strtotime($dagh));
		}
	}		
			

	//-------------------------------------------------------------
	// haal gegevens van de inverter op
	$mysqli = new mysqli($host, $user, $passwd, $db, $port);
	$diff = array();
	$date_e = (new DateTime(sprintf("today %s",date("Y-m-d 23:59:59", strtotime($checkdatee)))))->getTimestamp();
	$p1revrow = ["se_day" => 0];
	$table = $inverter == 1 ? "telemetry_inverter" : "telemetry_inverter_3phase";
	if ($period == 'd' ) {
		$checkdate = date($JSON_SUM, strtotime("-$limit $JSON_period",$date));
	} else {
		$checkdate = date($JSON_SUM, strtotime("-$limit $JSON_period",$date))."-01";
	}
	$query = 'SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), '.$SQLdatefilter.') as iDate, (max(e_total)-min(e_total))/1000 as prod' .
			' FROM ' . $table .
			' WHERE timestamp <= UNIX_TIMESTAMP("' .  date('Y-m-d', $date) . ' 23:59:59") AND timestamp >= UNIX_TIMESTAMP("' .  $checkdate . '")' .
			' GROUP BY iDate' .
			' ORDER by iDate';
	// haal de gegevens van de inverter op $SQL_datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
	$inverter_data = $mysqli->query($query);
	$thread_id = $mysqli->thread_id;
	// Sluit DB
	$mysqli->kill($thread_id);
	$mysqli->close();


	// ================================================================================
	// loop through the dates and merge the data from P1-monitor and the Converter arrays
	//
	for ($i=0; $i<=$limit-1; $i++) {
		$pnum=$limit-$i-1;
		$datafound = 0;
		if ($period == 'm') {
			$checkdate = date($JSON_SUM, strtotime(date( 'Y-m-01' )." -$pnum $JSON_period",$date));
		} else {
			$checkdate = date($JSON_SUM, strtotime("-$pnum $JSON_period",$date));
		}
		$diff['idate'] = date("Y-m-d",strtotime(date($checkdate)));
		$diff['serie'] = date($JSON_SUM,strtotime(date($checkdate)));

		// get inverter data for that period
		$diff['prod'] = 0;
		foreach($inverter_data as $j => $row){
			$compdate = date($JSON_SUM,strtotime(date($row['iDate'])));
			if ($compdate==$checkdate) {
				$diff['prod'] = round($row["prod"],2);
				$datafound = 1;
			}
			if ($datafound == 0) {
				$diff['prod'] = 0.00;
			}
		}

		// Get&Sum P1-monitor data for this period
		$tv1 = 0;
		$tv2 = 0;
		$tr1 = 0;
		$tr2 = 0;
		$diff['v1'] = 0;
		$diff['v2'] = 0;
		$diff['r1'] = 0;
		$diff['r2'] = 0;
		foreach($p1data as $item) {     //foreach element in $arr
			$compdate = date($JSON_SUM,strtotime($item['d']));
			if ($compdate==$checkdate) {
				$diff['v1'] = round($item['v1'],2);
				$diff['v2'] = round($item['v2'],2);
				$diff['r1'] = round($item['r1'],2);
				$diff['r2'] = round($item['r2'],2);
				$datafound = 1;
			}
		}
		//voeg het resultaat toe aan de total-array
		if ($datafound == 1) {
			array_push($total, $diff);
		}
	}
}
//Output totale resultaat als JSON
echo json_encode($total);
?>
