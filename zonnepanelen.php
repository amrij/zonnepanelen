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

versie: 1.64
auteur: André Rijkeboer
datum:  30-03-2019
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
	<link href='css/zonnepanelen.css' rel='stylesheet' type='text/css'/>
	<script src="js/jquery.plugin.js"></script>
	<script src="js/jquery.mousewheel.js"></script>
	<script src="js/jquery.calendars.js"></script>
	<script src="js/jquery.calendars.plus.js"></script>
	<script src="js/jquery.calendars.picker.js"></script>
	<script src="js/jquery.calendars.picker.ext.js"></script>
	<script src="js/jquery.calendars.validation.js"></script>
	<?php
		include('config.php');
		// Added to check settings for SQL database server and report when error occures
		$mysqli = new mysqli($host, $user, $passwd, $db, $port);
		if (mysqli_connect_errno())
		{
			echo "<div style=background-color:Red;color:white;>";
			echo "<p>We found a problem connecting to the SQL database ".$host.":".$port." db:".$db."<br>";
			echo "Error: ".mysqli_connect_error()."</p>";
			echo "The website can't be shown until this issue is fixed.</p>";
			echo "</div>";
			exit();
		}
		$thread_id = $mysqli->thread_id;
		$mysqli->kill($thread_id);
		$mysqli->close();
		// end SQL database check
		if ($aantal < 0) { $aantal = 0;}
		for ($i=1; $i<=$aantal; $i++){
			if ($op_id[$i][2] == 1){$pro[$i] =  "6%"; $top[$i] = "65%";}
			else                   {$pro[$i] = "20%"; $top[$i] = "78%";}
		}
		$mysqli = new mysqli($host, $user, $passwd, $db, $port);
		$query = "SELECT timestamp FROM telemetry_optimizers ORDER BY timestamp LIMIT 1";
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
		$ds = $_GET['ds'];
		setlocale(LC_ALL, 'nl_NL');
		if($date == ''){
			$date = date("d-m-Y H:i:s", time());
		}
		for ($i=0; $i<=14; $i++){
			$productie[$i] = $week[date("N", strtotime($date)-$i*86400)].date("d-m-Y", strtotime($date)-$i*86400);
		}
		$today = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp();
		$winter = date("I",$today)-1;
		$jaar = date("Y",$today);
		$maand = date("m",$today)-1;
		$dag = date("d",$today)-1;
		$datum1 = (new DateTime(sprintf("today %s",date("Y-m-d 00:00:00", time()))))->getTimestamp();
		$datumz = date("d-m-Y H:i:s",$today);
		$tomorrow = (new DateTime(sprintf("tomorrow %s",date("Y-m-d 00:00:00", strtotime($date)))))->getTimestamp();
		$date3 = date("Y-m-d", time());
		$datev = date("d-m-Y", strtotime($date));
		$a = strptime($date, '%d-%m-%Y %H:%M:%S');
		if ($a['tm_year']+1900 < 2000){
			$a = strptime($date, '%Y-%m-%d');
		}
		$a = mktime(0,0,0,$a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
		$date2 = strftime('%Y-%m-%d', $a);
		$datum = $today/86400;
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
			if ($i==0)        {$s = $solar_noon_a-$ha_sunrise*4/1440;} // Sunrise
			else if ($i == 2) {$s = $solar_noon_a+$ha_sunrise*4/1440;} // Sunset
			else              {$s = $solar_noon_a;}
			return $s;
		}
		function genxAxis() {
			print "
					type: 'datetime',
					pointstart: Date.UTC(1970,01,01),
					maxZoom: 9000 * 1000, // 600 seconds = 10 minutes
					title: { text: null },
					startOnTick: true,
					minPadding: 0,
					maxPadding: 0,
					labels: { overflow: 'justify' },
					tooltip: { enabled: true, crosshair: true },
					plotBands: [\n";
			for ($i = 0; $i < 25; $i += 2) {  print "			{
					color: '#ebfbff',
					from: Date.UTC(jaar, maand , dag, u[" . $i . "]-winter),
					to: Date.UTC(jaar, maand, dag, u[" . ($i+1) . "]-winter),
				},\n";
			}
			print "],\n";
		}
		function productieSeries() {
			print "
					series: [\n";
			for ($i=0; $i<=13; $i++) {  print "			{
						name: productie[" . $i . "],
						showInLegend: false,
						type: 'spline',
						yAxis: 0,
						color: '#d4d0d0',
						data: []//this will be filled by requestData()
					},";
			}
			print "
					{
						name: productie[14],
						showInLegend: true,
						type: 'spline',
						yAxis: 0,
						lineWidth: 2,
						color: '#009900',
						data: []//this will be filled by requestData()
					}],\n";
		}

		function e_panelen($aantal) {
			print "
					series: [\n";
			for ($i=$aantal; $i>=2; $i--) {  print "			{
					name: 'Paneel_" . $i . "',
					showInLegend: false,
					type: 'spline',
					yAxis: 0,
					color: '#d4d0d0',
					data: []//this will be filled by requestData()
				},";
			}
			print "
				{
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
				}],\n";
		}
	?>
</head>
<body>
	<div class='mainpage'>
		<div class='container'>
			<div id='container'></div>
				<div Class='power_chart_body' id='power_chart_body' style="top: 42.2%; left: 12.35%; z-index: 3; width: 86.8108%; height: 56.4%; position: absolute;"></div>
				<div Class='power_chart_paneel' id='power_chart_paneel' style="top: 0.26%; left: 54.05%; z-index: 3; width: 45.1%; height: 41.5%; position: absolute;"></div>
				<div Class='power_chart_inverter' id='power_chart_inverter' style="top: 0.26%; left: 54.05%; z-index: 4; width: 45.15%; height: 41.5%; position: absolute;"></div>
				<div Class='power_chart_vermogen' id='power_chart_vermogen' style="top: 0.26%; left: 54.05%; z-index: 3; width: 45.15%; height: 41.5%; position: absolute;"></div>
				<div Class='datum' id='datum' style="top: .3vw; left: 1vw; z-index: 3; width: 14vw; height: 1.4vw; position: absolute;">
					<input type="button" id="PrevDay" class="btn btn-success btn-sm" value="<">
					<input type="text" id="multiShowPicker" class="embed" size="8.5" style="font-size: .6vw; text-align:center;">
					<input type="button" id="NextDay" class="btn btn-success btn-sm"  value=">">
				</div>
				<div class="imageOver">
					<img src="./img/<?php echo $zonnesysteem;?>" alt=""  style="position:absolute; top: 0px; left: 0px; width: 100%; height: 100%; z-index: -100;"/>
				</div>
				<img src="./img/dummy.gif" style="top: 1.59%; left: 21.24%; z-index: 10; width: 3.19%; height: 11.93%; position: absolute;" usemap="#inverter"/>
				<map name="inverter" style="z-index: 20;">
					<area id="inverter_1" shape="rect" coords="0,0,100%,100%" title="" onmouseover="vermogenChart()" onmouseout="vermogenChartcl()">
				</map>
				<div class='inverter_text' id='inverter_text' style="top: 1.75%; left: 25%; z-index: 10; width: 10%; height: 12,75%; font-size: .5vw;line-height: 120%; position: absolute;"></div>
				<img src="./img/dummy.gif" style="top: 28.18%; left: 29.95%; z-index: 10; width: 3,62%; height: 11.36%; position: absolute;" usemap="#meter"/>
				<div  class="" id="arrow_PRD" style="top: 14.3%; left: 29.46%; width: 0.01%; height: 0.5% ; z-index: 20; position: absolute;"></div>
				<map name="meter" style="z-index: 20;">
					<area id="meter_1" shape="rect" coords="0,0,67,100" title="P1_Meter">
				</map>
				<div class='sunrise_text' id='sunrise_text' style="top: 25.1%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<img src="./img/zon/sunrise.gif" style="top: 23.5%; left: 44.3%; z-index: 10; width: 2.81%; height: 3.07%; position: absolute;" />
				<div class='solar_noon_text' id='solar_noon_text' style="top: 27.4%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<img src="./img/zon/solar_noon.gif" style="top: 25.8%; left: 44.3%; z-index: 10; width: 2.81%; height: 3.07%; position: absolute;" />
				<div class='sunset_text' id='sunset_text' style="top: 29.7%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<img src="./img/zon/sunset.gif" style="top: 28.1%; left: 44.3%; z-index: 10; width: 2.81%; height: 3.07%; position: absolute;" />
				<div class='daglengte_text' id='daglengte_text' style="top: 32.0%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<img src="./img/zon/daglengte.gif" style="top: 30.4%; left: 44.3%; z-index: 10; width: 2.81%; height: 3.07%; position: absolute;" />
				<img src="./img/maan/maan_th_mask1.gif" style="top: 34.2%; left: 44.75%; z-index: 20; width: 2.4%; position: absolute;" />
				<img class="maan_th" id="maan_th" src="" style="top: 34.35%; left: 44.75%; z-index: 10; width: 2.4%; position: absolute;"></img>
				<div class='fase_text' id='fase_text' style="top: 36%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<div class='verlicht_text' id='verlicht_text' style="top: 37.2%; left: 47.65%; z-index: 10; width: 7.84%; height: 4.09%; font-size: .6vw;line-height: 1.1em; position: absolute;"></div>
				<div Class='box_Zonnepanelen' id='box_Zonnepanelen'>
<?php
			for ($i=1; $i<=$aantal; $i++){
				echo '                     <div class="box_Zonnepaneel_'.$i.'" id="box_Zonnepaneel_'.$i.'">'."\n";
				echo '                         <div class="text_paneel_W" id="text_paneel_W_'.$i.'" style="z-index: 10; color: white; top: '.$pro[$i].'; width: 100%; font: arial; font-weight: bold; font-size: .6vw; text-align: center; position: absolute;"></div>'."\n";
				echo '                         <div class="text_paneel_W" id="text_paneel_W_'.$i.'a" style="z-index: 10; color: white; top: 36%; width: 100%; font: arial; font-weight: bold; font-size: .6vw; text-align: center; position: absolute;"></div>'."\n";
				echo '                         <img  id="image_'.$i.'" src="./img/dummy.gif" alt="" width="100%" height="100%" style="witdh: 0%; height: 100%; position:relative; z-index: 5;"/></div>'."\n";
				echo '                         <div class="box_Zonnepaneel_'.$i.'">'."\n";
				echo '                              <img src="./img/dummy.gif" alt="" width="100%" Height="100%" style=" position: relative; z-index: 15;" usemap="#'.$i.'">'."\n";
				echo '                         	    <map name="'.$i.'">'."\n";
				echo '                                  <area id="tool_paneel_'.$i.'" shape="rect" coords="0,0,100%,100%" title="" onmouseover="paneelChart(event,'.$i.')" onmouseout="paneelChartcl()">'."\n";
				echo '                              </map>'."\n";
				echo '                         <div class="text_Zonnepaneel" id="text_Zonnepaneel_'.$i.'" style="z-index: 10; color: white; top: '.$top[$i].'; width: 100%; font: arial; font-weight: bold; font-size: .6vw; text-align: center; position: absolute;"></div></div>'."\n";
			}
?>
			</div>
		</div>
	</div>
</body>
<script language="javascript" type="text/javascript">
	function toonDatum(datum) {
		var now = new Date();
		var yy = now.getYear()+1900;
		var mm = now.getMonth()+1;
		var dd = now.getDate();
		mm = "0"+mm;
		mm = mm.slice(-2);
		dd = "0"+dd;
		dd = dd.slice(-2);
		var tdatum =  yy + "-" + mm + "-" + dd;
		url =  window.location.pathname;
		if(tdatum!=datum) {
			url = url + '?date=';
			url = url + datum;
			url = url + ' 00:00:00&ds=1';
		}
		window.location.replace(url);//do something after you receive the result
	}

	var ds = '<?php echo $ds ?>';
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
	var op_sn = [0,'<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][0], "','";} ?>'];
	var pn_sn = [0,'<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][3], "','";} ?>'];
	var op_id = [0,'<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][1], "','";} ?>'];
	var rpan  = [0,'<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][2], "','";} ?>'];
	var vpan  = [0,<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][4], ",";} ?>];
	var u = [22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47];
	var data_p = [];
	var data_i = [];
	var chart_1 = "power_chart_paneel";
	var chart_2 = "power_chart_inverter";
	var productie = [<?php echo "'$productie[14]','$productie[13]','$productie[12]','$productie[11]','$productie[10]','$productie[9]','$productie[8]','$productie[7]','$productie[6]','$productie[5]','$productie[4]','$productie[3]','$productie[2]','voorafgaande dagen','$productie[0]','$productie[1]'"?>];
	var start_i = 0;
	var inverter_redraw = 1;


	google.charts.load('current', {'packages':['gauge', 'line']});
	google.charts.setOnLoadCallback(drawChart);
	function drawChart() {
		zonmaan();
		paneel();
		document.getElementById("power_chart_paneel").innerHTML ="";
		document.getElementById("power_chart_vermogen").innerHTML ="";
		document.getElementById("sunrise_text").innerHTML = sunrise+" uur";
		document.getElementById("solar_noon_text").innerHTML = solar_noon+" uur";
		document.getElementById("sunset_text").innerHTML = sunset+" uur";
		document.getElementById("daglengte_text").innerHTML = daglengte+" uur";
		setInterval(function() {
			var now = new Date();
			if (ds == '' && tomorrow < now/1000) {
				window.location = window.location.pathname;
				return false;
			}
			zonmaan();
			paneel();
		}, 60000);
	}
	var paneelGraph = {
			'Vermogen':     { 'metric': 'p1_current_power_prd', 'tekst': 'Vermogen',    'unit': 'W' },
			'Energie':      { 'metric': 'p1_volume_prd',        'tekst': 'Energie',     'unit': 'Wh' },
			'Temperatuur':  { 'metric': 'temperature',          'tekst': 'Temperatuur', 'unit': '°C' },
			'V_in':         { 'metric': 'vin',                  'tekst': 'Spanning In', 'unit': 'V' },
			'V_out':        { 'metric': 'vout',                 'tekst': 'Spanning In', 'unit': 'V' },
			'I_in':         { 'metric': 'iin',                  'tekst': 'Stroom In',   'unit': 'A' }
		};
	function paneelFillSeries(metric, shift, x) {
		for (var i = 0; i < data_p.length; i++){
			if (data_p[i]['op_id'] !== x && data_p[i]['serie'] == 0){
				var sIdx = data_p[i]['op_id'] - 1;
				if (data_p[i]['op_id'] > x ){ --sIdx; }
				paneel_chart.series[sIdx].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i][paneelGraph[metric]['metric']]*1], false, shift);
			} else {
				paneel_chart.series[aantal].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i][paneelGraph[metric]['metric']]*1], false, shift);
			}
		}
		paneel_chart.setTitle(null, { text: 'Paneel: '+op_id[x]+' en alle andere panelen', x: 20});
		paneel_chart.legend.update({x:10,y:20});
		paneel_chart.series[aantal].update({name: paneelGraph[metric]['tekst'] + " paneel: "+op_id[x], style: {font: 'Arial', fontWeight: 'bold', fontSize: '12px' }});
		paneel_chart.series[aantal-1].update({showInLegend: false});
		paneel_chart.series[aantal-2].update({showInLegend: true, name: paneelGraph[metric]['tekst'] + " overige panelen"});
		paneel_chart.yAxis[0].update({ opposite: true });
		paneel_chart.yAxis[0].update({ title: { text: paneelGraph[metric]['tekst'] + ' (' + paneelGraph[metric]['unit'] + ')' }, });
		paneel_chart.yAxis[1].update({ labels: { enabled: false }, title: { text: null } });
	}
	function paneelChart(event,x) {
		if (x <= aantal){
			inverter_redraw = 0;
			document.getElementById("power_chart_inverter").innerHTML ="";
			var series = paneel_chart.series[0];
			var shift = series.data.length > 86400; // shift if the series is longer than 86400(=1 dag)
			if (event.ctrlKey) {
				paneelFillSeries('Vermogen', shift, x);
			} else if (event.altKey) {
				paneelFillSeries('Energie', shift, x);
			} else if (event.shiftKey) {
				paneelFillSeries('Temperatuur', shift, x);
			} else {
				for(var i = 0; i < data_p.length; i++){
					if (data_p[i]['op_id'] == x && data_p[i]['serie'] == 0){
						paneel_chart.series[aantal-1].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i]['p1_volume_prd']*1], false, shift);
						paneel_chart.series[aantal].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i]['p1_current_power_prd']*1], false, shift);
					}
				}
				paneel_chart.legend.update({x:50,y:20});
				paneel_chart.series[aantal-2].update({showInLegend: false});
				paneel_chart.series[aantal-1].update({name: "Energie"});
				paneel_chart.series[aantal-1].update({showInLegend: true});
				paneel_chart.series[aantal].update({name: "Vermogen"});
				paneel_chart.setTitle(null, { text: 'Paneel: '+op_id[x], x: 55, style: {font: 'Arial', fontWeight: 'bold', fontSize: '12px' }});
				paneel_chart.yAxis[0].update({ opposite: false });
				paneel_chart.yAxis[1].update({ labels: { enabled: true }, title: { text: 'Energie (Wh)' } });
			}
			paneel_chart.redraw();
		}
	}

	function paneelChartcl() {
		inverter_redraw = 1;
		for (var i=0; i<=aantal; i++){
			paneel_chart.series[i].setData([]);
		}
		document.getElementById("power_chart_paneel").innerHTML ="";
		document.getElementById("power_chart_inverter").innerHTML ="";
		document.getElementById("power_chart_vermogen").innerHTML ="";
		inverter_chart.redraw();
	}

	function vermogenChart() {
		inverter_redraw = 0;
		document.getElementById("power_chart_inverter").innerHTML ="";
		document.getElementById("power_chart_paneel").innerHTML ="";
		document.getElementById("power_chart_vermogen").innerHTML ="";
		vermogen_chart.redraw();
	}

	function vermogenChartcl() {
		inverter_redraw = 1;
		document.getElementById("power_chart_paneel").innerHTML ="";
		document.getElementById("power_chart_inverter").innerHTML ="";
		document.getElementById("power_chart_vermogen").innerHTML ="";
		inverter_chart.redraw();
	}
	function waarde(l,d,x){
		s = String(x);
		n = s.indexOf('-');
		if ( n==0) { s=s.slice(1,s.length);}
		if ( s.indexOf('.') <0 ) { s = s + ".";}
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
		if (datum1 < tomorrow) {
			if(inv1Data[0]["IVACT"] != 0){
				document.getElementById("arrow_PRD").className = "arrow_right_green";
			}else{
				document.getElementById("arrow_PRD").className = "";
			}
			document.getElementById("inverter_text").innerHTML = "<b>Inverter:</b><br>Datum:&emsp;" + inv1Data[0]["IT"]
									   + "<br>Mode:&emsp;&nbsp;&nbsp;"      + inv1Data[0]["MODE"]
									   + "<br>Pcurrent:&nbsp;"              + inv1Data[0]["IVACT"]
									   + " W<br>Pmax:&nbsp;&nbsp;&nbsp;"    + inv1Data[0]["IVMAX"]
									   + " W<br><b>Etot:&emsp;&nbsp;&nbsp;" + waarde(0,3,inv1Data[0]["IE"])
									   + " kWh</b><br>T:&emsp;&emsp;&emsp;" + waarde(0,1,inv1Data[0]["ITACT"])
									   + " °C<br>Tmin:&emsp;&nbsp;"         + waarde(0,1,inv1Data[0]["ITMIN"])
									   + " °C<br>Tmax:&nbsp;&nbsp;&nbsp;"   + waarde(0,1,inv1Data[0]["ITMAX"])+" °C";
		}else{
			document.getElementById("inverter_text").innerHTML = "<b>Inverter:</b><br>Datum:&emsp;" + inv1Data[0]["IT"]
									   + "<br>Pmax:&emsp;&nbsp;"            + inv1Data[0]["IVMAX"]
									   + " W<br><b>Etot:&emsp;&emsp;"       + waarde(0,3,inv1Data[0]["IE"])
									   + " kWh</b><br>Tmin:&emsp;&emsp;"    + waarde(0,1,inv1Data[0]["ITMIN"])
									   + " °C<br>Tmax:&emsp;&nbsp;"         + waarde(0,1,inv1Data[0]["ITMAX"])+" °C";
			document.getElementById("arrow_PRD").className = "";
		}
		var tverm = 0;
		for (i=1; i<=aantal; i++) {
			tverm += vpan[i];
		}
		if (inverter == 1){
			document.getElementById("inverter_1").title = "Inverter: "+naam
								    + "\r\n\r\nS AC:	"   + inv1Data[0]["i_ac"]
								    + " A\r\nV AC:	"   + inv1Data[0]["v_ac"]
								    + " V\r\nFreqentie:"    + inv1Data[0]["frequency"]
								    + " Hz\r\nPactive:	"   + inv1Data[0]["p_active"]
								    + " kWh\r\nV DC:	"   + inv1Data[0]["v_dc"]
								    + " V\r\nE:		"   + inv1Data[0]["IE"]
								    + " kWh\r\nP(act):	"   + inv1Data[0]["IVACT"]
								    + " W\r\nEfficientie: " + waarde(0,3,(inv1Data[0]["IE"]*1000/tverm));
		}else{
			document.getElementById("inverter_1").title = "Inverter: "+naam
								    + "\r\n\r\n	L1	L2	L3\r\nS AC:	" +inv1Data[0]["i_ac1"]+"	"+inv1Data[0]["i_ac2"]+"	"+inv1Data[0]["i_ac3"]
								    + " A\r\nV AC:	"   + inv1Data[0]["v_ac1"]+"	"+inv1Data[0]["v_ac2"]+"	"+inv1Data[0]["v_ac3"]
								    + " V\r\nFre:	"   + inv1Data[0]["frequency1"]+"	"+inv1Data[0]["frequency2"]+"	"+inv1Data[0]["frequency3"]
								    + " Hz\r\nPactive:	"   + inv1Data[0]["p_active1"]+"	"+inv1Data[0]["p_active2"]+"	"+inv1Data[0]["p_active3"]
								    + " W\r\nV DC:	"   + inv1Data[0]["v_dc"]
								    + " V\r\nE:		"   + inv1Data[0]["IE"]
								    + " kWh\r\nP(act):	"   + inv1Data[0]["IVACT"]
								    + " W\r\nEfficientie: " + waarde(0,3,(inv1Data[0]["IE"]*1000/tverm));
		}
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
			document.getElementById("tool_paneel_"+i).title = inv1Data[0]["TM"+i]+"\r\nPaneel "+op_id[i]
									+ "\r\nOptimizer SN	"+op_sn[i]
									+ "\r\nPaneel SN	"+pn_sn[i]
									+ "\r\nEnergie		"+ inv1Data[0]["O"+i]
									+ " Wh\r\nVermogen (act.)	"+ inv1Data[0]["E"+i]
									+ " W\r\nVermogen (max.)	"+ inv1Data[0]["VM"+i]
									+ " W\r\nVermogen (max.)	"+ inv1Data[0]["VMT"+i]
									+ "\r\nStroom in	"+ inv1Data[0]["S"+i]
									+ " A\r\nSpanning in	"+ inv1Data[0]["VI"+i]
									+ " V\r\nSpanning uit	"+ inv1Data[0]["VU"+i]
									+ " V\r\nTemperatuur	"+ inv1Data[0]["T"+i]
									+ " °C\r\nEfficientie        "+ waarde(0,3,(inv1Data[0]["O"+i]/vpan[i]));

			if      ( inv1Data[0]["C"+i] == 0)  { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#000000"; }
			else if ( inv1Data[0]["C"+i] < 0.1) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#080f16"; }
			else if ( inv1Data[0]["C"+i] < 0.2) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#101e2d"; }
			else if ( inv1Data[0]["C"+i] < 0.3) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#182e44"; }
			else if ( inv1Data[0]["C"+i] < 0.4) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#203d5a"; }
			else if ( inv1Data[0]["C"+i] < 0.5) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#294d71"; }
			else if ( inv1Data[0]["C"+i] < 0.6) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#315c88"; }
			else if ( inv1Data[0]["C"+i] < 0.7) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#396b9e"; }
			else if ( inv1Data[0]["C"+i] < 0.8) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#417bb5"; }
			else if ( inv1Data[0]["C"+i] < 0.9) { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#498acc"; }
			else                                { document.getElementById("box_Zonnepaneel_"+i).style.backgroundColor =  "#529ae3"; }
		}
	}

	function zonmaan(){
		if (date2 >= date3){
			document.getElementById("NextDay").disabled = true;
		}else{
			document.getElementById("NextDay").disabled = false;
		}
		if (date2 <= begin){
			document.getElementById("PrevDay").disabled = true;
		}
		if (datum1 < tomorrow) {
			datumz = "";
		}
		var inv4Data = $.ajax({
			url: "maanfase.php",
			dataType: "json",
			type: 'GET',
			data: { "date" : datumz.replace("00:00:00", `${(new Date()).getHours()}:00:00`) },
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
							vermogen_chart.series[14-data_i[i]['serie']].addPoint([Date.UTC(data_i[i]['jaar'],data_i[i]['maand'],data_i[i]['dag'],data_i[i]['uur'],data_i[i]['minuut'],data_i[i]['sec']),data_i[i]['p1_current_power_prd']*1], false, shift);
						}
					}
					if(inverter_redraw == 1) {inverter_chart.redraw();} // default is energie chart
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
			power_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: 'power_chart_body',
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'x',
					events: {load: requestData1}
				},
				title: {
				   text: null
				},
				subtitle: {
					text: "Vermogen en energie op <?php echo $datev;?>",
					align: 'left',
					x: 90,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
						fontSize: '.85vw'
					},
					floating: true
				},
				xAxis: [{ <?php genxAxis(); ?> }],
				yAxis: [{
					title: {
						text: 'Vermogen (W)'
					},
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
						fontSize: '.7vw'
					},
					layout: 'vertical',
					align: 'left',
					x: 80,
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
							if (this.series.name == 'Energie') {
								s += '<br/>' + this.series.name + ': ' +
								this.y + ' kWh';
							}
							if (this.series.name == 'Vermogen') {
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
					name: 'Energie',
					type: 'areaspline',
					marker: {
						symbol: 'triangle'
					},
					yAxis: 1,
					lineWidth: 1,
					color: 'rgba(204,255,153,1)',
					pointWidth: 2,
					data: []//this will be filled by requestData()
				},{
					name: 'Vermogen',
					type: 'spline',
					yAxis: 0,
					color: '#009900',
					data: []//this will be filled by requestData()
				}]
			});
		});
		$(document).ready(function() {
			paneel_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: chart_1,
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'x',
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
						fontSize: '.85vw'
					},
					floating: true
				},
				xAxis: [{ <?php genxAxis(); ?> }],
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
						fontSize: '.7vw'
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
				<?php e_panelen($aantal); ?>
			});
		});
		$(document).ready(function() {
			inverter_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: "power_chart_inverter",
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'x',
					events: {load: requestDatai},
					spacingRight: 20
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
						fontSize: '.85vw'
					},
					floating: true
				},
				xAxis: [{ <?php genxAxis(); ?> }],
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
						fontSize: '.7vw'
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
									//	s += "<b>" + this.series.name.substr(this.series.name.length - 10, 5) + Highcharts.dateFormat(' %H:%M', this.x)+ ': ' + Highcharts.numberFormat(this.y,2) + ' kWh</b>';
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
				<?php productieSeries() ?>
			});
		});
		$(document).ready(function() {
			vermogen_chart = new Highcharts.Chart({
				chart: {
					type: 'area',
					renderTo: "power_chart_vermogen",
					spacingTop: 10,
					borderColor: 'grey',
					borderWidth: 1,
					borderRadius: 5,
					alignTicks:true,
					spacingBottom: 0,
					zoomType: 'x',
					spacingRight: 20,
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
						fontSize: '.85vw'
					},
					floating: true
				},
				xAxis: [{ <?php genxAxis(); ?> }],
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
						fontSize: '.7vw'
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
				<?php productieSeries() ?>
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
</script>
</html>
