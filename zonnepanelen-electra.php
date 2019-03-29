<!--
#
# Copyright (C) 2019 André Rijkeboer
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

based on versie: 1.48 of zonnepanelen.php
versie: 1.48.3
auteur: Jos van der Zande  based on the zonnepanelen.php model from André Rijkeboer
datum:  24-03-2019
omschrijving: hoofdprogramma
-->
<html>
<head>
	<title>Zonnepanelen</title>
	<link rel="shortcut icon" href="./img/sun.ico" type="image/x-icon"  />
	<script type="text/javascript" src="js/loader.js"></script>
	<script type="text/javascript" src="js/highcharts.js"></script>
	<script type="text/javascript" src="js/highcharts-more.js"></script>
	<script type="text/javascript" src="js/exporting.js"></script>
	<script type="text/javascript" src="js/data.js"></script>
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"></script>
		<meta name="description" content="">
	<meta name="viewport" content="width=device-width">
	<link rel="stylesheet" href="css/jquery.calendars.picker.css" id="theme">
	<link rel="stylesheet" href="css/app.css">
	<link href='css/zonnepanelen-electra.css' rel='stylesheet' type='text/css'/>
	<script src="js/jquery.plugin.js"></script>
	<script src="js/jquery.mousewheel.js"></script>
	<script src="js/jquery.calendars.js"></script>
	<script src="js/jquery.calendars.plus.js"></script>
	<script src="js/jquery.calendars.picker.js"></script>
	<script src="js/jquery.calendars.picker.ext.js"></script>
	<script src="js/jquery.calendars.validation.js"></script>
	<script language="javascript" type="text/javascript">
		function toonDatum(datum) {
			url = '<?php echo $_SERVER[PATH_INFO]?>?date='
			url = url + datum
			url = url + ' 00:00:00'
			window.location.replace(url);//do something after you receive the result
		}
	</script>
	<?php
		include('config.php');
		if ($aantal > 33) { $aantal = 33;}
		if ($aantal < 0) { $aantal = 0;}
		$mysqli = new mysqli($host, $user, $passwd, $db, $port);
		$query = sprintf("SELECT `timestamp` FROM `telemetry_optimizers` LIMIT 1");
		$result = $mysqli->query($query);
		$row = mysqli_fetch_assoc($result);
		$begin = gmdate("Y-m-d",$row['timestamp']);
		$thread_id = $mysqli->thread_id;
		$mysqli->kill($thread_id);
		$mysqli->close();
		$week[1] = "Maandag ";
		$week[2] = "Dinsdag ";
		$week[3] = "Woensdag ";
		$week[4] = "Donderdag ";
		$week[5] = "Vrijdag ";
		$week[6] = "Zaterdag ";
		$week[7] = "Zondag ";
		$date = $_GET['date'];
		setlocale(LC_ALL, 'nld_NLD');
		if($date == ''){
			$date = date("d-m-Y H:i:s", time());
		}
		for ($i=0; $i<=14; $i++){
			$productie[$i] = $week[date("N", strtotime($date)-$i*86400)].date("d-m-Y", strtotime($date)-$i*86400);
		}
		$winter = date("I",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp())-1;
		$jaar = date("Y",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp());
		$maand = date("m",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp())-1;
		$dag = date("d",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp())-1;
		$datum1 = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", time()))))->getTimestamp();
		$datumz = date("d-m-Y H:i:s",(new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp());
		$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp();
		$date3 = date("Y-m-d", time());
		$datev = date("d-m-Y", strtotime($date));
		$a = strptime($date, '%d-%m-%Y %H:%M:%S');
		if ($a['tm_year']+1900 < 2000){
			$a = strptime($date, '%Y-%m-%d');
			$d = mktime(0,0,0,$a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
		}
		$a = mktime(0,0,0,$a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
		$date2 = strftime('%Y-%m-%d', $a);
		$date4 = strftime('%Y,%m,%d', $d);
		$datum = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp()/86400;
		$timezone = date('Z',strtotime($date))/3600;
		$localtime = 0; //Time (pas local midnight)
		$sunrise_s = iteratie($datum,$lat,$long,$timezone,$localtime,0);
		$solar_noon_s = iteratie($datum,$lat,$long,$timezone,$localtime,1);
		$sunset_s = iteratie($datum,$lat,$long,$timezone,$localtime,2);
		$sunrise = date("H:i:s",($datum+$sunrise_s)*86400);
		$solar_noon = date("H:i:s",($datum+$solar_noon_s)*86400);
		$sunset = date("H:i:s",($datum+$sunset_s)*86400);
		$daglengte = date("H:i:s",($datum+$sunset_s-$sunrise_s)*86400);

		function iteratie($datum,$lat,$long,$timezone,$localtime,$i) {
				$epsilon = 0.000000000001;
				do {
					$st = $solar_noon_s = bereken($datum,$lat,$long,$timezone,$localtime,$i);
					$sv = $st - $localtime/24;
					$localtime = $st*24;
				}
				while ( abs($sv) > $epsilon );
				return $st;
			}

		function bereken($datum,$lat,$long,$timezone,$localtime,$i) {
			$julian_day = $datum + 2440587.5 + ($localtime-$timezone)/24; //Julian Day
			$julian_cen =($julian_day-2451545)/36525; //Julian Century
			$geom_mean_long_sun = ((280.46646+$julian_cen*(36000.76983 + $julian_cen*0.0003032))/360 - floor((280.46646+$julian_cen*(36000.76983 + $julian_cen*0.0003032))/360))*360; //Geom Mean Long Sun (deg)
			$geom_mean_anom_sun = 357.52911+$julian_cen*(35999.05029 - 0.0001537*$julian_cen); //Geom Mean Anom Sun (deg)
			$eccent_earth_orbit = 0.016708634-$julian_cen*(0.000042037+0.0000001267*$julian_cen); //Eccent Earth Orbit
			$sun_eq_of_ctr = sin(deg2rad($geom_mean_anom_sun))*(1.914602-$julian_cen*(0.004817+0.000014*$julian_cen))+sin(deg2rad(2*$geom_mean_anom_sun))*(0.019993-0.000101*$julian_cen)+sin(deg2rad(3*$geom_mean_anom_sun))*0.000289; //Sun Eq of Ctr
			$sun_true_long = $geom_mean_long_sun+$sun_eq_of_ctr; //Sun True Long (deg)
			$sun_app_long = $sun_true_long-0.00569-0.00478*sin(deg2rad(125.04-1934.136*$julian_cen)); //Sun App Long (deg)
			$mean_obliq_ecliptic = 23+(26+((21.448-$julian_cen*(46.815+$julian_cen*(0.00059-$julian_cen*0.001813))))/60)/60; //Mean Obliq Ecliptic (deg)
			$obliq_corr = $mean_obliq_ecliptic+0.00256*cos(deg2rad(125.04-1934.136*$julian_cen)); // Obliq Corr (deg)
			$sun_declin = rad2deg(asin(sin(deg2rad($obliq_corr))*sin(deg2rad($sun_app_long)))); //Sun Declin (deg)
			$var_y = tan(deg2rad($obliq_corr/2))*tan(deg2rad($obliq_corr/2)); //var y
			$eq_of_time = 4*rad2deg($var_y*sin(2*deg2rad($geom_mean_long_sun))-2*$eccent_earth_orbit*sin(deg2rad($geom_mean_anom_sun))+4*$eccent_earth_orbit*$var_y*sin(deg2rad($geom_mean_anom_sun))*cos(2*deg2rad($geom_mean_long_sun))-0.5*$var_y*$var_y*sin(4*deg2rad($geom_mean_long_sun))-1.25*$eccent_earth_orbit*$eccent_earth_orbit*sin(2*deg2rad($geom_mean_anom_sun))); //Eq of Time
			$ha_sunrise = rad2deg(acos(cos(deg2rad(90.833))/(cos(deg2rad($lat))*cos(deg2rad($sun_declin)))-tan(deg2rad($lat))*tan(deg2rad($sun_declin)))); //HA Sunrise (deg)
			$solar_noon_a = (720-4*$long-$eq_of_time+$timezone*60)/1440; //Solar Noon
			$sunrise_a = $solar_noon_a-$ha_sunrise*4/1440; // Sunrise
			$sunset_a = $solar_noon_a+$ha_sunrise*4/1440; // Sunset
			if ($i==0){ $s = $sunrise_a;}
			if ($i==1){ $s = $solar_noon_a;}
			if ($i==2){ $s = $sunset_a;}
			return $s;
		}
	?>
</head>
<body>
	<div class='mainpage'>
		<div class='container' id='container'>
			<div Class='box_inverter' id='box_inverter'>
				<div class="imageOver">
					<img src="./img/<?php echo $zonnesysteem_electra;?>" alt=""  style="position:absolute; top: 0px; left: 0px; width: 100%; height: 100%; z-index: -100;"/>
				</div>
				<div Class='datum' id='datum' style="top: 0.1; left: 25%; z-index: 3; width: 40%; height: 5%; position: absolute;">
						<TR style="text-align:center"><TD><input type="button" id="PrevDay" class="btn btn-success btn-sm" value="<">
						<input type="text" id="multiShowPicker" class="embed" size="8.5" style="text-align:center;">
						<input type="button" id="NextDay" class="btn btn-success btn-sm"  value=">"></TD><TR>
				</div>

				<img src="./img/dummy.gif" style="top: 10%; left: 4.00%; z-index: 10; width: 18.00%; height: 22.00%; position: absolute;" usemap="#inverter"/>
				<map name="inverter" style="z-index: 20;">
					<area id="inverter_1" shape="rect" coords="0,0,100%,100%" title="">
				</map>
				<div class='inverter_text' id='inverter_text' style="top: 10%; left: 25.5%; z-index: 10; width: 43%; height: 15%; line-height: 120%; position: absolute;"></div>
				<div class='sola_text' id='sola_text' style="top: 40%; left: 3%; width: 30%; height: 15%; line-height: 120%; position: absolute;"></div>
				<div class='elec_text' id='elec_text' style="top: 65%; left: 18%; width: 30%; height: 15%; line-height: 120%; position: absolute;"></div>
				<div class='so_text' id='so_text' style="top: 37.0%; left: 28.0%; width: 15%; height: 5%; line-height: 120%; position: absolute;"></div>
				<div class="" id="arrow_PRD"      style="top: 34.05%; left: 29.0%; width: 0.01%; height: 0.7% ; z-index: 20; position: absolute;"></div>
				<div class='p1_text' id='p1_text' style="top: 82.5%; left: 70.0%; width: 15%; height: 5%; line-height: 120%; position: absolute;"></div>
				<div class=""   id="arrow_RETURN" style="top: 87.35%; left: 75.0%; width: 0.03%; height: 2.1%; z-index: 20; position: absolute;"></div>
				<img src="./img/dummy.gif" style="top: 60%; left: 50%; z-index: 10; width: 3,62%; height: 25%; position: absolute;" usemap="#meter"/>
				<map name="meter" style="z-index: 20;">
					<area id="meter_1" shape="rect" coords="0,0,67,100" title="P1_Meter">
				</map>
				<div class='p1_huis' id='p1_huis' style="top: 30.0%; left: 61.0%; width: 15%; height: 5%; line-height: 120%; position: absolute;"></div>
				<img src="./img/dummy.gif" style="top: 3.5%; left: 65%; z-index: 10; width: 27%; height: 28%; position: absolute;" usemap="#huis"/>
				<map name="huis" style="z-index: 20;">
					<area id="huis_1" shape="rect" coords="0,0,150,150" title="thuis verbruik">
				</map>
			</div>

			<div Class='panel_energy' id='panel_energy'></div>
			<div Class='panel_vermogen' id='panel_vermogen'></div>
			<div Class='chart_energy' id='chart_energy'></div>
			<div Class='chart_vermogen' id='chart_vermogen'></div>
			<div Class='daygraph' id="daygraph"></div>
			<div Class='monthgraph' id="monthgraph"></div>

			<div Class='box_Zonnepanelen' id='box_Zonnepanelen'>
<?php
	for ($i=1; $i<=$aantal; $i++){
		echo '				<div class="box_Zonnepaneel_'.$i.'" id="box_Zonnepaneel_'.$i.'">'."\n";
		echo '					<div class="text_paneel_W" id="text_paneel_W_'.$i.'"></div>'."\n";
		echo '					<div class="text_paneel_WX" id="text_paneel_W_'.$i.'a"></div>'."\n";
		echo '					<img  id="image_'.$i.'" src="./img/dummy.gif" alt="" width="100%" height="100%" style="witdh: 0%; height: 100%; position:relative; z-index: 5;"/></div>'."\n";
		echo '					<div class="box_Zonnepaneel_'.$i.'">'."\n";
		echo '						<img src="./img/dummy.gif" alt="" width="100%" Height="100%" style=" position: relative; z-index: 15;" usemap="#'.$i.'">'."\n";
		echo '						<map name="'.$i.'">'."\n";
		echo '							<area id="tool_paneel_'.$i.'" shape="rect" coords="0,0,100%,100%" title="" onmouseover="paneelChart(event,'.$i.')" onmouseout="paneelChartcl()">'."\n";
		echo '						</map>'."\n";
		echo '					<div class="text_Zonnepaneel_n" id="text_Zonnepaneel_'.$i.'"></div>'."\n";
		echo '				</div>'."\n";
	}
?>
			<div Class='box_sunrise' id='box_sunrise'>
				<img src="./img/zon/sunrise.gif"                  style="top: .1%;   left: 3%;  z-index: 10; width: 20%; height: 12%; position: absolute;" />
				<div class='sunrise_text' id='sunrise_text'       style="top: .5%;   left: 30%; z-index: 10; width: 50%; height: 15%; line-height: 1.1em; position: absolute;"></div>

				<img src="./img/zon/solar_noon.gif"               style="top: 15.1%; left: 3%;  z-index: 10; width: 20%; height: 12%; position: absolute;" />
				<div class='solar_noon_text' id='solar_noon_text' style="top: 15.5%; left: 30%; z-index: 10; width: 50%; height: 15%; line-height: 1.1em; position: absolute;"></div>

				<img src="./img/zon/sunset.gif"                   style="top: 30.1%; left: 3%;  z-index: 10; width: 20%; height: 12%; position: absolute;" />
				<div class='sunset_text' id='sunset_text'         style="top: 30.5%; left: 30%; z-index: 10; width: 50%; height: 15%; line-height: 1.1em; position: absolute;"></div>

				<img src="./img/zon/daglengte.gif"                style="top: 45.1%; left: 3%;  z-index: 10; width: 20%; height: 12%; position: absolute;" />
				<div class='daglengte_text' id='daglengte_text'   style="top: 45.5%; left: 30%; z-index: 10; width: 50%; height: 15%; line-height: 1.1em; position: absolute;"></div>

				<img src="./img/maan/maan_th_mask1.gif"           style="top: 58.0%; left: 80%; z-index: 20; width: 20%; position: absolute;" />
				<img class="maan_th" id="maan_th" src=""          style="top: 59.0%; left: 80%; z-index: 10; width: 20%; position: absolute;"></img>
				<div class='fase_text' id='fase_text'             style="top: 68.0%; left: 30%; z-index: 10; width: 50%; height: 12%; line-height: 1.1em; position: absolute;"></div>
				<div class='verlicht_text' id='verlicht_text'     style="top: 81.0%; left: 30%; z-index: 10; width: 50%; height: 12%; line-height: 1.1em; position: absolute;"></div>
			</div>
		</div>
	</div>
</body>




<script type="text/javascript">
	var datum = '<?php echo $date ?>';
	var datumz = '<?php echo $datumz ?>';
	var datum1 = '<?php echo $datum1 ?>';
	var tomorrow = '<?php echo $tomorrow ?>';
	var date2 = "<?php echo $date2 ?>";
	var date3 = "<?php echo $date3 ?>";
	var datev = "<?php echo $datev ?>";
	var winter = '<?php echo $winter?>';
	var jaar = '<?php echo $jaar?>';
	var maand = '<?php echo $maand?>';
	var sunrise = '<?php echo $sunrise ?>';
	var solar_noon = '<?php echo $solar_noon ?>';
	var sunset = '<?php echo $sunset ?>';
	var daglengte = '<?php echo $daglengte ?>';
	var dag = '<?php echo $dag?>';
	var begin = '<?php echo $begin?>';
	var vermogen = '<?php echo $vermogen?>';
	var inverter = '<?php echo $inverter?>';
	var naam = '<?php echo $naam?>';
	var aantal = '<?php echo $aantal?>';
	var op_id = [0,'<?php echo $op_id[1][1]?>','<?php echo $op_id[2][1]?>','<?php echo $op_id[3][1]?>','<?php echo $op_id[4][1]?>','<?php echo $op_id[5][1]?>','<?php echo $op_id[6][1]?>','<?php echo $op_id[7][1]?>','<?php echo $op_id[8][1]?>','<?php echo $op_id[9][1]?>','<?php echo $op_id[10][1]?>','<?php echo $op_id[11][1]?>','<?php echo $op_id[12][1]?>','<?php echo $op_id[13][1]?>','<?php echo $op_id[14][1]?>','<?php echo $op_id[15][1]?>','<?php echo $op_id[16][1]?>','<?php echo $op_id[17][1]?>','<?php echo $op_id[18][1]?>','<?php echo $op_id[19][1]?>','<?php echo $op_id[20][1]?>','<?php echo $op_id[21][1]?>','<?php echo $op_id[22][1]?>','<?php echo $op_id[23][1]?>','<?php echo $op_id[24][1]?>','<?php echo $op_id[25][1]?>','<?php echo $op_id[26][1]?>','<?php echo $op_id[27][1]?>','<?php echo $op_id[28][1]?>','<?php echo $op_id[29][1]?>','<?php echo $op_id[30][1]?>','<?php echo $op_id[31][1]?>','<?php echo $op_id[32][1]?>','<?php echo $op_id[33][1]?>'];
	var rpan = [0,'<?php echo $op_id[1][2]?>','<?php echo $op_id[2][2]?>','<?php echo $op_id[3][2]?>','<?php echo $op_id[4][2]?>','<?php echo $op_id[5][2]?>','<?php echo $op_id[6][2]?>','<?php echo $op_id[7][2]?>','<?php echo $op_id[8][2]?>','<?php echo $op_id[9][2]?>','<?php echo $op_id[10][2]?>','<?php echo $op_id[11][2]?>','<?php echo $op_id[12][2]?>','<?php echo $op_id[13][2]?>','<?php echo $op_id[14][2]?>','<?php echo $op_id[15][2]?>','<?php echo $op_id[16][2]?>','<?php echo $op_id[17][2]?>','<?php echo $op_id[18][2]?>','<?php echo $op_id[19][2]?>','<?php echo $op_id[20][2]?>','<?php echo $op_id[21][2]?>','<?php echo $op_id[22][2]?>','<?php echo $op_id[23][2]?>','<?php echo $op_id[24][2]?>','<?php echo $op_id[25][2]?>','<?php echo $op_id[26][2]?>','<?php echo $op_id[27][2]?>','<?php echo $op_id[28][2]?>','<?php echo $op_id[29][2]?>','<?php echo $op_id[30][2]?>','<?php echo $op_id[31][2]?>','<?php echo $op_id[32][2]?>','<?php echo $op_id[33][2]?>'];
	var uur0 = '22';
	var uur1 = '23';
	var uur2 = '24';
	var uur3 = '25';
	var uur4 = '26';
	var uur5 = '27';
	var uur6 = '28';
	var uur7 = '29';
	var uur8 = '30';
	var uur9 = '31';
	var uur10 = '32';
	var uur11 = '33';
	var uur12 = '34';
	var uur13 = '35';
	var uur14 = '36';
	var uur15 = '37';
	var uur16 = '38';
	var uur17 = '39';
	var uur18 = '40';
	var uur19 = '41';
	var uur20 = '42';
	var uur21 = '43';
	var uur22 = '44';
	var uur23 = '45';
	var uur24 = '46';
	var uur25 = '47';
	var data_p = [];
	var data_i = [];
	var chart_1 = "chart_energy";
	var chart_2 = "chart_vermogen";
	var productie = ['<?php echo $productie[14]?>','<?php echo $productie[13]?>','<?php echo $productie[12]?>','<?php echo $productie[11]?>','<?php echo $productie[10]?>','<?php echo $productie[9]?>','<?php echo $productie[8]?>','<?php echo $productie[7]?>','<?php echo $productie[6]?>','<?php echo $productie[5]?>','<?php echo $productie[4]?>','<?php echo $productie[3]?>','<?php echo $productie[2]?>','voorafgaande dagen','<?php echo $productie[0]?>','<?php echo $productie[1]?>'];
	var start_i = 0;
	var inverter_redraw = 1;
	var SolarProdToday = 0;
	var p1CounterToday = 0;
	var p1CounterDelivToday = 0;
	var s_lasttimestamp = 0;
	var s_p1CounterToday = 0;
	var s_p1CounterDelivToday = 0;
	var wchart="";
	var ychart="";
	var pve = 0;
	var pvs = 0;
	var pse = 0;
	var psv = 0;

	google.charts.load('current', {'packages':['gauge', 'line']});
	google.charts.setOnLoadCallback(drawChart);
	function drawChart() {
		paneel();
		zonmaan();
		draw_p1_chart();
		document.getElementById("panel_vermogen").innerHTML ="";
		document.getElementById("panel_energy").innerHTML ="";
		document.getElementById("sunrise_text").innerHTML = sunrise+" uur";
		document.getElementById("solar_noon_text").innerHTML = solar_noon+" uur";
		document.getElementById("sunset_text").innerHTML = sunset+" uur";
		document.getElementById("daglengte_text").innerHTML = daglengte+" uur";
		setInterval(function() {
			var now = new Date();
			var diff = <?php echo $tomorrow ?>-(+now/1000);
			if (diff < 0 ) {
				window.location = window.location.pathname;
				return false;
			}
			p1_update();
		}, 10000);

		setInterval(function() {
			zonmaan();
		}, 3600000);

		setInterval(function() {
			paneel();
		}, 60000);

	}
	function paneelChart(event,x) {
		if (x <= aantal){
			inverter_redraw = 0;
			document.getElementById("chart_vermogen").innerHTML ="";
			document.getElementById("chart_energy").innerHTML ="";
			// #### Vermogen  #####
			var series = paneel_chartv.series[0];
			var shift = series.data.length > 86400; // shift if the series is longer than 86400(=1 dag)
			for (var i = 0; i < data_p.length; i++){
				if (data_p[i]['op_id'] !== x && data_p[i]['serie'] == 0){
					if (data_p[i]['op_id'] < x ){
						paneel_chartv.series[data_p[i]['op_id']-1].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_current_power_prd']*1], false, shift);
						paneel_charte.series[data_p[i]['op_id']-1].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_volume_prd']*1], false, shift);
					} else {
						paneel_chartv.series[data_p[i]['op_id']-2].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_current_power_prd']*1], false, shift);
						paneel_charte.series[data_p[i]['op_id']-2].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_volume_prd']*1], false, shift);
					}
				} else {
					paneel_chartv.series[33].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_current_power_prd']*1], false, shift);
					paneel_charte.series[33].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],0),data_p[i]['p1_volume_prd']*1], false, shift);
				}
			}
			paneel_chartv.setTitle(null, { text: 'Paneel: '+op_id[x]+' en alle andere panelen', x: 20});
			paneel_chartv.yAxis[0].update({
				opposite: true
			});
			paneel_charte.setTitle(null, { text: 'Paneel: '+op_id[x]+' en alle andere panelen', x: 20});
			paneel_charte.yAxis[0].update({
				opposite: true
			});
			paneel_chartv.legend.update({x:10,y:20});
			paneel_chartv.series[33].update({name: "Vermogen paneel: "+op_id[x], style: {font: 'Arial', fontWeight: 'bold', fontSize: '12px' }});
			paneel_chartv.series[32].update({showInLegend: false});
			paneel_chartv.series[31].update({showInLegend: true, name: "Vermogen overige panelen"});
			paneel_chartv.yAxis[0].update({
				title: {
					text: 'Vermogen (W)'
				},
			});
			paneel_chartv.yAxis[1].update({
				labels: {
					enabled: false
				},
				title: {
					text: null
				}
			});
			paneel_charte.legend.update({x:10,y:20});
			paneel_charte.series[33].update({name: "Energie paneel: "+op_id[x], style: {font: 'Arial', fontWeight: 'bold', fontSize: '12px' }});
			paneel_charte.series[32].update({showInLegend: false});
			paneel_charte.series[31].update({showInLegend: true, name: "Energie overige panelen"});
			paneel_charte.yAxis[0].update({
				title: {
					text: 'Energie (Wh)'
				},
			});
			paneel_charte.yAxis[1].update({
				labels: {
					enabled: false
				},
				title: {
					text: null
				}
			});
			paneel_chartv.redraw();
			paneel_charte.redraw();
		}
	}

	function paneelChartcl() {
		inverter_redraw = 1;
		document.getElementById("panel_vermogen").innerHTML ="";
		document.getElementById("panel_energy").innerHTML ="";
		for (var i=0; i<=33; i++){
			paneel_chartv.series[i].setData([]);
			paneel_charte.series[i].setData([]);
		}
		inverter_chart.redraw();
		vermogen_chart.redraw();
	}

	function waarde(l,d,x){
		s = String(x);
		n = s.indexOf('-');
		if ( n==0) { s=s.slice(1,s.length);}
		p=s.indexOf('.');
		if ( p <0 ) { s = s + ".";}
		p=s.indexOf('.');
		for (var i=1; i <= l; i++) {
			if (l > i) {if (s.indexOf('.')<i+1) { s = "0"+ s;};}
		}
		p=s.indexOf('.');
		for (var i=1; i<=d; i++){
			if (s.length<p+1+i) { s = s + "0";}
		}
		if (d == 0 && p+1 <= s.length) { s=s.slice(0,p);}
		if (d > 0 && p+1+d < s.length) { s=s.slice(0,p+1+d);}
		if (n==0) { s="-"+s;}
		return s;
	}

	function paneel(){
		var inv1Data = $.ajax({
			url: "live-server-data-zon.php",
			dataType: "json",
			type: 'GET',
			data: { "date" : datum },
			async: false,
		}).responseText;

		inv1Data = eval(inv1Data)
		SolarProdToday = inv1Data[0]["IE"];

		if (p1CounterToday == 0) {
			p1_update()
			s_p1CounterToday = p1CounterToday;
			s_p1CounterDelivToday = p1CounterDelivToday;
		}

		if (datum1 < tomorrow && (s_lasttimestamp != inv1Data[0]["IT"] || inv1Data[0]["MODE"] != "MPPT")) {
			if(inv1Data[0]["IVACT"] != 0){
				document.getElementById("arrow_PRD").className = "arrow_right_green";
			}else{
				document.getElementById("arrow_PRD").className = "";
			}
			if (inv1Data[0]["MODE"] == "MPPT") {
				s_lasttimestamp = inv1Data[0]["IT"];
			} else {
				s_lasttimestamp = "";
				s_p1CounterToday = p1CounterToday;
				s_p1CounterDelivToday = p1CounterDelivToday;
			}

			document.getElementById("so_text").className = "green_text";
			document.getElementById("so_text").innerHTML = inv1Data[0]["IVACT"]+ " Watt";
			document.getElementById("sola_text").innerHTML = "<table width=100% class=data-table>"+
					"<tr><td colspan=2 style=\"font-size:smaller\">"+inv1Data[0]["IT"].substr(11,10)+"</td></tr>" +
					"<tr><td colspan=2><b><u>Solar vandaag</u></b></td></tr>"+
					"<tr><td>verbruik:</td><td>"+waarde(0,3,parseFloat(inv1Data[0]["IE"])-parseFloat(s_p1CounterDelivToday))+" kWh</td></tr>"+
					"<tr><td>retour:</td><td>"+waarde(0,3,parseFloat(s_p1CounterDelivToday))+" kWh</td></tr>"+
					"<tr><td></td><td>----------</td></tr>"+
					"<tr><td class=green_text>productie:</td><td class=green_text>"+waarde(0,3,inv1Data[0]["IE"])+" kWh</td></tr>"+
					"</table>";
			document.getElementById("inverter_text").innerHTML = "<table width=100% class=data-table>"+
					"<tr><td>Date:</td><td colspan=3>"+inv1Data[0]["IT"]+"</td></tr>"+
					"<tr><td>Mode:</td><td colspan=3>"+inv1Data[0]["MODE"]+"</td></tr>"+
					"<tr><td>MaxP:</td><td colspan=3>"+inv1Data[0]["IVMAX"]+" W</td><tr>"+
					"<td>Temp:</td><td colspan=3>"+waarde(0,1,inv1Data[0]["ITACT"])+" / "+waarde(0,1,inv1Data[0]["ITMIN"])+" / "+waarde(0,1,inv1Data[0]["ITMAX"])+" °C</td></tr>"+
					"<tr><td>v_dc:</td><td colspan=3>"+waarde(0,1,inv1Data[0]["v_dc"])+"</td></tr></table>";
		}else{
			document.getElementById("inverter_text").innerHTML = "<b>Inverter:</b><br>D:&emsp;&emsp;&nbsp;&nbsp;"+inv1Data[0]["IT"]+"<br>Pmax:&nbsp;&nbsp;"+inv1Data[0]["IVMAX"]+" W<br><b>E:&emsp;&emsp;&emsp;"+waarde(0,3,inv1Data[0]["IE"])+" kWh</b><br>Tmin:&emsp;"+waarde(0,1,inv1Data[0]["ITMIN"])+" °C<br>Tmax:&nbsp;&nbsp;"+waarde(0,1,inv1Data[0]["ITMAX"])+" °C";
			document.getElementById("arrow_PRD").className = "";
		}
		if (inverter == 1){
			document.getElementById("inverter_1").title = "Inverter: "+naam+"\r\n\r\nS AC:	"+inv1Data[0]["i_ac"]+" A\r\nV AC:	"+inv1Data[0]["v_ac"]+" V\r\nFre:	"+inv1Data[0]["frequency"]+" Hz\r\nPactive:	"+inv1Data[0]["p_active"]+" kWh\r\nV DC:	"+waarde(0,1,inv1Data[0]["v_dc"])+" V\r\nE:	"+inv1Data[0]["IE"]+" kWh\r\nP(act):	"+inv1Data[0]["IVACT"]+" W";
		}else{
			document.getElementById("inverter_1").title = "Inverter: "+naam+"\r\n\r\n	L1	L2	L3\r\nS AC:	"+inv1Data[0]["i_ac1"]+"	"+inv1Data[0]["i_ac2"]+"	"+inv1Data[0]["i_ac3"]+" A\r\nV AC:	"+inv1Data[0]["v_ac1"]+"	"+inv1Data[0]["v_ac2"]+"	"+inv1Data[0]["v_ac3"]+" V\r\nFre:	"+inv1Data[0]["frequency1"]+"	"+inv1Data[0]["frequency2"]+"	"+inv1Data[0]["frequency3"]+" Hz\r\nPactive:	"+inv1Data[0]["p_active1"]+"	"+inv1Data[0]["p_active2"]+"	"+inv1Data[0]["p_active3"]+" W\r\nV DC:	"+waarde(0,1,inv1Data[0]["v_dc"])+" V\r\nE:	"+inv1Data[0]["IE"]+" kWh\r\nP(act):	"+inv1Data[0]["IVACT"]+" W";
		}
//~ 			for (var i = Math.round(aantal)+1; i<=33; i++){
//~ 				document.getElementById("tool_paneel_"+i).coords = "0,0,0,0";
//~ 			}
		for (var i=1; i<=aantal; i++){
			document.getElementById("text_Zonnepaneel_"+i).innerHTML = op_id[i];
			if (rpan[i] == 0){
				document.getElementById("image_"+i).src = "./img/Zonnepaneel-ver.gif";
			}else{
				document.getElementById("image_"+i).src = "./img/Zonnepaneel-hor.gif";
			}
			if (vermogen == 1){
				document.getElementById("text_paneel_W_"+i).innerHTML = waarde(0,0,inv1Data[0]["O"+i])+ " Wh";
				if(inv1Data[0]["IVACT"] != 0){
					document.getElementById("text_paneel_W_"+i+"a").innerHTML = waarde(0,0,inv1Data[0]["E"+i])+ " W";
				} else {
					document.getElementById("text_paneel_W_"+i+"a").innerHTML = waarde(0,0,inv1Data[0]["VM"+i])+ " W";
				}
			} else {
				document.getElementById("text_paneel_W_"+i).innerHTML = waarde(0,0,inv1Data[0]["O"+i]);
				document.getElementById("text_paneel_W_"+i+"a").innerHTML = "Wh";
			}
			document.getElementById("tool_paneel_"+i).title = inv1Data[0]["TM"+i]+"\r\nPaneel "+op_id[i]+"\r\nEnergie		"+ inv1Data[0]["O"+i] +" Wh\r\nVermogen (act.)	"+ inv1Data[0]["E"+i] +" W\r\nVermogen (max.)	"+ inv1Data[0]["VM"+i] +" W\r\nStroom in	"+ inv1Data[0]["S"+i] +" A\r\nSpanning in	"+ inv1Data[0]["VI"+i] +" V\r\nSpanning uit	"+ inv1Data[0]["VU"+i] +" V\r\nTemperatuur	"+ inv1Data[0]["T"+i] +" °C";
			if ( inv1Data[0]["C"+i] == 0) {
				document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#000000";
			} else {
				if ( inv1Data[0]["C"+i] < 0.1) {
					document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#080f16";
				} else {
					if ( inv1Data[0]["C"+i] < 0.2) {
						document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#101e2d";
					} else {
						if ( inv1Data[0]["C"+i] < 0.3) {
							document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#182e44";
						} else {
							if ( inv1Data[0]["C"+i] < 0.4) {
								document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#203d5a";
							} else {
								if ( inv1Data[0]["C"+i] < 0.5) {
									document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#294d71";
								} else {
									if ( inv1Data[0]["C"+i] < 0.6) {
										document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#315c88";
									} else {
										if ( inv1Data[0]["C"+i] < 0.7) {
											document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#396b9e";
										} else {
											if ( inv1Data[0]["C"+i] < 0.8) {
												document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#417bb5";
											} else {
												if ( inv1Data[0]["C"+i] < 0.9) {
													document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#498acc";
												} else {
													document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#529ae3";
			}	}	}	}	}	}	}	}	}	}
		}
	}

	function p1_update(){
		var p1data = $.ajax({
			url: "<?php echo $DataURL?>?period=c",
			dataType: "json",
			type: 'GET',
			data: { },
			async: false,
		}).responseText;
		p1data = JSON.parse(p1data);
		p1servertime = p1data[0]["ServerTime"];
		p1CounterToday = p1data[0]["CounterToday"];
		p1CounterDelivToday = p1data[0]["CounterDelivToday"];
		p1Usage = p1data[0]["Usage"];
		p1UsageDeliv = p1data[0]["UsageDeliv"];
		if (typeof p1servertime === 'undefined') {p1servertime = "";}
		if (typeof p1CounterToday === 'undefined') {
			p1CounterToday = 0;
		} else {
			p1CounterToday = parseFloat(p1CounterToday);
		}
		if (typeof p1CounterDelivToday === 'undefined') {
			p1CounterDelivToday = 0;
		} else {
			p1CounterDelivToday = parseFloat(p1CounterDelivToday);
		}
		if (typeof p1Usage === 'undefined') {
			p1Usage = 0;
		} else {
			p1Usage = parseFloat(p1Usage);
		}
		if (typeof p1UsageDeliv === 'undefined') {
			p1UsageDeliv = 0;
		} else {
			p1UsageDeliv = parseFloat(p1UsageDeliv);
		}
		if( p1CounterToday == 0){
			document.getElementById("arrow_RETURN").className = "";
			document.getElementById("p1_text").className = "red_text";
			document.getElementById("p1_text").innerHTML = "No data";
		}else if( parseFloat(p1Usage) == 0){
			document.getElementById("arrow_RETURN").className = "arrow_right_green";
			document.getElementById("p1_text").className = "green_text";
			document.getElementById("p1_text").innerHTML = p1UsageDeliv +" Watt";
		}else{
			document.getElementById("arrow_RETURN").className = "arrow_left_red";
			document.getElementById("p1_text").className = "red_text";
			document.getElementById("p1_text").innerHTML = p1Usage +" Watt";
		}
		var diff=parseFloat(p1CounterToday)-parseFloat(p1CounterDelivToday);
		var cdiff  = "red_text";
		if (diff < 0) {
			cdiff  = "green_text";
			diff = diff * -1;
		}

		document.getElementById("elec_text").innerHTML = "<table width=100% class=data-table>"+
				"<tr><td colspan=2 style=\"font-size:smaller\">"+p1servertime.substr(11,10)+"</td></tr>" +
				"<tr><td colspan=2><u><b><?php echo $ElecLeverancier?> vandaag</u></b></td></tr>" +
				"<tr><td>verbruik:</td><td>"+waarde(0,3,parseFloat(p1CounterToday))+" kWh</td></tr>" +
				"<tr><td>retour:</td><td>"+waarde(0,3,parseFloat(p1CounterDelivToday))+" kWh</td></tr>" +
				"<tr><td></td><td>----------</td></tr>"+
				"<tr><td class="+cdiff+">netto:</td><td class="+cdiff+" >"+waarde(0,3,diff)+" kWh</td></tr>"+
				"</table>";

		if (wchart != "" && pse+psv+pve+pvs > 0) {
			// update current day info in graphs
			var cdate = new Date();
			var prod = SolarProdToday   ;      //Solar productie
			var ve = p1CounterToday;
			var vs = prod - p1CounterDelivToday;
			var se = p1CounterDelivToday;
			var sv = vs;
			wchart.series[0].data[wchart.series[0].data.length-1].update(se);
			wchart.series[1].data[wchart.series[1].data.length-1].update(sv);
			wchart.series[2].data[wchart.series[2].data.length-1].update(ve);
			wchart.series[3].data[wchart.series[3].data.length-1].update(vs);
			//
			// update huidige maand
			var mse = ychart.series[0].data[ychart.series[0].data.length-1].y - pse + se;
			var msv = ychart.series[1].data[ychart.series[1].data.length-1].y - psv + sv;
			var mve = ychart.series[2].data[ychart.series[2].data.length-1].y - pve + ve;
			var mvs = ychart.series[3].data[ychart.series[3].data.length-1].y - pvs + vs;
			ychart.series[0].data[ychart.series[0].data.length-1].update(mse);
			ychart.series[1].data[ychart.series[1].data.length-1].update(msv);
			ychart.series[2].data[ychart.series[2].data.length-1].update(mve);
			ychart.series[3].data[ychart.series[3].data.length-1].update(mvs);
			pve = ve;
			pvs = vs;
			pse = se;
			psv = sv;
			//
			wchart.redraw();
			ychart.redraw();
			update_map_fields();
		}
	}

	function update_map_fields() {
		if (wchart.series[0].data.length>0 && ychart.series[0].data.length>0) {
			var se = wchart.series[0].data[wchart.series[0].data.length-1].y;
			var sv = wchart.series[1].data[wchart.series[1].data.length-1].y;
			var ve = wchart.series[2].data[wchart.series[2].data.length-1].y;
			var vs = wchart.series[3].data[wchart.series[3].data.length-1].y;
			var mse = ychart.series[0].data[ychart.series[0].data.length-1].y;
			var msv = ychart.series[1].data[ychart.series[1].data.length-1].y;
			var mve = ychart.series[2].data[ychart.series[2].data.length-1].y;
			var mvs = ychart.series[3].data[ychart.series[3].data.length-1].y;
			var cur = document.getElementById("p1_text").innerHTML;
			var yse = mse;
			var ysv = msv;
			var yve = mve;
			var yvs = mvs;
			$.each(ychart.series[2].data, function(i, point) {
				var cd = new Date();
				var cm = cd.getMonth();
				var cy = cd.getYear();
				var pd = new Date(point.x);
				var pm = pd.getMonth();
				var py = pd.getYear();
				if(cy == py && cm != pm) {
					yse += ychart.series[0].data[i].y;
					ysv += ychart.series[1].data[i].y;
					yve += ychart.series[2].data[i].y;
					yvs += ychart.series[3].data[i].y;
				}
			});

			document.getElementById("p1_huis").className = "red_text";
			if (s_p1CounterToday+s_p1CounterDelivToday > 0) {
				var cP1Huis = parseFloat('0'+document.getElementById("p1_huis").innerHTML);
				if ( cP1Huis == 0 || cP1Huis < SolarProdToday - s_p1CounterDelivToday + s_p1CounterToday) {
					document.getElementById("p1_huis").innerHTML = waarde(0,3,SolarProdToday - s_p1CounterDelivToday + s_p1CounterToday)+" kWh";
					document.getElementById("huis_1").title = "thuis verbruik: "+s_lasttimestamp+
							"\r\nVandaag\r\nZonne energie: " + waarde(0,3,SolarProdToday - s_p1CounterDelivToday)+" kWh"+
							"\r\n<?php echo $ElecLeverancier?> energie: " + waarde(0,3,s_p1CounterToday)+" kWh"+
							"\r\nTotaal verbruik: "+ waarde(0,3,SolarProdToday - s_p1CounterDelivToday + s_p1CounterToday)+" kWh" +
							"\r\n\r\nMaand\r\nZonne energie: " + waarde(0,2,mvs)+" kWh"+
							"\r\n<?php echo $ElecLeverancier?> energie: " + waarde(0,2,mve)+" kWh"+
							"\r\nTotaal verbruik: "+ waarde(0,2,mve+mvs)+" kWh" +
							"\r\n\r\nJaar\r\nZonne energie: " + waarde(0,1,yvs)+" kWh"+
							"\r\n<?php echo $ElecLeverancier?> energie: " + waarde(0,1,yve)+" kWh"+
							"\r\nTotaal verbruik: "+ waarde(0,1,yve+yvs)+" kWh";
				}
			} else {
				document.getElementById("p1_huis").innerHTML = "No Data";
				document.getElementById("huis_1").title = "No Data";
			}

			if ( document.getElementById("p1_text").className == "red_text") {
				cur = "verbruik: " + cur;
			} else {
				cur = "retour:   " + cur;
			}
			document.getElementById("meter_1").title = "<?php echo $ElecLeverancier?> P1 meter" +
					"\r\nHuidig\r\n"+cur+
					"\r\n\r\nVandaag\r\nverbruik: "+waarde(0,3,ve)+" kWh\r\nretour:    "+waarde(0,3,se)+" kWh\r\nnetto:      "+waarde(0,3,ve-se)+" kWh"+
					"\r\n\r\nMaand\r\nverbruik: "+waarde(0,2,mve)+" kWh\r\nretour:    "+waarde(0,2,mse)+" kWh\r\nnetto:      "+waarde(0,2,mve-mse)+" kWh" +
					"\r\n\r\nJaar\r\nverbruik: "+waarde(0,1,yve)+" kWh\r\nretour:    "+waarde(0,1,yse)+" kWh\r\nnetto:      "+waarde(0,1,yve-yse)+" kWh";

		}
	}

	function zonmaan(){
		if (date2 >= date3){
			document.getElementById("NextDay").disabled = true;
		}else{
			document.getElementById("NextDay").disabled = false;
		}
		if (date2 <= "2016-01-01"){
			document.getElementById("PrevDay").disabled = true;
		}
		if (datum1 < tomorrow) {
			datumz = Date();
		}
		var inv4Data = $.ajax({
			url: "maanfase.php",
			dataType: "json",
			type: 'GET',
			data: { "date" : datumz },
			async: false,
		}).responseText;
		inv4Data = eval(inv4Data)
		date3 = inv4Data[0]["date3"];
		datum1 = inv4Data[0]["datum1"];
		document.getElementById("maan_th").src = inv4Data[0]["filenaam"];
		document.getElementById("fase_text").innerHTML = inv4Data[0]["phase_naam"];
		document.getElementById("verlicht_text").innerHTML = inv4Data[0]["illumination"]+'% Verlicht';
	}
	$(function() {
		Highcharts.setOptions({
			global: {
				useUTC: false,
			},
			style: {
				fontFamily: 'Arial'
			}
		})
		var urlname = 'live-server-data-s.php'
		var urlname1 = 'live-server-data-paneel.php'
		var urlname2 = 'live-server-data-inverter.php'
		function requestData1() {
			$.ajax({
				url: urlname,//url of data source
				type: 'GET',
				data: { "date" : datum }, //optional
				success: function(data) {
					var series = power_chart.series[0];
					var shift = series.data.length > 86400; // shift if the series is longer than 86400(=1 dag)
					data = eval(data);
					for(var i = 0; i < data.length; i++){
						power_chart.series[0].addPoint([Date.UTC(data[i]['jaar'],data[i]['maand'],data[i]['dag'],data[i]['uur'],data[i]['minuut'],data[i]['sec']),data[i]['p1_volume_prd']*1], false, shift);
						power_chart.series[1].addPoint([Date.UTC(data[i]['jaar'],data[i]['maand'],data[i]['dag'],data[i]['uur'],data[i]['minuut'],data[i]['sec']),data[i]['p1_current_power_prd']*1], false, shift);
					}
					power_chart.redraw();
					urlname = 'live-server-data-c.php';
					if (datum1 < tomorrow) {
					   setTimeout(requestData1, 1000*60);
					} else {
					   setTimeout(requestData1, 1000*86400);
					}
				},
				cache: false
			});
		}
		function requestData2() {
			$.ajax({
				url: urlname1,//url of data source
				type: 'GET',
				data: { "date" : datum }, //optional
				success: function(data) {
					data_p = eval(data);
					if (datum1 < tomorrow) {
					   setTimeout(requestData2, 1000*60);
					} else {
					   setTimeout(requestData2, 1000*86400);
					}
				},
				cache: false
			});
		}
		function requestDatai() {
			$.ajax({
				url: urlname2,//url of data source
				type: 'GET',
				data: { "date" : datum }, //optional
				success: function(data) {
					data_i = eval(data);
					var series = inverter_chart.series[0];
					var shift = series.data.length > 86400; // shift if the series is longer than 86400(=1 dag)
					for (var i=0; i<=14; i++){
						inverter_chart.series[i].setData([]);
						vermogen_chart.series[i].setData([]);
					}
					for(var i = 0; i < data_i.length; i++){
						if (data_i[i]['op_id'] == "i"){
							inverter_chart.series[14-data_i[i]['serie']].addPoint([Date.UTC(data_i[i]['jaar'],data_i[i]['maand'],data_i[i]['dag'],data_i[i]['uur'],Math.round(data_i[i]['minuut']*0.2)*5,0),data_i[i]['p1_volume_prd']*1], false, shift);
							vermogen_chart.series[14-data_i[i]['serie']].addPoint([Date.UTC(data_i[i]['jaar'],data_i[i]['maand'],data_i[i]['dag'],data_i[i]['uur'],Math.round(data_i[i]['minuut']*0.2)*5,0),data_i[i]['p1_current_power_prd']*1], false, shift);
						}
					}
					if(inverter_redraw == 1) {
						inverter_chart.redraw();
						vermogen_chart.redraw();
					}
					if (datum1 < tomorrow) {
					   setTimeout(requestDatai, 1000*60);
					} else {
					   setTimeout(requestDatai, 1000*86400);
					}
				},
				cache: false
			});
		}
		function requestDatav() {
		}
		$(document).ready(function() {
			paneel_chartv = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: 'panel_vermogen',
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'none',
					events: {load: requestData2}
				},
				title: {
					text: null
				},
				subtitle: {
					text: "",
					align: 'left',
					x: 90,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
					},
					floating: true
				},
				xAxis: [{
					type: 'datetime',
					pointstart: Date.UTC(1970,01,01),
					maxZoom: 9000 * 1000, // 600 seconds = 10 minutes
					title: {
						text: null
					},
					startOnTick: true,
					minPadding: 0,
					maxPadding: 0,
					labels: {
						overflow: 'justify'
					},
					tooltip: {
						enabled: true,
						crosshair: true
					},
					plotBands: [{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur0-winter),
						to: Date.UTC(jaar, maand, dag, uur1-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur2-winter),
						to: Date.UTC(jaar, maand, dag, uur3-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur4-winter),
						to: Date.UTC(jaar, maand, dag, uur5-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur6-winter),
						to: Date.UTC(jaar, maand, dag, uur7-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur8-winter),
						to: Date.UTC(jaar, maand, dag, uur9-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur10-winter),
						to: Date.UTC(jaar, maand, dag, uur11-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur12-winter),
						to: Date.UTC(jaar, maand, dag, uur13-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur14-winter),
						to: Date.UTC(jaar, maand, dag, uur15-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur16-winter),
						to: Date.UTC(jaar, maand, dag, uur17-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur18-winter),
						to: Date.UTC(jaar, maand, dag, uur19-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur20-winter),
						to: Date.UTC(jaar, maand, dag, uur21-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur22-winter),
						to: Date.UTC(jaar, maand, dag, uur23-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur24-winter),
						to: Date.UTC(jaar, maand, dag, uur25-winter),
					}],
				}],
				yAxis: [{
					title: {
						text: 'Vermogen(W)'
					},
					showEmpty: false,
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick) / 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					}
				}, {
					title: {
						text: 'Energie (Wh)'
					},
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick)/ 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					},
					opposite: true
				}],
				legend: {
					itemStyle: {
						fontWeight: 'Thin',
					},
					layout: 'vertical',
					align: 'left',
					x: 10,
					verticalAlign: 'top',
					y: 20,
					floating: true,
				},
				credits: {
					enabled: false
				},
				tooltip: {
					formatter: function () {
						var s = '<b>' + Highcharts.dateFormat('%A %d-%m-%Y %H:%M:%S', this.x) + '</b>';
						$.each(this.points, function () {
							if (this.series.name == 'Energie Productie') {
								s += '<br/>' + this.series.name + ': ' +
								this.y + ' kWh';
							}
							if (this.series.name == 'Stroom Productie') {
								s += '<br/>' + this.series.name + ': ' +
								this.y + ' W';
							}
						});
						return s;
					},
					shared: true,
					snap: 0,
					crosshairs: [{
						width: 1,
						color: 'red',
						zIndex: 3
					}]
				},
				plotOptions: {
					  spline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							symbol: 'circle',
							states: {
								hover: {
								enabled: true
								}
							}
						}
					  },
					  areaspline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							type: 'triangle',
							states: {
								hover: {
								enabled: true,
								}
							}
						}
					}
				},
				exporting: {
					enabled: false,
					filename: 'power_chart',
					url: 'export.php'
				},
				series: [{
					name: 'Paneel_33',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_32',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_31',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_30',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_29',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_28',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_27',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_26',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_25',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_24',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_23',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_22',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_21',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_20',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_19',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_18',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_17',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_16',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_15',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_14',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_13',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_12',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_11',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_10',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_9',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_8',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_7',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_6',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_5',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_4',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_3',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_2',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Energie Productie',
					showInLegend: true,
					type: 'areaspline',
					marker: {
						symbol: 'triangle'
					},
					yAxis: 1,
					showEmpty: true,
					lineWidth: 1,
					color: 'rgba(204,255,153,1)',
					pointWidth: 2,
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_1',
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					color: '#009900',
					data: []//this will be filled by requestData()
				}]
			});
		});
		$(document).ready(function() {
			paneel_charte = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: 'panel_energy',
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'none',
					//only needed once as I show both graphs and they use same data -> paneel_chartv
					//events: {load: requestData2}
				},
				title: {
					text: null
				},
				subtitle: {
					text: "",
					align: 'left',
					x: 90,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
					},
					floating: true
				},
				xAxis: [{
					type: 'datetime',
					pointstart: Date.UTC(1970,01,01),
					maxZoom: 9000 * 1000, // 600 seconds = 10 minutes
					title: {
						text: null
					},
					startOnTick: true,
					minPadding: 0,
					maxPadding: 0,
					labels: {
						overflow: 'justify'
					},
					tooltip: {
						enabled: true,
						crosshair: true
					},
					plotBands: [{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur0-winter),
						to: Date.UTC(jaar, maand, dag, uur1-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur2-winter),
						to: Date.UTC(jaar, maand, dag, uur3-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur4-winter),
						to: Date.UTC(jaar, maand, dag, uur5-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur6-winter),
						to: Date.UTC(jaar, maand, dag, uur7-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur8-winter),
						to: Date.UTC(jaar, maand, dag, uur9-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur10-winter),
						to: Date.UTC(jaar, maand, dag, uur11-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur12-winter),
						to: Date.UTC(jaar, maand, dag, uur13-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur14-winter),
						to: Date.UTC(jaar, maand, dag, uur15-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur16-winter),
						to: Date.UTC(jaar, maand, dag, uur17-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur18-winter),
						to: Date.UTC(jaar, maand, dag, uur19-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur20-winter),
						to: Date.UTC(jaar, maand, dag, uur21-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur22-winter),
						to: Date.UTC(jaar, maand, dag, uur23-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur24-winter),
						to: Date.UTC(jaar, maand, dag, uur25-winter),
					}],
				}],
				yAxis: [{
					title: {
						text: 'Vermogen(W)'
					},
					showEmpty: false,
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick) / 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					}
				}, {
					title: {
						text: 'Energie (Wh)'
					},
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick)/ 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					},
					opposite: true
				}],
				legend: {
					itemStyle: {
						fontWeight: 'Thin',
					},
					layout: 'vertical',
					align: 'left',
					x: 10,
					verticalAlign: 'top',
					y: 20,
					floating: true,
				},
				credits: {
					enabled: false
				},
				tooltip: {
					formatter: function () {
						var s = '<b>' + Highcharts.dateFormat('%A %d-%m-%Y %H:%M:%S', this.x) + '</b>';
						$.each(this.points, function () {
							if (this.series.name == 'Energie Productie') {
								s += '<br/>' + this.series.name + ': ' +
								this.y + ' kWh';
							}
							if (this.series.name == 'Stroom Productie') {
								s += '<br/>' + this.series.name + ': ' +
								this.y + ' W';
							}
						});
						return s;
					},
					shared: true,
					snap: 0,
					crosshairs: [{
						width: 1,
						color: 'red',
						zIndex: 3
					}]
				},
				plotOptions: {
					  spline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							symbol: 'circle',
							states: {
								hover: {
								enabled: true
								}
							}
						}
					  },
					  areaspline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							type: 'triangle',
							states: {
								hover: {
								enabled: true,
								}
							}
						}
					}
				},
				exporting: {
					enabled: false,
					filename: 'power_chart',
					url: 'export.php'
				},
				series: [{
					name: 'Paneel_33',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_32',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_31',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_30',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_29',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_28',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_27',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_26',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_25',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_24',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_23',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_22',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_21',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_20',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_19',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_18',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_17',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_16',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_15',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_14',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_13',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_12',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_11',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_10',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_9',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_8',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_7',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_6',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_5',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_4',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_3',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_2',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: 'Energie Productie',
					showInLegend: true,
					type: 'areaspline',
					marker: {
						symbol: 'triangle'
					},
					yAxis: 1,
					showEmpty: true,
					lineWidth: 1,
					color: 'rgba(204,255,153,1)',
					pointWidth: 2,
					data: []//this will be filled by requestData()
				},{
					name: 'Paneel_1',
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					color: '#009900',
					data: []//this will be filled by requestData()
				}]
			});
		});
		$(document).ready(function() {
			inverter_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: "chart_energy",
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'none',
					events: {load: requestDatai},
					spacingRight: 5
				},
				title: {
				   text: null
				},
				subtitle: {
					text: "Energie op <?php echo $datev;?> en 14 voorafgaande  dagen",
					align: 'left',
					x: 20,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
					},
					floating: true
				},
				xAxis: [{
					type: 'datetime',
					pointstart: Date.UTC(1970,01,01),
					maxZoom: 9000 * 1000, // 600 seconds = 10 minutes
					title: {
						text: null
					},
					startOnTick: true,
					minPadding: 0,
					maxPadding: 0,
					labels: {
						overflow: 'justify'
					},
					tooltip: {
						enabled: true,
						crosshair: true
					},
					plotBands: [{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur0-winter),
						to: Date.UTC(jaar, maand, dag, uur1-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur2-winter),
						to: Date.UTC(jaar, maand, dag, uur3-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur4-winter),
						to: Date.UTC(jaar, maand, dag, uur5-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur6-winter),
						to: Date.UTC(jaar, maand, dag, uur7-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur8-winter),
						to: Date.UTC(jaar, maand, dag, uur9-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur10-winter),
						to: Date.UTC(jaar, maand, dag, uur11-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur12-winter),
						to: Date.UTC(jaar, maand, dag, uur13-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur14-winter),
						to: Date.UTC(jaar, maand, dag, uur15-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur16-winter),
						to: Date.UTC(jaar, maand, dag, uur17-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur18-winter),
						to: Date.UTC(jaar, maand, dag, uur19-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur20-winter),
						to: Date.UTC(jaar, maand, dag, uur21-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur22-winter),
						to: Date.UTC(jaar, maand, dag, uur23-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur24-winter),
						to: Date.UTC(jaar, maand, dag, uur25-winter),
					}],
				}],
				yAxis: [{
					title: {
						text: 'Energie (kWh)'
					},
					opposite: true,
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick)/ 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					}
				}],
				legend: {
					itemStyle: {
						fontWeight: 'Thin',
					},
					layout: 'vertical',
					align: 'left',
					x: 10,
					verticalAlign: 'top',
					y: 20,
					floating: true,
				},
				credits: {
					enabled: false
				},
				tooltip: {
					formatter: function () {
						var s ="";
						s += '-> <u><b>' + Highcharts.dateFormat(' %H:%M', this.x)+ '</b></u><br>';
						//if (this.points[this.points.length-1].series.name != 'voorafgaande dagen') {
						//	s += "<b>" + this.points[this.points.length-1].series.name.substr(this.points[this.points.length-1].series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.points[this.points.length-1].y,2) + ' kWh</b>';
						//}
						var sortedPoints = this.points.sort(function(a, b){
							return ((a.y > b.y) ? -1 : ((a.y < b.y) ? 1 : 0));
						});
						$.each(sortedPoints, function () {
							for (i=0; i<=14; i++){
								if (this.series.name == productie[i]) {
									if (s != ""){ s += '<br>'}
										//s += "<b>" + this.series.name.substr(this.series.name.length - 10, 5) + Highcharts.dateFormat(' %H:%M', this.x)+ ': ' + Highcharts.numberFormat(this.y,2) + ' kWh</b>';
									if (i == 14){
										s += "<b>" + this.series.name.substr(this.series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,2) + ' kWh</b>';
									} else if (i != 13){
										s += this.series.name.substr(this.series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,2) + ' kWh';
									} else {
										s += productie[15].substr(productie[15].length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,2) + ' kWh';
									}
								}
							}
						});
						return s;
					},
					shared: true,
					snap: 0,
					crosshairs: [{
						width: 1,
						color: 'red',
						zIndex: 3
					}]
				},
				plotOptions: {
					  spline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							symbol: 'circle',
							states: {
								hover: {
								enabled: true
								}
							}
						}
					  },
					  areaspline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							type: 'triangle',
							states: {
								hover: {
								enabled: true,
								}
							}
						}
					}
				},
				exporting: {
					enabled: false,
					filename: 'power_chart',
					url: 'export.php'
				},
				series: [{
					name: productie[0],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[1],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[2],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[3],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[4],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[5],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[6],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[7],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[8],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[9],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[10],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[11],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[12],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[13],
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[14],
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					lineWidth: 2,
					color: '#009900',
					data: []//this will be filled by requestData()
				}]
			});
		});
		$(document).ready(function() {
			vermogen_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: "chart_vermogen",
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'none',
					spacingRight: 5,
					events: {load: requestDatav}
				},
				title: {
				   text: null
				},
				subtitle: {
					text: "Vermogen op <?php echo $datev;?> en 14 voorafgaande  dagen",
					align: 'left',
					x: 20,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
					},
					floating: true
				},
				xAxis: [{
					type: 'datetime',
					pointstart: Date.UTC(1970,01,01),
					maxZoom: 9000 * 1000, // 600 seconds = 10 minutes
					title: {
						text: null
					},
					startOnTick: true,
					minPadding: 0,
					maxPadding: 0,
					labels: {
						overflow: 'justify'
					},
					tooltip: {
						enabled: true,
						crosshair: true
					},
					plotBands: [{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur0-winter),
						to: Date.UTC(jaar, maand, dag, uur1-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur2-winter),
						to: Date.UTC(jaar, maand, dag, uur3-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur4-winter),
						to: Date.UTC(jaar, maand, dag, uur5-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur6-winter),
						to: Date.UTC(jaar, maand, dag, uur7-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur8-winter),
						to: Date.UTC(jaar, maand, dag, uur9-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur10-winter),
						to: Date.UTC(jaar, maand, dag, uur11-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur12-winter),
						to: Date.UTC(jaar, maand, dag, uur13-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur14-winter),
						to: Date.UTC(jaar, maand, dag, uur15-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur16-winter),
						to: Date.UTC(jaar, maand, dag, uur17-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur18-winter),
						to: Date.UTC(jaar, maand, dag, uur19-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur20-winter),
						to: Date.UTC(jaar, maand, dag, uur21-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur22-winter),
						to: Date.UTC(jaar, maand, dag, uur23-winter),
					},{
						color: '#ebfbff',
						from: Date.UTC(jaar, maand , dag, uur24-winter),
						to: Date.UTC(jaar, maand, dag, uur25-winter),
					}],
				}],
				yAxis: [{
					title: {
						text: 'Vermogen (W)'
					},
					opposite: true,
					tickPositioner: function () {
						var positions = [],
						tick = Math.floor(0),
						tickMax = Math.ceil(this.dataMax),
						increment = Math.ceil((tickMax - tick)/ 6);
						if (this.dataMax ==  this.dataMin ) {
							increment = .5,
							tickMax = tick + 3
						}
						if (this.dataMax !== null && this.dataMin !== null) {
							for (i=0; i<=6; i += 1) {
								positions.push(tick);
								tick += increment;
							}
						}
						return positions;
					}
				}],
				legend: {
					itemStyle: {
						fontWeight: 'Thin',
					},
					layout: 'vertical',
					align: 'left',
					x: 10,
					verticalAlign: 'top',
					y: 20,
					floating: true,
				},
				credits: {
					enabled: false
				},
				tooltip: {
					formatter: function () {
						var s = "";
						s += '-> <u><b>' + Highcharts.dateFormat(' %H:%M', this.x)+ '</b></u><br>';
						//if (this.points[this.points.length-1].series.name != 'voorafgaande dagen') {
						//	s += "<b>" + this.points[this.points.length-1].series.name.substr(this.points[this.points.length-1].series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.points[this.points.length-1].y,2) + ' W</b>';
						//}
						var sortedPoints = this.points.sort(function(a, b){
							return ((a.y > b.y) ? -1 : ((a.y < b.y) ? 1 : 0));
						});
						$.each(sortedPoints, function () {
							for (i=0; i<=14; i++){
								if (this.series.y == this.y) {};
								if (this.series.name == productie[i]) {
									if (s != ""){ s += '<br>'}
									if (i == 14){
										s += "<b>" + this.series.name.substr(this.series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,0) + ' W</b>';
									} else if (i != 13){
										s += this.series.name.substr(this.series.name.length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,0) + ' W';
									} else {
										s += productie[15].substr(productie[15].length - 10, 5) + ': ' + Highcharts.numberFormat(this.y,0) + ' W';
									}
								}
							}
						});
						return s;
					},
					shared: true,
					snap: 0,
					crosshairs: [{
						width: 1,
						color: 'red',
						zIndex: 3
					}]
				},
				plotOptions: {
					  spline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							symbol: 'circle',
							states: {
								hover: {
								enabled: true
								}
							}
						}
					  },
					  areaspline: {
						lineWidth: 1,
						marker: {
							enabled: false,
							type: 'triangle',
							states: {
								hover: {
								enabled: true,
								}
							}
						}
					}
				},
				exporting: {
					enabled: false,
					filename: 'power_chart',
					url: 'export.php'
				},
				series: [{
					name: productie[0],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[1],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[2],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[3],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[4],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[5],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[6],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[7],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[8],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[9],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[10],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[11],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[12],
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[13],
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},{
					name: productie[14],
					showInLegend: true,
					type: 'spline',
					yAxis: 0,
					lineWidth: 2,
					color: '#009900',
					data: []//this will be filled by requestData()
				}]
			});
		});
	});


	$('#multiShowPicker').calendarsPicker({
		pickerClass: 'noPrevNext', maxDate: +0, minDate: begin,
		dateFormat: 'yyyy-mm-dd', defaultDate: date2, selectDefaultDate: true,
		renderer: $.calendarsPicker.weekOfYearRenderer,
		firstDay: 1, showOtherMonths: true, rangeSelect: false, showOnFocus: true,
		onShow: $.calendarsPicker.multipleEvents(
		$.calendarsPicker.selectWeek, $.calendarsPicker.showStatus),
		onClose: function(dates) { toonDatum(dates); },
	});
	$('#PrevDay').click(function() {
		var dates = $('#multiShowPicker').calendarsPicker('getDate');
		var date = new Date(dates[0]);
		date.setDate(date.getDate()-1);
		var day = date.getDate();
		if (day < 10){
		day = "0" + String(day);
		}else{
		day = String(day);
		}
		var month = date.getMonth()+1;
		if (month < 10){
		month = "0" + String(month);
		}else{
		month = String(month);
		}
		var year = date.getFullYear();
		var datum = String(year) + "-" + month + "-" + day;
		toonDatum(datum);
	});
	$('#NextDay').click(function() {
		var dates = $('#multiShowPicker').calendarsPicker('getDate');
		var date = new Date(dates[0]);
		date.setDate(date.getDate()+1);
		var day = date.getDate();
		if (day < 10){
			day = "0" + String(day);
		}else{
			day = String(day);
		}
		var month = date.getMonth()+1;
		if (month < 10){
			month = "0" + String(month);
		}else{
			month = String(month);
		}
		var year = date.getFullYear();
		var datum = String(year) + "-" + month + "-" + day;
		toonDatum(datum);
	});

// -------------------------------
// P1 meter scripts
// -------------------------------
	function draw_p1_chart() {

		// definieer standaard opties voor iedere grafiek
		var chartoptions={
			chart: {
				renderTo: 'DIV',
				borderColor: 'grey',
				borderWidth: 1,
				borderRadius: 5,
				type: 'column',
				marginRight: 10,
			},
			title: {
				text: null
			},
			subtitle: {
				text: 'TITLE',
				style: {
					font: 'Arial',
					fontWeight: 'bold',
					color: 'gray',
					fontWeight: 'bold'
				}
			},
			series: {
			},
			xAxis: {
				type: 'datetime',
				dateTimeLabelFormats: {
				},
				labels: {
					style: {
						color: 'gray'
					}
				}
			},
			yAxis: {
				title: {
					text: 'Energie (kWh)',
					style: {
						color: 'gray',
						fontWeight: 'bold'
					}
				},
				min: 0,
				labels: {
					style: {
						color: 'gray'
					}
				}
			},
			tooltip: {
				useHTML: true,
				formatter: function() {
					var s = '<b><u>';
					sRE = 0;
					sVS = 0;
					sVE = 0;
					$.each(this.points, function(i, point) {
						if(point.series.name == 'Solar Retour <?php echo $ElecLeverancier?>') {
							sRE += point.y;
						} else if(point.series.name == 'Solar verbruik') {
							sVS += point.y;
						} else if(point.series.name == 'Verbruik <?php echo $ElecLeverancier?>') {
							sVE += point.y;
						} ;
					});
					if (this.points[0].series.chart.renderTo.id == "monthgraph") {
						s += "" + Highcharts.dateFormat('%B %Y', this.x);
					} else {
						s += Highcharts.dateFormat('%A', this.x) + ' ' + Highcharts.dateFormat('%d-%m-%Y', this.x);
					}
					s += '</u></b><br/>';
					//
					if(sVS+sRE>0){
						s += 'Solar verbruik&nbsp;: ' + Highcharts.numberFormat(sVS,1) + ' kWh<br/>';
						s += '<b>Solar totaal&nbsp;&nbsp;&nbsp;&nbsp;: ' + Highcharts.numberFormat(sVS+sRE,1) + '</b> kWh<br/>';
						s += '----------------------<br/>';
						s += '<?php echo $ElecLeverancier?> verbruik: ' + Highcharts.numberFormat(sVE,1) + ' kWh<br/>';
						s += '<?php echo $ElecLeverancier?> retour&nbsp;-: ' + Highcharts.numberFormat(sRE,1) + ' kWh<br/>';
						s += '<b><?php echo $ElecLeverancier?> Netto&nbsp;&nbsp;&nbsp;: <b>' + Highcharts.numberFormat(sVE-sRE,1) + '</b> kWh<br/>';
						s += '&nbsp;<br/>';
						s += '<b>Totaal verbruik: <b>' + Highcharts.numberFormat(sVE+sVS,1) + '</b> kWh';
					} else {
						s += '<b><?php echo $ElecLeverancier?> verbruik: <b>' + Highcharts.numberFormat(sVE-sRE,1) + '</b> kWh';
					}
					return s;
				},
				shared: true
			},
			plotOptions: {
				series: {
					dataLabels: {
					},
				},
				column: {
					stacking: 'normal',
					minPointLength: 4,
					pointPadding: 0.15,
					groupPadding: 0
				},
				area: {
					stacking: 'normal',
					minPointLength: 4,
					pointPadding: 0.1,
					groupPadding: 0
				}
			},
			exporting: {
				enabled: false,
				filename: 'power_chart',
				url: 'export.php'
			},
			legend: {
				enabled: true,
				itemStyle: {
					color: 'gray',
					fontWeight: 'bold'
				}
			},
			credits: {
				enabled: false
			},
		};


		// Add weeknummer format
		Highcharts.dateFormats = {
			W: function (timestamp) {
				var date = new Date(timestamp),
					day = date.getUTCDay() === 0 ? 7 : date.getUTCDay(),
					dayNumber;
				date.setDate(date.getUTCDate() + 4 - day);
				dayNumber = Math.floor((date.getTime() - new Date(date.getUTCFullYear(), 0, 1, -6)) / 86400000);
				return 1 + Math.floor(dayNumber / 7);

			}
		};

		// creeer de Charts met ieder hun eigen setting
		chartoptions.subtitle.text='<?php echo $ElecLeverancier?> overzicht laatste <?php echo $ElecDagGraph?> dagen.';
		chartoptions.chart.renderTo='daygraph';
		chartoptions.xAxis.dateTimeLabelFormats.day='%a %d-%b';
		chartoptions.xAxis.tickInterval=24 * 3600 * 1000;
		wchart = new Highcharts.Chart(chartoptions);


		chartoptions.subtitle.text='<?php echo $ElecLeverancier?> overzicht laatste <?php echo $ElecMaandGraph?> maanden.';
		chartoptions.chart.renderTo='monthgraph';
		chartoptions.series.pointInterval=24 * 3600 * 1000*30;
		chartoptions.xAxis.tickInterval=28*24*3600*1000;
		ychart = new Highcharts.Chart(chartoptions);

		// voeg de data series toe aan de Charts
		AddSeriestoChart(wchart, 0);
		AddSeriestoChart(ychart, 0);

		// lees data en update grafieken alleen initieel
		updateP1graphs(wchart,"d",<?php echo $ElecDagGraph?>);
		updateP1graphs(ychart,"m",<?php echo $ElecMaandGraph?>);
	}
	function updateP1graphs(ichart,gtype, periods) {
		var url='<?php echo $DataURL?>?period='+gtype+'&aantal='+periods;
		$.getJSON(url,
			function(data1){
				var series = [], domoData= data1.result;
				if (typeof data1 != 'undefined') {
					AddDataToUtilityChart(data1, ichart, 0);
				}
				ichart.redraw();
				update_map_fields();
		   }
		);
	}

	function AddDataToUtilityChart(data, chart, switchtype) {
		var datatableverbruikElecNet = [];
		var datatableverbruikSolar = [];
		var datatableSolarElecNet = [];
		var datatableSolarVerbruik = [];
		var datatableTotalUsage = [];
		var datatableTotalReturn = [];
		var valueUnits = "";
		var length = data.length;
		$.each(data, function (i, item) {
			var cdate = GetDateFromString(item.idate);
			var prod = parseFloat(item.prod);  //Solar productie
			var v1 = parseFloat(item.v1);      // verbruik hoog
			var v2 = parseFloat(item.v2);      // verbruik laag
			var r1 = parseFloat(item.r1);      // return hoog
			var r2 = parseFloat(item.r2);      // return laag
			var ve = v1 + v2;
			var vs = prod - r1 - r2;
			var se = r1 + r2;
			var sv = vs;
			datatableverbruikElecNet.push([cdate, ve]);
			datatableverbruikSolar.push([cdate, vs]);
			datatableSolarElecNet.push([cdate, se]);
			datatableSolarVerbruik.push([cdate, sv]);
			if (chart.renderTo.id == "daygraph") {
			pve = ve;
			pvs = vs;
			pse = se;
			psv = sv;
			}
		});

		var series;
		//Electra Usage/Return
		var totDecimals = 3;
		if (datatableSolarElecNet.length > 0) {
			series = chart.get('SolarElecNet');
			series.setData(datatableSolarElecNet, false);
		}
		if (datatableSolarVerbruik.length > 0) {
			series = chart.get('SolarVerbruik');
			series.setData(datatableSolarVerbruik, false);
		}
		if (datatableverbruikElecNet.length > 0) {
			series = chart.get('verbruikElecNet');
			series.setData(datatableverbruikElecNet, false);
		}
		if (datatableverbruikSolar.length > 0) {
			series = chart.get('verbruikSolar');
			series.setData(datatableverbruikSolar, false);
		}
	}
	//
	function AddSeriestoChart(chart, switchtype) {
		totDecimals = 0;
		chart.addSeries({
			id: 'SolarElecNet',
			type: 'area',
			name: 'Solar Retour <?php echo $ElecLeverancier?>',
			dataLabels: {
				enabled: false,
				color: 'green',
				formatter: function () {
						return Highcharts.numberFormat(this.point.stackTotal,1);
					}

			},
			tooltip: {
				valueSuffix: (chart.subtitle.textStr != 'Last Day') ? ' kWh' : ' Watt',
				valueDecimals: totDecimals
			},
			color: 'rgba(30,242,110,1)',
			stack: 'sreturn',
			yAxis: (chart.subtitle.textStr != 'Last Day') ? 0 : 1
		}, false);

		chart.addSeries({
			id: 'SolarVerbruik',
			type: 'area',
			name: 'Solar verbruik',
			dataLabels: {
				enabled: false,
				color: 'green',
				formatter: function () {
						return Highcharts.numberFormat(this.y,1);
					}
			},
			showInLegend: false,
			tooltip: {
				valueSuffix: (chart.subtitle.textStr != 'Last Day') ? ' kWh' : ' Watt',
				valueDecimals: totDecimals
			},
			color: 'rgba(3,222,190,1)',
			stack: 'sreturn',
			yAxis: (chart.subtitle.textStr != 'Last Day') ? 0 : 1
		}, false);

		chart.addSeries({
			id: 'verbruikElecNet',
			name: 'Verbruik <?php echo $ElecLeverancier?>',
			dataLabels: {
				enabled: true,
				inside: false,
				align: 'center',
				crop: false,
				overflow: 'none',
				verticalalign: 'top',
				color: 'red',
				y: -2,
				rotation: 0,
				formatter: function () {
					var color = "red";
					var s = '';
					if (this.series.chart.series["0"].stackedYData[this.point.index] > 0) {
						var diff = this.series.chart.series["2"].stackedYData[this.point.index] - this.series.chart.series["0"].stackedYData[this.point.index];
						if (this.series.chart.series["0"].stackedYData[this.point.index] > this.series.chart.series["2"].stackedYData[this.point.index]) {
							color = "green";
					}
						s += '<span style="font-size:smaller; color:' + color + ';">' + Highcharts.numberFormat(diff,0) + '</span><br>';
					}
					s += '<span style="font-size:smaller; color: black;">' + Highcharts.numberFormat(this.point.stackTotal,0) + '</span>';
					return s ;
				}
			},
			tooltip: {
				valueSuffix: (chart.subtitle.textStr != 'Last Day') ? ' kWh' : ' Watt',
				valueDecimals: totDecimals
			},
			color: 'rgba(60,130,252,0.5)',
			stack: 'susage',
			yAxis: (chart.subtitle.textStr != 'Last Day') ? 0 : 1
		}, false);

		chart.addSeries({
			id: 'verbruikSolar',
			name: 'Verbruik Solar',
			dataLabels: {
				enabled: false,
			},
			tooltip: {
				valueSuffix: (chart.subtitle.textStr != 'Last Day') ? ' kWh' : ' Watt',
				valueDecimals: totDecimals
			},
			color: 'rgba(3,190,252,0.5)',
			stack: 'susage',
			yAxis: (chart.subtitle.textStr != 'Last Day') ? 0 : 1
		}, false);
	}

	function GetDateFromString(s) {
			var year = 1;
			var month = 1;
			var week = 0;
			var day = 1;
			if (s.length > 3) {
				year = parseInt(s.substring(0, 4), 10);
			}
			if (s.length = 6) {
				week = parseInt(s.substring(0, 4), 10);
			}
			if (s.length > 6) {
				month = parseInt(s.substring(5, 7), 10);
			}
			if (s.length > 8) {
				day = parseInt(s.substring(8, 10), 10);
			}
		return Date.UTC(year,month - 1,day);
	}
</script>
</html>
