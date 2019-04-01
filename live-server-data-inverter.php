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
// versie: 1.21
// auteur: André Rijkeboer
// datum:  29-03-2019
// omschrijving: ophalen van de stroom en energie gegevens van de panelen en de inverter (1 dag)

include('config.php');

$d1 = $_GET['date'];
if ($d1 == '') { $d1 = date("d-m-Y H:i:s", time()); }
$midnight = date("Y-m-d 00:00:00", strtotime($d1));
$today    = (new DateTime("today " . $midnight))->getTimestamp();
$tomorrow = (new DateTime("tomorrow " . $midnight))->getTimestamp();

$total = array();
$diff = array();

$dagen = 14;
$dag = [gmdate("d", $today-($dagen-1)*86400), $dagen];
$date_i = $today-$dagen*86400;

//open MySQL database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);
// enkel of drie fase inverter
$table   = $inverter == 1 ? 'telemetry_inverter' : 'telemetry_inverter_3phase';
$sqlcols = $inverter == 1 ? 'v_ac, i_ac, v_dc, p_active'
			  : '(v_ac1+v_ac2+v_ac3)/3 as v_ac, (i_ac1+i_ac2+i_ac3) as i_ac, v_dc, (p_active1+p_active2+p_active3) as p_active';
// haal de gegevens van de inverter op
$de_day_total = 0;
$diff['jaar']   = gmdate("Y", strtotime($d1));
$diff['maand']  = gmdate("m", strtotime($d1))-1;
$diff['dag']    = gmdate("d", strtotime($d1));
foreach ($mysqli->query(
		'SELECT timestamp, IF(temperature = 0, NULL, temperature) temperature, de_day, ' . $sqlcols .
		' FROM ' . $table .
		' WHERE e_day>0 AND timestamp BETWEEN ' . $date_i . ' AND ' . $tomorrow .
		' ORDER BY timestamp')
                as $j => $row) {
	while ($dag[0] != gmdate("d", $row['timestamp'])) {
		$dag[1]--;
		$dag[0] = gmdate("d", $today-($dag[1]-1)*86400);
		$de_day_total = 0;
	}
	$de_day_total += $row["de_day"];
	$diff['op_id']  = "i";
	$diff['serie']  = $dag[1];
	$diff['uur']    = gmdate("H",$row['timestamp']);
	$diff['minuut'] = gmdate("i",$row['timestamp']);
	$diff['sec']    = gmdate("s",$row['timestamp']);
	$diff['temperature'] = $row['temperature'] * 2;
	$diff['p1_volume_prd'] = round($de_day_total/1000,3);
	$diff['p1_current_power_prd'] = $row['p_active'];
	//voeg het resultaat toe aan de total-array
	array_push($total, $diff);
}
	
// Sluit DB	
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();
//Output totale resultaat als JSON
echo json_encode($total);
?>
