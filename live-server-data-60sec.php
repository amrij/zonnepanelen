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
// versie: 1.70.1
// auteur: André Rijkeboer
// datum:  21-07-2019
// omschrijving: ophalen van de gegevens van de panelen, de inverter en van astronomische gegevens

# ophalen algemene gegevens
include('config.php');

# openen van de database
$mysqli = new mysqli($host, $user, $passwd, $db, $port);

# ophale datum gegevens
$d1 = array_key_exists('date', $_GET) ? $_GET['date'] : "";
if ($d1 == '') { $d1 = date("d-m-Y H:i:s", time()); }

# ophalen periode gegevens
$period = array_key_exists('period', $_GET) ? $_GET['period'] : "s";
$limit = $period == "s" ? "" : " limit 1";

# zet datum gegevens
$midnight = date("Y-m-d 00:00:00", strtotime($d1));
$today    = (new DateTime("today " . $midnight))->getTimestamp();
$tomorrow = (new DateTime("tomorrow " . $midnight))->getTimestamp();

#zet de juiste inverter type
$table = $inverter == 1 ? "telemetry_inverter" : "telemetry_inverter_3phase";

# bepaal de eerste dag van de data in de database
$query = "SELECT min(timestamp) as timestamp FROM telemetry_optimizers";
$result = $mysqli->query($query);
$row = mysqli_fetch_assoc($result);
$begin = gmdate("Y-m-d 00:00:00", $row['timestamp']);

# reset $total voor overdracht gegevens naar zonnepanelen.php
$total = array();

####################
# invertergegevens #
# case "in":       #
####################
$dag = [gmdate("d", $today-($InvDays-1)*86400), $InvDays];
$date_i = $today-$InvDays*86400;

# zet de totale energie per dag op 0
$de_day_total = 0;

$sqlcols = $inverter == 1 ? 'v_ac, i_ac, v_dc, p_active'
			  : '(v_ac1+v_ac2+v_ac3)/3 as v_ac, (i_ac1+i_ac2+i_ac3) as i_ac, v_dc, (p_active1+p_active2+p_active3) as p_active';

# reset difference array voor opslag in record van $total
$diff = array();

# haal de gegevens van de inverter op uit de database 
$diff['ca']  = "in";
$query = 'SELECT timestamp, IF(temperature = 0, NULL, temperature) temperature, de_day, ' . $sqlcols .
	' FROM ' . $table .
	' WHERE e_day>0 AND timestamp BETWEEN ' . $date_i . ' AND ' . $tomorrow .
	' ORDER BY timestamp';
foreach ($mysqli->query($query) as $j => $row) {
	while ($dag[0] != gmdate("d", $row['timestamp'])) {
		$dag[1]--;
		$dag[0] = gmdate("d", $today-($dag[1]-1)*86400);
		$de_day_total = 0;
	}
	$de_day_total += $row["de_day"];
	$diff['serie']  = $dag[1];
	$diff['ts']    = ($today + date("H", $row['timestamp']) * 3600 + round(0.2 * date("i", $row['timestamp'])) * 5 * 60) * 1000;
	$diff['vp'] = sprintf("%.3f", $de_day_total/1000);
	$diff['cp'] = $row['p_active'];
	# voeg het resultaat toe aan de total-array
	array_push($total, $diff);
}

##################
# paneelgegevens #
# case "pa":     #
##################

# reset difference array voor opslag in record van $total en paneel array voor paneelgegevens
$diff = array();
$paneel = array();

# zet voor alle paneelgegevens de waarde op 0
if ($aantal < 0) { $aantal = 0; }
for ($i = 1; $i <= $aantal; $i++) {
	$paneel[$i]['uptime'] = 0;
	$paneel[$i]['energie'] = 0;
	$paneel[$i]['verschil'] = 0;
}

# haal gegevens van de panelen op
$query = sprintf("SELECT HEX(op_id) optimizer, timestamp, uptime, v_in*i_in*0.125*0.00625 as vermogen, e_day*0.25 as energie, temperature
		FROM telemetry_optimizers
		WHERE timestamp > %s AND timestamp <= %s
		ORDER BY timestamp;",
		$today, $tomorrow);
$result = $mysqli->query($query);

# Zet de waarden bij het juiste paneel
$diff['ca']  = "pa";
while ($row = mysqli_fetch_assoc($result)) {
	for ($i = 1; $i <= $aantal; $i++) {
		if ($row['optimizer'] == $op_id[$i][0]) {
			$diff['serie']  = 0;
			$diff['op_id']  = $i;
			$diff['ts'] = $row['timestamp'] * 1000;
			$diff['temp'] = $row['temperature']*2;
			$diff['cp'] = sprintf("%.3f", $row['vermogen']);

			if ($paneel[$i]['uptime'] > $row['uptime']) {
				$paneel[$i]['verschil'] = $paneel[$i]['energie'];
			}
			$diff['vp'] = sprintf("%.3f", $row['energie'] + $paneel[$i]['verschil']);
			$paneel[$i]['energie'] = $diff['vp'];
			$paneel[$i]['uptime'] = $row['uptime'];
			# voeg het resultaat toe aan de total-array
			array_push($total, $diff);
		}
	}
}

############################
# inverter en paneel tekst #
# case "ip":               #
############################

# reset difference array voor opslag in record van $total en mode array voor invertergegevens
$diff = array();

$diff['ca'] = "ip";
# zet gegevens van de panelen op 0 voor het geval dat
# midnight < begin of de sql query faalt
for ($i = 1; $i <= $aantal; $i++) {
	$diff['O' . $i]	= 0;
	$diff['C' . $i]	= 0;
	$diff['TM' . $i]	= 0;
	$diff['VI' . $i]	= 0;
	$diff['VU' . $i]	= 0;
	$diff['S' . $i]	= 0;
	$diff['T' . $i]	= 0;
	$diff['E' . $i]	= 0;
	$diff['VM' . $i]	= 0;
	$diff['VMT' . $i]	= "    00:00:00";
}
$diff['IT']	= $d1;
$diff['ITMIN']	= 0;
$diff['ITMAX']	= 0;
$diff['ITACT']	= 0;
$diff['IVACT']	= 0;
$diff['IVMAX']	= 0;
$diff['IE']	= 0;
$diff['MODE']	= '';
$diff['v_dc']	= 0;

If ($midnight >= $begin) {
	# loop door alle records en maak berekeningen in php.
	$format = '%d-%m-%Y %H:%i:%s';
	# haal gegevens van de panelen op
	$query = "SELECT HEX(op_id) optimizer, FROM_UNIXTIME(timestamp, '" . $format . "') time, v_in, v_out, i_in, temperature, uptime, e_day" .
		" FROM telemetry_optimizers" .
		" WHERE timestamp > " . $today . " AND timestamp < " . $tomorrow . 
		" order BY HEX(op_id), timestamp";
	$result = $mysqli->query($query);
	if ($result) {
		$prev_id = 0;
		$prev_uptime = 0;
		$prev_e_day = 0;
		$max = 0;
		while ($row = mysqli_fetch_assoc($result)) {
			# zet de waarde bij het juiste paneel
			for ($i = 1; $i <= $aantal; $i++){
				if ($row['optimizer'] == $op_id[$i][0]) {
					$diff['O' . $i] += ($i == $prev_id and $row['uptime'] > $prev_uptime)   ? $row['e_day'] - $prev_e_day
														: $row['e_day'];
					if ($max < $diff['O' . $i]){
						$max = $diff['O' . $i];
					}
					$prev_id = $i;
					$prev_uptime = $row['uptime'];
					$prev_e_day = $row['e_day'];
					$diff['TM' . $i]	= $row['time'];
					$diff['VI' . $i]	= $row['v_in'];
					$diff['VU' . $i]	= $row['v_out'];
					$diff['S' . $i]	= $row['i_in'];
					$diff['T' . $i]	= $row['temperature'];
					$v_m = $row['v_in']*$row['i_in'];
					if ($v_m > $diff['VM' . $i]) {
						$diff['VM' . $i]	= $v_m;
						$diff['VMT' . $i]	= $row['time'];
					}
				}
			}
		}
		# omzetten naar juiste waarden
		if ($max == 0) {$max = 1;}
		for ($i = 1; $i <= $aantal; $i++) {
			$diff['C' . $i]   = sprintf("%.2f", $diff['O' . $i]/$max);
			$diff['O' . $i]   = sprintf("%.2f", $diff['O' . $i] * 0.25);
			$diff['VI' . $i]  = sprintf("%.2f", $diff['VI' . $i] * 0.125);
			$diff['VU' . $i]  = sprintf("%.2f", $diff['VU' . $i] * 0.125);
			$diff['S' . $i]   = sprintf("%.2f", $diff['S' . $i] * 0.00625);
			$diff['T' . $i]  *= 2;
			$diff['E' . $i]   = sprintf("%.2f", $diff['VI' . $i] * $diff['S' . $i]);
			$diff['VM' . $i]  = sprintf("%.2f", $diff['VM' . $i]*0.125*0.00625);
			$diff['VMT' . $i] = substr($diff['VMT' . $i], 11);
		}
	}



	# zet mode status inverter
	$mode = array();
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

	# zet de juiste inverter type
	$cols = $inverter == 1 ? "p_active" : "p_active1+p_active2+p_active3";

	# Verzamel min / max van de dag
	$query = "SELECT MIN(temperature) t_min, MAX(temperature) t_max, MAX(" . $cols . ") p_max, max(e_total)-min(e_total) + " .
		" (select de_day FROM  " . $table . " WHERE `timestamp` > " . $today . " AND timestamp < " . $tomorrow .
		" ORDER BY `timestamp` DESC LIMIT 1) e_day" .
		" FROM " . $table .
		" WHERE timestamp > " . $today . " AND timestamp < " . $tomorrow;
	$result = $mysqli->query($query);

	# zet de waarden in de record
	if ($result) {
		$row = mysqli_fetch_assoc($result);
		$diff['ITMIN']	= sprintf("%.1f", $row['t_min']);
		$diff['ITMAX']	= sprintf("%.1f", $row['t_max']);
		$diff['IVMAX']	= sprintf("%.0f", $row['p_max']);
		$diff['IE']	= sprintf("%.3f", $row['e_day']/1000);
	}

	# zet de juiste inverter type
	$cols = $inverter == 1 ? "v_ac, i_ac, frequency, p_active"
				: "v_ac1, v_ac2, v_ac3, i_ac1, i_ac2, i_ac3, frequency1, frequency2, frequency3, p_active1, p_active2, p_active3, p_active1+p_active2+p_active3 p_active";

	# Verzamel de laaste of de huidge waarden van de dag
	$query = "SELECT FROM_UNIXTIME(timestamp, '" . $format . "') datum, temperature t_act, mode, FORMAT(v_dc,3) as v_dc, " .
			$cols .
		" FROM " . $table .
		" WHERE timestamp BETWEEN " . $today . " AND " . $tomorrow . " ORDER BY timestamp DESC limit 1";
	$result = $mysqli->query($query);

	# zet de waarden in het record
	if ($result) {
		$row = mysqli_fetch_assoc($result);

		$diff['IT']	= $row['datum'];
		$diff['ITACT']	= sprintf("%.1f", $row['t_act']);
		$diff['IVACT']	= sprintf("%.0f", $row['p_active']);
		$diff['MODE']	= $mode[$row['mode']];
		$diff['v_dc']	= sprintf("%.3f", $row['v_dc']);
		if ($inverter == 1) {
			$diff['v_ac']		= sprintf("%.1f", $row['v_ac']);
			$diff['i_ac']		= sprintf("%.3f", $row['i_ac']);
			$diff['frequency']	= sprintf("%.2f", $row['frequency']);
			$diff['p_active']	= sprintf("%.0f", $row['p_active']);
		}else{
			$diff['v_ac1']		= sprintf("%.1f", $row['v_ac1']);
			$diff['v_ac2']		= sprintf("%.1f", $row['v_ac2']);
			$diff['v_ac3']		= sprintf("%.1f", $row['v_ac3']);
			$diff['i_ac1']		= sprintf("%.3f", $row['i_ac1']);
			$diff['i_ac2']		= sprintf("%.3f", $row['i_ac2']);
			$diff['i_ac3']		= sprintf("%.3f", $row['i_ac3']);
			$diff['frequency1']	= sprintf("%.2f", $row['frequency1']);
			$diff['frequency2']	= sprintf("%.2f", $row['frequency2']);
			$diff['frequency3']	= sprintf("%.2f", $row['frequency3']);
			$diff['p_active1']	= sprintf("%.0f", $row['p_active1']);
			$diff['p_active2']	= sprintf("%.0f", $row['p_active2']);
			$diff['p_active3']	= sprintf("%.0f", $row['p_active3']);
		}
	}
}

# voeg het resultaat toe aan de total-array
array_push($total, $diff);

################
# powergrafiek #
# case "po":   #
################

$diff = array();

# enkel of drie fase inverter
$cols  = $inverter == 1 ? 'p_active'           : '(p_active1+p_active2+p_active3) as p_active';

# haal de gegevens op
$de_day_total = 0;
$diff['ca']   = 'po';
foreach ($mysqli->query(
		'SELECT timestamp, de_day, ' . $cols .
		' FROM ' . $table .
		' WHERE timestamp BETWEEN ' . $today . ' AND ' . $tomorrow .
		' ORDER BY timestamp' .
		$limit )
		as $j => $row) {
	$de_day_total += $row["de_day"];
	$diff['ts']   = $row['timestamp'] * 1000;
	$diff['vp'] = sprintf("%.3f", $de_day_total/1000);
	$diff['cp'] = $row['p_active'];
	// voeg het resultaat toe aan de total-array
	array_push($total, $diff);
}

##########################
# astronomische gegevens #
# case "mf":             #
##########################

# ophalen routines (Moon phase calculation class)
include('MoonPhase.php'); 

$diff = array();

# zet datum gegevens
$date1 = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", time()))))->getTimestamp();
if (date('d-m-Y', strtotime($d1)) == date("d-m-Y", time())) {
	$date2 = date("d-m-Y H:i:s", time());
}else{
	$date2 = date('d-m-Y H:i:s', strtotime($d1));
}
$today = strtotime($date2);
$date3 = date("Y-m-d", time());

$moon = new Solaris\MoonPhase(strtotime($date2));
$diff['ca'] = 'mf';
$diff['d1'] = $date1;
$diff['d2'] = $date2;
$diff['d3'] = $date3;
$diff['de'] = round( $moon->distance(), 0 );
$diff['sde'] = round( $moon->sundistance(), 0 );
$diff['nm'] = date( 'd-m-Y H:i:s', $moon->new_moon() );
$diff['fq'] = date( 'd-m-Y H:i:s', $moon->first_quarter() );
$diff['fm'] = date( 'd-m-Y H:i:s', $moon->full_moon() );
$diff['lq'] = date( 'd-m-Y H:i:s', $moon->last_quarter() );
$diff['nnm'] = date( 'd-m-Y H:i:s', $moon->next_new_moon() );
$diff['nfq'] = date( 'd-m-Y H:i:s', $moon->next_first_quarter() );
$diff['nfm'] = date( 'd-m-Y H:i:s', $moon->next_full_moon() );
$diff['nlq'] = date( 'd-m-Y H:i:s', $moon->next_last_quarter() );
$diff['ta'] = round(($moon->next_new_moon() - $moon->new_moon())/86400,2);
$diff['ae'] = round(($today - $moon->new_moon())/86400,2);
$diff['pe'] = round($moon->phase(),4);
if     ($diff["pe"] >  .975 || $diff["pe"] <  .025) { $diff['pm'] = "Nieuwe maan"; }
elseif ($diff["pe"] >= .025 && $diff['pe'] <= .225) { $diff['pm'] = "Jonge maan"; }
elseif ($diff["pe"] >  .225 && $diff['pe'] <  .275) { $diff['pm'] = "Eerste kwartier"; }
elseif ($diff["pe"] >= .275 && $diff['pe'] <= .475) { $diff['pm'] = "Wassende maan"; }
elseif ($diff["pe"] >  .475 && $diff['pe'] <  .525) { $diff['pm'] = "Volle maan"; }
elseif ($diff["pe"] >= .525 && $diff['pe'] <= .725) { $diff['pm'] = "Afnemende maan"; }
elseif ($diff["pe"] >  .725 && $diff['pe'] <  .775) { $diff['pm'] = "Laatste kwartier"; }
elseif ($diff["pe"] >= .775 && $diff['pe'] <  .975) { $diff['pm'] = "Asgrauwe maan"; }
$diff['dr'] = floor($moon->diameter()*60). "'" . floor(($moon->diameter()*60 -floor($moon->diameter()*60))*60). "''";
$diff['sdr'] = floor($moon->sundiameter()*60). "'" . floor(($moon->sundiameter()*60 -floor($moon->sundiameter()*60))*60). "''";
$diff['in'] = round($moon->illumination()*100,0);
$diff['fn'] = sprintf('./img/maan/phase_%003d.png',round($moon->phase()*360,0));

# voeg het resultaat toe aan de total-array
array_push($total, $diff);

# Sluit DB	
$thread_id = $mysqli->thread_id;
$mysqli->kill($thread_id);
$mysqli->close();

# Output totaal resultaat als JSON
echo json_encode($total);
?>
