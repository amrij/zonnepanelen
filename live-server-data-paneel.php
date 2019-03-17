<?php
//
// Copyright (C) 2019 André Rijkeboer
//
// This file is part of zonnepanelen, which shows telemetry data from
// the TCP traffic of SolarEdge PV inverters.
//
// zonnepanelen is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the
// Free Software Foundation, either version 3 of the License, or (at
// your option) any later version.
//
// zonnepanelen is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with zonnepanelen.  If not, see <http://www.gnu.org/licenses/>.
//
// versie: 1.11
// auteur: André Rijkeboer
// datum:  17-03-2019
// omschrijving: ophalen van de stroom en energie gegevens van de panelen en de inverter (15 dagen)

$d1 = $_GET['date'];
if($d1 == ''){ 
$d1 = date("d-m-Y H:i:s", time());
}
$d3 = date("Y-m-d", strtotime($d1));
$d2 = time();
$date = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$op_id = array();
$total = array();
$mode = array();
$diff = array();
$paneel = array();
include('config.php');
//open MySQL database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);
// haal gegevens van de panelen op
$diff = array();
$format = '%H:%i:%s';
$query = sprintf("SELECT HEX(`op_id`) optimizer, `timestamp`, uptime, `v_in`*`i_in`*0.125*0.00625 vermogen, e_day*0.25 as energie, `temperature`
	FROM  (
	SELECT `op_id`,`timestamp`,`v_in`,`v_out`,`i_in`, `temperature`, `e_day`, `uptime`
	FROM `telemetry_optimizers` 
		WHERE `timestamp` > %s AND `timestamp` <= %s 
	ORDER BY `timestamp` 
	) x;", $date, $tomorrow);
$result = $mysqli->query($query);
if ($aantal > 33) { $aantal = 33;}
if ($aantal < 0) { $aantal = 0;}
for ($i  = 1; $i <= $aantal; $i++){
	$paneel[$i]['uptime']= 0;
	$paneel[$i]['energie'] = 0;
	$paneel[$i]['verschil'] = 0;
}

//Zet de waarden bij het juiste paneel
while ($row = mysqli_fetch_assoc($result)) {
	for ($i = 1; $i <= $aantal; $i++){
		if ($row['optimizer'] == $op_id[$i][0]) {
			if ($paneel[$i]['uptime'] <=  $row['uptime']) {
				$diff['op_id'] = $i;
				$diff['serie'] = 0;
				$diff['jaar'] = gmdate("Y", strtotime($d1));
				$diff['maand'] = gmdate("m", strtotime($d1))-1;
				$diff['dag'] = gmdate("d", strtotime($d1));
				$diff['uur'] = gmdate("H",$row['timestamp']);
				$diff['minuut'] = gmdate("i",$row['timestamp']);
				$diff['sec'] = gmdate("s",$row['timestamp']);
				$diff['p1_volume_prd'] = round($row['energie'] + $paneel[$i]['verschil'],3);
				$diff['p1_current_power_prd'] = $row['vermogen'];
				$diff['temperature'] = $row['temperature'];
				$paneel[$i]['uptime'] = $row['uptime'];
				$paneel[$i]['energie'] = $diff['p1_volume_prd'];
			}else{
				$diff['op_id'] = $i;
				$diff['serie'] = 0;
				$diff['jaar'] = gmdate("Y", strtotime($d1));
				$diff['maand'] = gmdate("m", strtotime($d1))-1;
				$diff['dag'] = gmdate("d", strtotime($d1));
				$diff['uur'] = gmdate("H",$row['timestamp']);
				$diff['minuut'] = gmdate("i",$row['timestamp']);
				$diff['sec'] = gmdate("s",$row['timestamp']);
				$paneel[$i]['verschil'] = $paneel[$i]['energie'];
				$diff['p1_volume_prd'] = round($row['energie'] + $paneel[$i]['verschil'],3);
				$diff['p1_current_power_prd'] = $row['vermogen'];
				$diff['temperature'] = $row['temperature'];
				$paneel[$i]['uptime'] = $row['uptime'];
				$paneel[$i]['energie'] = $diff['p1_volume_prd'];
			}
			//voeg het resultaat toe aan de total-array
			array_push($total, $diff);
		}
	}
}

	
// Sluit DB	
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();
//Output totale resultaat als JSON
echo json_encode($total);
?>