<?php
//
// versie: 1.1
// auteur: Jos van der Zande  based on model from André Rijkeboer
//
// datum:  12-03-2018
// omschrijving: ophalen van de P1meter informatie uit Domoticz en SolarEdge gegeven om ze samen in 1 grafiek te laten zien
//
// De verwachte JSON output is voor "period=c"  (current data)   aantal= wordt niet gebruikt
//[{
// "ServerTime" : "2019-03-13 11:48:40",
// "CounterDelivToday" : "1.349 kWh",
// "CounterToday" : "3.893 kWh",
// "Usage" : "167 Watt",
// "UsageDeliv" : "0 Watt",
//}]

// De verwachte JSON output is voor "period=d&aantal=xx"
// [
// {"idate":"2019-02-10","serie":"2019-02-10","prod":0,"v1":10.94,"v2":0,"r1":0,"r2":0},
// {"idate":"2019-02-11","serie":"2019-02-11","prod":0,"v1":3.68,"v2":9.92,"r1":0,"r2":0},
// {"idate":"2019-02-12","serie":"2019-02-12","prod":0,"v1":3.45,"v2":8.49,"r1":0,"r2":0}
// ]
// De verwachte JSON output is voor "period=m&aantal=xx"
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

$SQLdatefilter = '"%Y-%m-%d"';
if($period == '' || $period == 'd' ) {
	$SQLdatefilter = '"%Y-%m-%d"';
	$JSON_SUM = "Y-m-d";
	$JSON_period = "day";
} elseif ($period == 'm') {
	$SQLdatefilter = '"%Y-%m"';
	$JSON_SUM = "Y-m";
	$JSON_period = "month";
} else {
	$SQLdatefilter = '"%Y-%m-%d"';
	$JSON_SUM = "Y-m-d";
	$JSON_period = "day";
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
$domodata = array();
$domorec = array();
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
	// ============================================================================================
	// Laad de data van het laatste jaar voor de P1_ElectriciteitsMeter uit domoticz
	$response = file_get_contents('http://'.$domohost.'&/json.htm?type=graph&sensor=counter&idx='.$domoidx.'&range=year');
	$domo_rest = json_decode($response,true);
	$domo_data_cy = $domo_rest['result'];

	// ==================================================================================================
	$checkdate = date($JSON_SUM, strtotime("-$limit $JSON_period"));
	$i=0;
	$savedate=0;
	// process all Domoticz data and sum for the requested period
	// last year data
	if( ($JSON_period == "day" && $limit > 364) || ($JSON_period == "month" && $limit > 11) ) {
		$domo_data_py = $domo_rest['resultprev'];
		foreach($domo_data_py as $item) {
			$compdate = date($JSON_SUM,strtotime($item['d']));
			if($compdate>=$checkdate){
				if($savedate!=$compdate) {
					if ($savedate!=0) {
						$domorec['d']= $td;
						$domorec['v1']= $tv1;
						$domorec['v2']= $tv2;
						$domorec['r1']= $tr1;
						$domorec['r2']= $tr2;
						array_push($domodata, $domorec);
						$td = $item['d'];
					}
					$savedate=$compdate;
					$td = $item['d'];
					$tv1 = $item['v'];
					$tv2 = $item['v2'];
					$tr1 = $item['r1'];
					$tr2 = $item['r2'];
				} else {
					$tv1 += $item['v'];
					$tv2 += $item['v2'];
					$tr1 += $item['r1'];
					$tr2 += $item['r2'];
				}
			}
		}
	}
	//-------------------------------------------------------------------
	// process this year data
	foreach($domo_data_cy as $item) {
		$compdate = date($JSON_SUM,strtotime($item['d']));
		if($compdate>=$checkdate){
			if($savedate!=$compdate) {
				if ($savedate!=0) {
					$domorec['d']= $td;
					$domorec['v1']= $tv1;
					$domorec['v2']= $tv2;
					$domorec['r1']= $tr1;
					$domorec['r2']= $tr2;
					array_push($domodata, $domorec);
					$td = $item['d'];
				}
				$savedate=$compdate;
				$td = $item['d'];
				$tv1 = $item['v'];
				$tv2 = $item['v2'];
				$tr1 = $item['r1'];
				$tr2 = $item['r2'];
			} else {
				$tv1 += $item['v'];
				$tv2 += $item['v2'];
				$tr1 += $item['r1'];
				$tr2 += $item['r2'];
			}
		}
	}
	// write last record
	if ($savedate != 0){
		$domorec['d']= $td;
		$domorec['v1']= $tv1;
		$domorec['v2']= $tv2;
		$domorec['r1']= $tr1;
		$domorec['r2']= $tr2;
		array_push($domodata, $domorec);
	}

	//-------------------------------------------------------------
	//open MySQL database
	$mysqli = new mysqli($host, $user, $passwd, $db, $port);
	// haal gegevens van de inverter op
	$diff = array();
	$date_i = $date-365*86400;
	$p1revrow = ["se_day" => 0];
	if ($inverter == 1){
		// haal de gegevens van de enkel fase inverter op $SQL_datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
		$inverter_data = $mysqli->query(
				' SELECT * FROM ( ' .
				'    SELECT DATE_FORMAT(t1.d, '.$SQLdatefilter.') as oDate, DATE(t1.d) as iDate, sum(IFNULL(t1.tzon,0)) as prod ' .
				'	 FROM (	SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, sum(de_day)/1000 as tzon ' .
				'	 		FROM   solaredge.telemetry_inverter ' .
				'			GROUP BY d  ' .
				'		  ) t1 ' .
				' GROUP BY oDate ' .
				' ORDER by t1.d desc ' .
				' LIMIT '.$limit.') output' .
				' ORDER by oDate ;') ;
	}else{
		// haal de gegevens van de 3 fase inverter op
	}
	// Sluit DB
	$thread_id = $mysqli->thread_id;
	$mysqli->kill($thread_id);
	$mysqli->close();


	// ================================================================================
	// loop through the dates and merge the data from Domoticz and the Converter arrays
	//
	for ($i=0; $i<=$limit-1; $i++) {
		$pnum=$limit-$i-1;
		$datafound = 0;
		$checkdate = date($JSON_SUM, strtotime("-$pnum $JSON_period"));
		$diff['idate'] = date("Y-m-d",strtotime(date($checkdate)));
		$diff['serie'] = date($JSON_SUM,strtotime(date($checkdate)));

		// get inverter data for that period
		foreach($inverter_data as $j => $row){
			$compdate = date($JSON_SUM,strtotime(date($row['iDate'])));
			if ($compdate==$checkdate) {
				//~ 			echo "! yes compdate:".$compdate." \n";
				$diff['prod'] = round($row["prod"],2);
				$datafound = 1;
			}
			if ($datafound == 0) {
				$diff['prod'] = 0.00;
			}
		}

		// Get&Sum Domoticz data for this period
		$tv1 = 0;
		$tv2 = 0;
		$tr1 = 0;
		$tr2 = 0;
		foreach($domodata as $item) {     //foreach element in $arr
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