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
// versie: 1.68.0
// auteur: André Rijkeboer
// datum:  29-05-2019
// omschrijving: ophalen van de stroom en energie gegevens van de panelen en de inverter (1 dag)

include('config.php');

$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";
if ($d1 == '') { $d1 = date("d-m-Y H:i:s", time()); }
$midnight = date("Y-m-d 00:00:00", strtotime($d1));
$today    = (new DateTime("today " . $midnight))->getTimestamp();
$tomorrow = (new DateTime("tomorrow " . $midnight))->getTimestamp();

$total = array();
$diff = array();
$paneel = array();

if ($aantal < 0) { $aantal = 0; }
for ($i = 1; $i <= $aantal; $i++) {
	$paneel[$i]['uptime'] = 0;
	$paneel[$i]['energie'] = 0;
	$paneel[$i]['verschil'] = 0;
}

//open MySQL database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);
// haal gegevens van de panelen op
$query = sprintf("SELECT HEX(op_id) optimizer, timestamp, uptime, v_in*i_in*0.125*0.00625 as vermogen, e_day*0.25 as energie, temperature
	          FROM telemetry_optimizers
		  WHERE timestamp > %s AND timestamp <= %s
	          ORDER BY timestamp;",
		$today, $tomorrow);
$result = $mysqli->query($query);

//Zet de waarden bij het juiste paneel
while ($row = mysqli_fetch_assoc($result)) {
	for ($i = 1; $i <= $aantal; $i++) {
		if ($row['optimizer'] == $op_id[$i][0]) {
			$diff['serie']  = 0;
			$diff['op_id']  = $i;
			$diff['ts'] = $row['timestamp'] * 1000;
			$diff['temperature'] = $row['temperature']*2;
			$diff['cp'] = sprintf("%.3f", $row['vermogen']);

			if ($paneel[$i]['uptime'] > $row['uptime']) {
				$paneel[$i]['verschil'] = $paneel[$i]['energie'];
			}
			$diff['vp'] = sprintf("%.3f", $row['energie'] + $paneel[$i]['verschil']);
			$paneel[$i]['energie'] = $diff['vp'];
			$paneel[$i]['uptime'] = $row['uptime'];
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
