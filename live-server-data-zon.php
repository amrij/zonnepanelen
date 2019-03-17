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
// versie: 1.22
// auteur: André Rijkeboer
// datum:  17-03-2019
// omschrijving: ophalen van de tekstgegevens van het zonnepanelensysteem

$d1 = $_GET['date'];
if($d1 == ''){ $d1 = date("d-m-Y H:i:s", time()); }
$d3 = date("Y-m-d", strtotime($d1));
$date = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($d1)))))->getTimestamp();
$op_id = array();
$total = array();
$mode = array();
$diff = array();
// mode inverter
$mode[0] = '';
$mode[1] = 'OFF';
$mode[2] = 'SLEEPING';
$mode[3] = 'STARTING';
$mode[4] = 'MPPT';
$mode[5] = 'THROTTLED';
$mode[6] = 'SHUTTING_DOWN';
$mode[7] = '';
$mode[8] = 'STANDBY';
$mode[9] = '';
//open MySQL database
include('config.php');
$mysqli = new mysqli($host, $user, $passwd, $db, $port);
if ($aantal > 33) { $aantal = 33;}
if ($aantal < 0) { $aantal = 0;}
// bepaal de eerste dag van de data in de database
$query = sprintf("SELECT timestamp FROM telemetry_optimizers LIMIT 1");
$result = $mysqli->query($query);
$row = mysqli_fetch_assoc($result);
$begin = gmdate("Y-m-d",$row['timestamp']);
// haal gegevens van de panelen op
If ($d3 >= $begin) {
	$query = sprintf("SELECT HEX(op_id) optimizer, SUM(de_day*0.25) energy
		FROM (
		SELECT
		op_id,
		IF(op_id = @prevop AND uptime > @prevup, e_day - @prevval, e_day) de_day,
			@prevval := e_day,
			@prevup := uptime,
			@prevop := op_id
			FROM telemetry_optimizers
			JOIN (SELECT @prevval := 0, @prevup := 0, @prevop := 0) vars
			WHERE timestamp > %s AND timestamp <= %s
			ORDER BY op_id, timestamp
		) x
		GROUP BY op_id;", $date, $tomorrow);
	$result = $mysqli->query($query);
	$max =0;
	for ($i = 1; $i <= $aantal; $i++){
		$diff[sprintf('O%s',$i)]	= 0;
		$diff[sprintf('C%s',$i)]	= 0;
		$diff[sprintf('TM%s',$i)]	= 0;
		$diff[sprintf('VI%s',$i)]	= 0;
		$diff[sprintf('VU%s',$i)]	= 0;
		$diff[sprintf('S%s',$i)]	= 0;
		$diff[sprintf('T%s',$i)]	= 0;
		$diff[sprintf('E%s',$i)]	= 0;
		$diff[sprintf('VM%s',$i)]	= 0;
		$diff[sprintf('VMT%s',$i)]	= 0;
	}

	//Zet de waarden bij het juiste paneel
	while ($row = mysqli_fetch_assoc($result)) {
		for ($i = 1; $i <= $aantal; $i++){
			if ($row['optimizer'] == $op_id[$i][0]) {
				$diff[sprintf('O%s',$i)]	= round($row['energy'],2);
				if ( $max < round($row['energy'],2)){
					$max = round($row['energy'],2);
				}
			}
		}
	}

	if ($max >0){
		for ($i = 1; $i <= $aantal; $i++){
			$diff[sprintf('C%s',$i)]	= round($diff[sprintf('O%s',$i)]/$max,2);
		}
	}
	$format='%d-%m-%Y %H:%i:%s';
	$query = sprintf("SELECT HEX(op_id) optimizer, FROM_UNIXTIME(timestamp, '%s') time, v_in, v_out, i_in, temperature, max(v_in*i_in) v_m
			FROM telemetry_optimizers
			WHERE timestamp > %s AND timestamp < %s
			GROUP BY HEX(op_id)
			ORDER BY timestamp DESC;",
			$format, $date, $tomorrow);
	$result = $mysqli->query($query);

	while ($row = mysqli_fetch_assoc($result)) {
		for ($i = 1; $i <= $aantal; $i++){
			if ($row['optimizer'] == $op_id[$i][0]) {
				$diff[sprintf('TM%s',$i)]	= $row['time'];
				$diff[sprintf('VI%s',$i)]	= round($row['v_in']*0.125,2);
				$diff[sprintf('VU%s',$i)]	= round($row['v_out']*0.125,2);
				$diff[sprintf('S%s',$i)]	= round($row['i_in']*0.00625,2);
				$diff[sprintf('T%s',$i)]	= round($row['temperature']*2,2);
				$diff[sprintf('E%s',$i)]	= round($row['v_in']*0.125*$row['i_in']*0.00625,2);
				$diff[sprintf('VM%s',$i)]	= round($row['v_m']*0.125*0.00625,2);
			}
		}
	}
	$formatt='%H:%i:%s';
	for ($i = 1; $i <= $aantal; $i++){
		$query = sprintf("SELECT FROM_UNIXTIME(timestamp, '%s') time
				FROM telemetry_optimizers
				WHERE (timestamp > %s AND timestamp < %s)
				  and (%s = round(v_in*i_in*0.125*0.00625,2))
				  and (HEX(op_id) = '%s');",
				$formatt, $date, $tomorrow, $diff[sprintf('VM%s',$i)], $op_id[$i][0]);
		$result = $mysqli->query($query);
		$row = mysqli_fetch_assoc($result);
		$diff[sprintf('VMT%s',$i)]	= $row['time'];
	}
	$format1 = '%Y%m%d';
	if ($inverter == 1){
		// haal de gegevens van de enkel fase inverter op
		$query = sprintf("SELECT datum, MIN(temperature) t_min, MAX(temperature) t_max, temperature t_act, p_active p_act,
				MAX(p_active) p_max, MAX(se_day) e_day, mode, v_ac, i_ac, frequency, v_dc
			FROM (
				SELECT temperature, mode, v_ac, i_ac, frequency, v_dc, p_active, FROM_UNIXTIME(timestamp,'%s') datum,
					@curdate := FROM_UNIXTIME(timestamp, '%s') date,
					@prevsum := IF(@prevdate = @curdate, @prevsum + de_day, de_day) se_day,
					@prevdate := @curdate date2
				FROM telemetry_inverter
				JOIN (SELECT @prevsum := 0, @curdate := NULL, @prevdate := NULL) vars
				WHERE timestamp BETWEEN %s AND %s ORDER BY timestamp DESC
			) x
			GROUP BY date", $format, $format1, $date, $tomorrow);
	}else{
		// haal de gegevens van de 3 fase inverter op
		$query = sprintf("SELECT datum, MIN(temperature) t_min, MAX(temperature) t_max, temperature t_act, p_active p_act,
				MAX(p_active) p_max, MAX(se_day) e_day, mode, v_ac1, v_ac2, v_ac3, i_ac1, i_ac2, i_ac3, frequency1, frequency2, frequency3,
				v_dc,p_active1, p_active2, p_active3
			FROM (
				SELECT temperature, mode,v_ac1, v_ac2, v_ac3, i_ac1, i_ac2, i_ac3, frequency1, frequency2, frequency3, v_dc,
					p_active1, p_active2, p_active3, (p_active1+p_active2+p_active3) p_active,
					FROM_UNIXTIME(timestamp,'%s') datum,
					@curdate := FROM_UNIXTIME(timestamp, '%s') date,
					@prevsum := IF(@prevdate = @curdate, @prevsum + de_day, de_day) se_day,
					@prevdate := @curdate date2
				FROM telemetry_inverter_3phase
				JOIN (SELECT @prevsum := 0, @curdate := NULL, @prevdate := NULL) vars
				WHERE timestamp BETWEEN %s AND %s ORDER BY timestamp DESC
			) x
			GROUP BY date", $format, $format1, $date, $tomorrow);
	}
	$result = $mysqli->query($query);
	$row = mysqli_fetch_assoc($result);
	$diff['IT']	= $row['datum'];
	$diff['ITMIN']	= round($row['t_min'],1);
	$diff['ITMAX']	= round($row['t_max'],1);
	$diff['ITACT']	= round($row['t_act'],1);
	$diff['IVACT']	= round($row['p_act'],0);
	$diff['IVMAX']	= round($row['p_max'],0);
	$diff['IE']	= round($row['e_day']/1000,3);
	$diff['MODE'] = $mode[$row['mode']];
	if ($inverter == 1){
		$diff['v_ac']	= round($row['v_ac'],1);
		$diff['i_ac']	= round($row['i_ac'],3);
		$diff['frequency']	= round($row['frequency'],2);
		$diff['v_dc']	= round($row['v_dc'],3);
		$diff['p_active']	= round($row['p_act'],0);
	}else{
		$diff['v_ac1']	= round($row['v_ac1'],1);
		$diff['v_ac2']	= round($row['v_ac2'],1);
		$diff['v_ac3']	= round($row['v_ac3'],1);
		$diff['i_ac1']	= round($row['i_ac1'],3);
		$diff['i_ac2']	= round($row['i_ac2'],3);
		$diff['i_ac3']	= round($row['i_ac3'],3);
		$diff['frequency1']	= round($row['frequency1'],2);
		$diff['frequency2']	= round($row['frequency2'],2);
		$diff['frequency3']	= round($row['frequency3'],2);
		$diff['v_dc']	= round($row['v_dc'],3);
		$diff['p_active1']	= round($row['p_active1'],0);
		$diff['p_active2']	= round($row['p_active2'],0);
		$diff['p_active3']	= round($row['p_active3'],0);
	}
}else{
	for ($i = 1; $i <= $aantal; $i++){
		$diff[sprintf('O%s',$i)]	= 0;
		$diff[sprintf('C%s',$i)]	= 0;
		$diff[sprintf('TM%s',$i)]	= 0;
		$diff[sprintf('VI%s',$i)]	= 0;
		$diff[sprintf('VU%s',$i)]	= 0;
		$diff[sprintf('S%s',$i)]	= 0;
		$diff[sprintf('T%s',$i)]	= 0;
		$diff[sprintf('E%s',$i)]	= 0;
	}
	$diff['IT']	= $d3;
	$diff['ITMIN']	= 0;
	$diff['ITMAX']	= 0;
	$diff['ITACT']	= 0;
	$diff['IVACT']	= 0;
	$diff['IVMAX']	= 0;
	$diff['IE']	= 0;
	$diff['MODE'] = 0;
}
	
//voeg het resultaat toe aan de total-array
array_push($total, $diff);

// Sluit DB	
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();
//Output totale resultaat als JSON
echo json_encode($total);
?>