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
// versie: 1.02
// auteur: André Rijkeboer
// datum:  20-04-2018
// omschrijving: berekenen maanstand
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	include('general_functions.php');
}
$date = $_GET['date'];
if($date == ''){
	$date = date("d-m-Y H:i:s", time());
}
$date3 = date("Y-m-d", time());
$datum1 = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", time()))))->getTimestamp();
$a = strptime($date, '%d-%m-%Y %H:%M:%S');
if ($a['tm_year']+1900 < 2000){
	$dl = strlen($date);
	$a = strptime(substr($date,4,$dl-35), '%b %d %Y %H:%M:%S');
	$d = mktime($a['tm_hour'],$a['tm_min'],$a['tm_sec'],$a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
	$date = strftime('%d-%m-%Y %H:%M:%S', $d);
}

include('MoonPhase.php'); // ophalen routines (Moon phase calculation class)
include('config.php');
$offset = date('Z',strtotime($date))/3600;    // verschil tussen GMT en locale tijd in uren
$total = array();
$zenith=90+280/600;
$moon = new Solaris\MoonPhase(strtotime($date));
$diff['date3'] = $date3;
$diff['datum1'] = $datum1;
$diff['age'] = $moon->age();
$diff['distance'] = round( $moon->distance(), 2 );
$diff['next'] = date( 'H:i:s, d-m-Y', $moon->next_new_moon() );
$diff['phase'] = round($moon->phase(),2);
$diff['phase_naam'] = $moon->phase_name();
$diff['diameter'] = $moon->diameter();
$diff['phase_naam'] = "Volle maan";
$diff['illumination'] = round($moon->illumination()*100,0);
if ($diff["illumination"] < 3){
	$diff['phase_naam'] = "Nieuwe maan";
}elseif ($diff["illumination"] < 50 && $diff['phase'] < 0.5){
	$diff['phase_naam'] = "Jonge maan";
}elseif ($diff["illumination"] < 53 && $diff['phase'] > 0.5){
	$diff['phase_naam'] = "Asgrauwe maan";
}elseif ($diff["illumination"] < 53 && $diff['phase'] < 0.5){
	$diff['phase_naam'] = "Eerste kwartier";
}elseif ($diff["illumination"] < 50 && $diff['phase'] > 0.5){
	$diff['phase_naam'] = "Laatste kwartier";
}elseif ($diff["illumination"] < 100 && $diff['phase'] < 0.5){
	$diff['phase_naam'] = "Wassende maan";
}elseif ($diff["illumination"] < 97 && $diff['phase'] > 0.5){
	$diff['phase_naam'] = "Afnemende maan";
}
if ( $diff["phase"] < 0.5 ){
	$diff['filenaam'] = sprintf('./img/maan/maan_th_-0_%s.jpg',$diff["illumination"]);
} else{
	$diff['filenaam'] = sprintf('./img/maan/maan_th_0_%s.jpg',$diff["illumination"]);
}

//voeg het resultaat toe aan de total-array
array_push($total, $diff);

//Output total results as JSON
echo json_encode($total);
?>
