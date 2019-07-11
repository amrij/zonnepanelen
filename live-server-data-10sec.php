<?php
#
# Copyright (C) 2019 Jos van der Zande en André Rijkeboer
#
# This file is part of zonnepanelen, which shows telemetry data from
# the TCP traffic of SolarEdge PV inverters.
#
# zonnepanelen is free software: you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by the
# Free Software Foundation, either version 3 of the License, or (at
# your option) any later version.
#
# zonnepanelen is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with zonnepanelen.  If not, see <http://www.gnu.org/licenses/>.
#
# versie: 1.70.0
# auteurs:
#	Jos van der Zande 	case 1: Domoticz, case: 2 DSMR
# 	André Rijkeboer		case 3: P1 Database
# datum:  29-06-2019

// omschrijving: ophalen van de P1meter informatie uit case: 1 Domoticz, case: 2 DSMR, case: 3 P1 Database en SolarEdge gegevens om ze samen in 1 grafiek te laten zien
//
//~ URL tbv live data p1 Meter: live-server-data-electra-10sec.php/period=c
//~ ==========================================================================
//~ De verwachte JSON output is voor "period=c"  (current data)   aantal= wordt niet gebruikt
//~ [{
//~  "ServerTime" : "2019-03-13 11:48:40",
//~  "CounterDelivToday" : "1.349 kWh",
//~  "CounterToday" : "3.893 kWh",
//~  "Usage" : "167 Watt",
//~  "UsageDeliv" : "0 Watt",
//~ }]

//~ URL tbv dag grafiek p1 Meter: live-server-data-electra-10sec.php/period=d&aantal=60
//~ ======================================================================================
//~ De verwachte JSON output is voor "period=d&aantal=xx"
//~ [
//~ {"idate":"2019-02-10","serie":"2019-02-10","prod":0,"v1":10.94,"v2":0,"r1":0,"r2":0},
//~ {"idate":"2019-02-11","serie":"2019-02-11","prod":0,"v1":3.68,"v2":9.92,"r1":0,"r2":0},
//~ {"idate":"2019-02-12","serie":"2019-02-12","prod":0,"v1":3.45,"v2":8.49,"r1":0,"r2":0}
//~ ]

//~ URL tbv maand grafiek p1 Meter: live-server-data-electra-10-sec.php/period=m&aantal=13
//~ ========================================================================================
//~ De verwachte JSON output is voor "period=m&aantal=xx"
//~ [
//~ {"idate":"2019-01-01","serie":"2019-01","prod":0,"v1":186.47,"v2":181.2,"r1":0,"r2":0},
//~ {"idate":"2019-02-01","serie":"2019-02","prod":137.72,"v1":163.95,"v2":154.13,"r1":36.64,"r2":71.46},
//~ {"idate":"2019-03-01","serie":"2019-03","prod":128.23,"v1":63.63,"v2":34.71,"r1":15.8,"r2":72.11}
//~ ]

// ## Ten behove van de P1 Database zijn de volgende gegevens noodzakelijk:
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

include('config.php');

//open MySQL database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);

$limit = array_key_exists('aantal', $_GET) ? $_GET['aantal'] : "";
$period = array_key_exists('period', $_GET) ? $_GET['period'] : "";
$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";

if($d1 == '') { $d1 = date("d-m-Y H:i:s", time()); }

$total = array();
$diff = array();
$table = $inverter == 1 ? 'telemetry_inverter' : 'telemetry_inverter_3phase';


switch ($methode){
	case "1": // domoticz
		if($limit == '') { $limit = '30'; }
		if ($period == 'm') {
			$SQLdatefilter = '"%Y-%m"';
			$JSON_SUM = "Y-m";
			$JSON_period = "month";
		} else {
			$SQLdatefilter = '"%Y-%m-%d"';
			$JSON_SUM = "Y-m-d";
			$JSON_period = "day";
		}
		
		$date = (new DateTime("today " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$tomorrow = (new DateTime("tomorrow " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$domodata = array();
		$domorec = array();

		// Get current info for P1_ElectriciteitsMeter from domoticz
		if ($period == 'c' ) {
			//Get current info for P1_ElectriciteitsMeter from domoticz
			$response = file_get_contents('http://'.$domohost.'&/json.htm?type=devices&rid='.$domoidx);
			$domo_rest = json_decode($response,true);
			$diff['ts'] = strtotime($domo_rest["ServerTime"]) * 1000;
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
			$checkdate = date($JSON_SUM, strtotime("-$limit $JSON_period",$date));
			$checkdates = date($JSON_SUM, strtotime("-$limit $JSON_period",$date));
			$checkdatee = date("Y-m-d", $date);
			$i=0;
			$savedate=0;
			// process all Domoticz data and sum for the requested period
			// last year data
			$domo_data_py = $domo_rest['resultprev'];
			foreach($domo_data_py as $item) {
				$compdate = date($JSON_SUM,strtotime($item['d']));
				$compdate2 = date("Y-m-d",strtotime($item['d']));
				if($compdate>=$checkdates && $compdate2<=$checkdatee){
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
			//-------------------------------------------------------------------
			// process this year data
			foreach($domo_data_cy as $item) {
				$compdate = date($JSON_SUM,strtotime($item['d']));
				$compdate2 = date("Y-m-d",strtotime($item['d']));
				if($compdate>=$checkdates && $compdate2<=$checkdatee){
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
			// haal gegevens van de inverter op
			$diff = array();
			$date_e = (new DateTime(sprintf("today %s",date("Y-m-d 23:59:59", strtotime($checkdatee)))))->getTimestamp();
			$p1revrow = ["se_day" => 0];
			$query = ' SELECT * FROM ( ' .
				'    SELECT DATE_FORMAT(t1.d, '.$SQLdatefilter.') as oDate, DATE(t1.d) as iDate, sum(IFNULL(t1.tzon,0)) as prod ' .
				'	 FROM (	SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, (max(e_total)-min(e_total))/1000 as tzon ' .
				'	 		FROM ' . $table .
				'			WHERE timestamp < ' . $tomorrow .
				'			GROUP BY d  ' .
				'		  ) t1 ' .
				' GROUP BY oDate ' .
				' ORDER by t1.d desc ' .
				' LIMIT '.$limit.') output' .
				' ORDER by oDate ;';
			// haal de gegevens van de inverter op $SQL_datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
			$inverter_data = $mysqli->query($query);
			$thread_id = $mysqli->thread_id;
			// Sluit DB
			$mysqli->kill($thread_id);
			$mysqli->close();


			// ================================================================================
			// loop through the dates and merge the data from Domoticz and the Converter arrays
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

				// Get&Sum Domoticz data for this period
				$tv1 = 0;
				$tv2 = 0;
				$tr1 = 0;
				$tr2 = 0;
				$diff['v1'] = 0;
				$diff['v2'] = 0;
				$diff['r1'] = 0;
				$diff['r2'] = 0;
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
		break;
	case "2": // DSMR
		if($period == '' || $period == 'd' ) {
			$SQLdatefilter = '"%Y-%m-%d"';
			$JSON_SUM = "Y-m-d";
			$JSON_period = "day";
			if($limit == '') { $limit = '30'; }
			$periods = $limit-1;
		} elseif ($period == 'm') {
			$SQLdatefilter = '"%Y-%m"';
			$JSON_SUM = "Y-m";
			$JSON_period = "month";
			if($limit == '') { $limit = '12'; }
			$periods = $limit*31-1;
		}

		$date = (new DateTime("today " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$tomorrow = (new DateTime("tomorrow " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$today = new DateTime();
		$dsmrdata = array();
		$dsmrrec = array();

		// Get current info for P1_ElectriciteitsMeter from DSMR server
		if ($period == 'c' ) {
			//Get current info for P1_ElectriciteitsMeter from DSMR server
			$response = file_get_contents($dsmr_url.'/api/v2/consumption/electricity-live', null, stream_context_create(array(
					'http' => array(
					'method' => 'GET',
					'header' => array('X-AUTHKEY:'.$dsmr_apikey))
					)
			));
			$dsmr_restc = json_decode($response,true);

			//Get today info for P1_ElectriciteitsMeter from DSMR server
			$response = file_get_contents($dsmr_url.'/api/v2/consumption/today', null, stream_context_create(array(
					'http' => array(
					'method' => 'GET',
					'header' => array('X-AUTHKEY:'.$dsmr_apikey))
					)
			));
			$dsmr_restd = json_decode($response,true);

			$diff['ts'] = strtotime($dsmr_restc['timestamp']) * 1000;
			$diff['CounterToday'] = round((floatval($dsmr_restd['electricity1'])+floatval($dsmr_restd['electricity2'])),3);
			$diff['CounterDelivToday'] = round((floatval($dsmr_restd['electricity1_returned'])+floatval($dsmr_restd['electricity2_returned'])),3);
			$diff['Usage'] = round(floatval($dsmr_restc['currently_delivered']),3);
			$diff['UsageDeliv'] = round(floatval($dsmr_restc['currently_returned']),3);
			array_push($total, $diff);
		} else {
			// ============================================================================================
			// Laad de data van het laatste jaar voor de P1_ElectriciteitsMeter uit DSMR server

			$sdate=date("Y-m-d", strtotime("-$periods day",$date));
			$response = file_get_contents($dsmr_url.'/api/v2/statistics/day?day__gte='.$sdate.'&limit='.($periods+1).'', null, stream_context_create(array(
					'http' => array(
					'method' => 'GET',
					'header' => array('X-AUTHKEY:'.$dsmr_apikey))
					)
			));
			$dsmr_rest = json_decode($response,true);
			$dsmr_data_cy = $dsmr_rest['results'];

			// ==================================================================================================
			$checkdate = date($JSON_SUM, strtotime("-$limit $JSON_period",$date));
			$checkdates = date($JSON_SUM, strtotime("-$limit $JSON_period",$date));
			$checkdatee = date("Y-m-d", $date);

			$i=0;
			$savedate=0;
			// process all DSMR data and sum for the requested period
			foreach($dsmr_data_cy as $item) {
				$compdate = date($JSON_SUM,strtotime($item['day']));
				$compdate2 = date("Y-m-d",strtotime($item['day']));
				if($compdate>=$checkdates && $compdate2<=$checkdatee){
					if($savedate!=$compdate) {
						if ($savedate!=0) {
							$dsmrrec['d']= $td;
							$dsmrrec['v1']= $tv1;
							$dsmrrec['v2']= $tv2;
							$dsmrrec['r1']= $tr1;
							$dsmrrec['r2']= $tr2;
							array_push($dsmrdata, $dsmrrec);
						}
						$savedate=$compdate;
						$td = $item['day'];
						$tv1 = $item['electricity1'];
						$tv2 = $item['electricity2'];
						$tr1 = $item['electricity1_returned'];
						$tr2 = $item['electricity2_returned'];
					} else {
						$tv1 += $item['electricity1'];
						$tv2 += $item['electricity2'];
						$tr1 += $item['electricity1_returned'];
						$tr2 += $item['electricity2_returned'];
					}
				}
			}


			$tcompdate = date("Y-m-d");
			if($tcompdate<=$checkdatee){
				//Get today info for P1_ElectriciteitsMeter from DSMR server
				$response = file_get_contents($dsmr_url.'/api/v2/consumption/today', null, stream_context_create(array(
						'http' => array(
						'method' => 'GET',
						'header' => array('X-AUTHKEY:'.$dsmr_apikey))
						)
				));
				// get todays info from DSMR since that is not part of the previous call
				$dsmr_rest = json_decode($response,true);
				$compdate = date($JSON_SUM,strtotime($dsmr_rest['day']));
				if ($savedate != 0){
					if ($compdate != $savedate) {
						$dsmrrec['d']= $td;
						$dsmrrec['v1']= $tv1;
						$dsmrrec['v2']= $tv2;
						$dsmrrec['r1']= $tr1;
						$dsmrrec['r2']= $tr2;
						array_push($dsmrdata, $dsmrrec);
						//echo "Write1a: " . $td."  v1:".$tv1."  v2:".$tv2."\n";
						$tv1 = 0;
						$tv2 = 0;
						$tr1 = 0;
						$tr2 = 0;
					}
				}
				$dsmrrec['d']= $compdate;
				$dsmrrec['v1']= $tv1 + round(floatval($dsmr_rest['electricity1']),3);
				$dsmrrec['v2']= $tv2 + round(floatval($dsmr_rest['electricity2']),3);
				$dsmrrec['r1']= $tr1 + round(floatval($dsmr_rest['electricity1_returned']),3);
				$dsmrrec['r2']= $tr2 + round(floatval($dsmr_rest['electricity2_returned']),3);
				array_push($dsmrdata, $dsmrrec);
			} else {
				if ($savedate != 0){
					$dsmrrec['d']= $td;
					$dsmrrec['v1']= $tv1;
					$dsmrrec['v2']= $tv2;
					$dsmrrec['r1']= $tr1;
					$dsmrrec['r2']= $tr2;
					array_push($dsmrdata, $dsmrrec);
				}
			}
			//-------------------------------------------------------------
			$diff = array();
			$p1revrow = ["se_day" => 0];
			$query = ' SELECT * FROM ( ' .
				'    SELECT DATE_FORMAT(t1.d, '.$SQLdatefilter.') as oDate, DATE(t1.d) as iDate, sum(IFNULL(t1.tzon,0)) as prod ' .
				'        FROM ( SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, (max(e_total)-min(e_total))/1000 as tzon ' .
				'                       FROM ' . $table .
				'                       WHERE timestamp < ' . $tomorrow .
				'                       GROUP BY d  ' .
				'                 ) t1 ' .
				' GROUP BY oDate ' .
				' ORDER by t1.d desc ' .
				' LIMIT '.$limit.') output' .
				' ORDER by oDate ;';
			// haal de gegevens van de inverter op $SQL_datefilter = 'DATE_FORMAT(t2.d, "%Y-%m-%d")';
			$inverter_data = $mysqli->query($query);
			$thread_id = $mysqli->thread_id;
			// Sluit DB
			$mysqli->kill($thread_id);
			$mysqli->close();

			// ================================================================================
			// loop through the dates and merge the data from DSMR server and the Converter arrays
			//
			for ($i=0; $i<=$limit-1; $i++) {
				$pnum=$limit-$i-1;
				$datafound = 0;
				if ($period == 'm') {
					$checkdate = date($JSON_SUM, strtotime(date( 'Y-m' )." -$pnum $JSON_period",$date));
				} else {
					$checkdate = date($JSON_SUM, strtotime("-$pnum $JSON_period",$date));
				}
				$diff['idate'] = date("Y-m-d",strtotime(date($checkdate)));
				$diff['serie'] = date($JSON_SUM,strtotime(date($checkdate)));
				$diff['prod'] = 0;
				$diff['v1'] = 0;
				$diff['v2'] = 0;
				$diff['r1'] = 0;
				$diff['r2'] = 0;

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

				// Get&Sum DSMR server data for this period
				$tv1 = 0;
				$tv2 = 0;
				$tr1 = 0;
				$tr2 = 0;
				$diff['v1'] = 0;
				$diff['v2'] = 0;
				$diff['r1'] = 0;
				$diff['r2'] = 0;
				foreach($dsmrdata as $item) {     //foreach element in $arr
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
		break;
	case "3": // P1 Database
		if ($limit == '') { $limit = '30'; }
		if ($period == '') { $period = 'c'; }
		if ($period == 'm') {
			$datefilter = "%Y-%m";
			$SQLdatefilter1 = 'Y-m';
			$limitf = 31*$limit*86400;
		} else {
			$datefilter = "%Y-%m-%d";
			$SQLdatefilter1 = 'Y-m-d';
			$limitf =  $limit * 86400;
		}

		$time = strtotime(gmdate("d-m-Y 12:00:00",(new DateTime("tomorrow " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp()));
		$d3 = date("Y-m-d", strtotime($d1));
		$d3a = date("d", strtotime($d1));
		$d3b = date("Y-m", strtotime($d1));
		if ($d3a == '01' and $period == 'm'){
			$limit -=1;
		}
		$d2 = time();
		$date = (new DateTime("today " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$yesterday1 = gmdate("Y-m-d",(new DateTime("yesterday " . date("Y-m-d 12:00:00", time())))->getTimestamp());
		$morgen = date("Y-m-d",(new DateTime("tomorrow " . date("Y-m-d 12:00:00", $time)))->getTimestamp());
		$today = gmdate("Y-m-d H:i:s",(new DateTime("today " . date("Y-m-d 00:00:00", $time)))->getTimestamp());
		$tomorrow = (new DateTime("tomorrow " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();
		$yesterday = (new DateTime("yesterday " . date("Y-m-d 00:00:00", strtotime($d1))))->getTimestamp();

		// controle of P1_Meter_overzicht bestaat
		$val = $mysqli->query('select 1 from `P1_Meter_Overzicht` LIMIT 1');

		if($val == FALSE)
		{
			// creëer tabel P1_Meter_Overzicht 
			$sql = "CREATE TABLE P1_Meter_Overzicht (
				  datum date NOT NULL COMMENT 'Datum',
				  prod float DEFAULT NULL COMMENT 'Solar productie',
				  v1 float DEFAULT NULL COMMENT 'Verbruik laag tarief',
				  v2 float DEFAULT NULL COMMENT 'Verbruik hoog tarief',
				  r1 float DEFAULT NULL COMMENT 'Retour laag tarief',
				  r2 float DEFAULT NULL COMMENT 'Retour hoog tarief',
				  PRIMARY KEY (datum),
				  KEY (datum)
				)";

			if ($mysqli->query($sql) !== TRUE) {
				echo "Error creating table: " . $mysqli->error;
			}

		}
		// controleer of er iets in P1_Meter_Overzicht staat
		$val = $mysqli->query('select UNIX_TIMESTAMP(datum) datum from P1_Meter_Overzicht order by datum desc LIMIT 1');
		$row = mysqli_fetch_assoc($val);
		if ($row){
			// zet de laaste gegevens in P1_Meter_Overzicht
			$van = $row[datum];
			$sql = 'INSERT INTO P1_Meter_Overzicht(datum, prod, v1, v2, r1, r2) ' .
				'	SELECT * FROM (SELECT DATE(t2.d) as datum, sum(IFNULL(t1.tzon,0)) as prod, ' .
				'						sum(t2.sv1) as v1, sum(t2.sv2) as v2, ' .
				'						sum(t2.sr1) as r1, sum(t2.sr2) as r2 ' .
				'			FROM (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, ' .
				'					max(mv1)-min(mv1) as sv1, max(mv2)-min(mv2) as sv2, ' .
				'					max(mr1)-min(mr1) as sr1, max(mr2)-min(mr2) as sr2 ' .
				'				FROM P1_Meter ' .
				'				where timestamp >= ' . $van . ' and timestamp < ' . $tomorrow .
				'				GROUP BY d ) t2 ' .
				'			left join (SELECT timestamp, DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, ' .
				'					(max(e_total)-min(e_total))/1000 as tzon ' .
				'				FROM ' . $table .
				'				where timestamp >= ' . $van . ' and timestamp < ' . $tomorrow .
				'				GROUP BY d ) t1 ON t1.d = t2.d ' .
				'		GROUP BY datum) output ' .
				'		ORDER by Datum ' .
				'ON DUPLICATE KEY UPDATE prod = output.prod, v1 = output.v1, v2 = output.v2, r1 = output.r1, r2 = output.r2';
			if ($mysqli->query($sql) !== TRUE) {
				echo "Error update table: " . $mysqli->error;
			}
		}else{
			// vul P1_Meter_Overzicht met de gegevens
			$sql = 'INSERT INTO P1_Meter_Overzicht(datum, prod, v1, v2, r1, r2) ' .
				'	SELECT * FROM (SELECT DATE(t2.d) as datum, sum(IFNULL(t1.tzon,0)) as prod, ' .
				'						sum(t2.sv1) as v1, sum(t2.sv2) as v2, ' .
				'						sum(t2.sr1) as r1, sum(t2.sr2) as r2 ' .
				'			FROM (SELECT DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, ' .
				'					max(mv1)-min(mv1) as sv1, max(mv2)-min(mv2) as sv2, ' .
				'					max(mr1)-min(mr1) as sr1, max(mr2)-min(mr2) as sr2 ' .
				'				FROM P1_Meter ' .
				'				where timestamp < ' . $tomorrow .
				'				GROUP BY d ) t2 ' .
				'			left join (SELECT timestamp, DATE_FORMAT(DATE(FROM_UNIXTIME(timestamp)), "%Y-%m-%d") as d, ' .
				'					(max(e_total)-min(e_total))/1000 as tzon ' .
				'				FROM ' . $table .
				'				where timestamp < ' . $tomorrow .
				'				GROUP BY d ) t1 ON t1.d = t2.d ' .
				'		GROUP BY datum) output ' .
				'		ORDER by Datum';
			if ($mysqli->query($sql) !== TRUE) {
				echo "Error insert table: " . $mysqli->error;
			}
		}
		// Get current info for P1_ElectriciteitsMeter from solaredge database
		if ($period == 'c' ){
			// ***************************************************************************************************************
			// Haal huidig energy verbruik/retour op van de P1_ElectriciteitsMeter .... ????
			// ***************************************************************************************************************
			$result = $mysqli->query("SELECT timestamp, dv, dr, cv, cr" .
						" From P1_Meter" .
						" where timestamp >= " . $yesterday. " and timestamp < " . $tomorrow .
						" order by timestamp desc limit 1");
			$row = mysqli_fetch_assoc($result);	
			if ($row){
				$diff['ts'] = $row['timestamp'] * 1000;
				$diff['CounterToday'] = $row['dv'];
				$diff['CounterDelivToday'] = $row['dr'];
				$diff['Usage'] = $row['cv'];
				$diff['UsageDeliv'] = $row['cr'];
			} else {
				$diff['ts'] = $time * 1000;
				$diff['CounterToday'] = 0;
				$diff['CounterDelivToday'] = 0;
				$diff['Usage'] = 0;
				$diff['UsageDeliv'] = 0;
			}		
			array_push($total, $diff);
		} else {
			// haal gegevens van de panelen op
			$diff = array();
			$p1revrow = ["se_day" => 0];
			// haal de gegevens op
			foreach($mysqli->query('SELECT * FROM ( ' .
						'	SELECT DATE_FORMAT(t2.d, "' . $datefilter . '") as oDate, DATE(t2.d) as iDate, sum(t2.tzon) as prod, sum(t2.sv1) as v1, sum(t2.sv2) as v2, sum(t2.sr1) as r1, sum(t2.sr2) as r2 ' .
						'	FROM	(SELECT DATE_FORMAT(datum, "%Y-%m-%d") as d, sum(v1) as sv1, sum(v2) as sv2, sum(r1) as sr1, sum(r2) as sr2, sum(prod) as tzon ' .
						'			FROM   P1_Meter_Overzicht ' .
						'       		where datum < "'.$morgen.'"'.
						'			GROUP BY d ' .
						'		) t2 ' .
						'	GROUP BY oDate ' .
						'	ORDER by t2.d desc ' .
						'	LIMIT '.$limit.' ) output' .
						' ORDER by oDate ;') as $j => $row){
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
 
		break;
}

// Sluit DB
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();

//Output totale resultaat als JSON
echo json_encode($total);
?>
