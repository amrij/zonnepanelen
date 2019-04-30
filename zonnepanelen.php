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
based on versie: 1.64 of zonnepanelen.php
versie: 1.64.3
auteur: Jos van der Zande  based on the zonnepanelen.php model from André Rijkeboer
datum:  15-04-2019
omschrijving: hoofdprogramma
-->
<html>
<head>
	<title>Zonnepanelen-p1</title>
	<link rel="shortcut icon" href="./img/sun.ico" type="image/x-icon"/>
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
	<link href='css/zonnepanelen-local.css' rel='stylesheet' type='text/css'/>
	<script src="js/jquery.plugin.js"></script>
	<script src="js/jquery.mousewheel.js"></script>
	<script src="js/jquery.calendars.js"></script>
	<script src="js/jquery.calendars.plus.js"></script>
	<script src="js/jquery.calendars.picker.js"></script>
	<script src="js/jquery.calendars.picker.ext.js"></script>
	<script src="js/jquery.calendars.validation.js"></script>
	<?php
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			include('general_functions.php');
		}
		include('config.php');
		$mysqli = new mysqli($host, $user, $passwd, $db, $port);
		if (mysqli_connect_errno()) {
			//Bail out if database connection fails
			echo "<div style=background-color:Red;color:white;>";
			echo "<p>We found a problem connecting to the SQL database ".$host.":".$port." db:".$db."<br>";
			echo "Error: ".mysqli_connect_error()."</p>";
			echo "The website can't be shown until this issue is fixed.</p>";
			echo "</div>";
			exit();
		}
		// end SQL database check
		$query = "SELECT min(timestamp) as timestamp FROM telemetry_optimizers";
		$result = $mysqli->query($query);
		$row = mysqli_fetch_assoc($result);
		$begin = gmdate("Y-m-d",$row['timestamp']);
		$thread_id = $mysqli->thread_id;
		$mysqli->kill($thread_id);
		$mysqli->close();
		if ($aantal < 0) { $aantal = 0;}
		for ($i=1; $i<=$aantal; $i++){
			if ($op_id[$i][2] == 1){$pro[$i] =  "6%"; $top[$i] = "65%";}
			else                   {$pro[$i] = "20%"; $top[$i] = "78%";}
		}
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
		if ($date == '') { $date = date("d-m-Y H:i:s", time()); }
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
		<div class='container' id='container'>
			<div Class='box_inverter' id='box_inverter'>
				<img src="./img/<?php echo $zonnesysteem;?>" alt=""  style="position:absolute; top: 0px; left: 0px; width: 100%; height: 100%; z-index: -100;"/>
				<div class='datum' id='datum' >
					<input type="button" id="PrevDay" class="btn btn-success btn-sm" value="<" title="Vorige dag">
					<input type="text" id="multiShowPicker" class="embed" size="8.5" style="text-align:center;">
					<input type="button" id="NextDay" class="btn btn-success btn-sm"  value=">" title="Volgende dag">
					<input type="button" id="Today" class="btn btn-success btn-sm" value=">|" title="Vandaag">
				</div>

				<div class='map_inverter' id='map_inverter'>
					<img src="./img/dummy.gif" style="width:100%; height:100%" usemap="#inverter"/>
				</div>
				<map name="inverter" style="z-index: 20;">
					<area id="inverter_1" shape="rect" coords="0,0,100%,100%" title="">
				</map>
				<div class="inverter_text" id="inverter_text"></div>
				<div class="sola_text" id="sola_text"></div>
				<div class="arrow_prd_pos"><div id="arrow_PRD"></div></div>
				<div class="so_text"><div id="so_text"></div></div>
				<div class="arrow_return_pos"><div id="arrow_RETURN"></div></div>
				<div class="sum_text" id="sum_text"></div>
				<div class="elec_text" id="elec_text"></div>
				<div class="p1_text_pos"><div id="p1_text"></div></div>
				<div class="map_p1_meter" id="map_p1_meter">
					<img src="./img/dummy.gif" style="width:100%; height:100%" usemap="#meter"/>
				</div>
				<map name="meter" style="z-index: 20;">
					<area id="meter_1" shape="rect" coords="0,0,67,100" title="P1_Meter">
				</map>

				<div class="p1_huis_pos"><div id="p1_huis"></div></div>
				<div class="map_huis" id="map_huis">
					<img src="./img/dummy.gif" style="width:100%; height:100%" usemap="#huis"/>
				</div>
				<map name="huis" style="z-index: 20;">
					<area id="huis_1" shape="rect" coords="0,0,150,150" title="thuis verbruik">
				</map>
			</div>

			<div Class='box_panel_energy' id='box_panel_energy'>
				<div id="panel_energy" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
			<div Class='box_panel_vermogen' id='box_panel_vermogen'>
				<div id="panel_vermogen" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
			<div Class='box_chart_energy' id='box_chart_energy'>
				<div id="chart_energy" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
			<div Class='box_chart_vermogen' id='box_chart_vermogen'>
				<div id="chart_vermogen" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
<?php
if ($P1 == 1){
echo <<<EOF
			<div Class='box_daygraph' id='box_daygraph'>
				<div id="daygraph" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
			<div Class='box_monthgraph' id='box_monthgraph'>
				<div id="monthgraph" style="position: absolute; width: 100%; height: 100%;"></div>
			</div>
EOF
;
} else {
echo <<<EOF
			<div Class="power_chart_body" id="power_chart_body"></div>
EOF
;
}

?>
			<div Class="box_Zonnepanelen" id="box_Zonnepanelen">
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
		</div>
		<div Class='box_sunrise' id='box_sunrise'>
			<img src="./img/zon/sunrise.gif"                  style="top: .1%;   left: 3%;  z-index: 10; width: 32%; height: 15%; position: absolute;" />
			<div class='sunrise_text' id='sunrise_text'       style="top: 10.0%;   left: 40%; z-index: 10; width: 40%; height: 15%; line-height: 1.0em; position: absolute;"></div>

			<img src="./img/zon/solar_noon.gif"               style="top: 15.1%; left: 3%;  z-index: 10; width: 32%; height: 15%; position: absolute;" />
			<div class='solar_noon_text' id='solar_noon_text' style="top: 25.0%; left: 40%; z-index: 10; width: 40%; height: 15%; line-height: 1.0em; position: absolute;"></div>

			<img src="./img/zon/sunset.gif"                   style="top: 30.1%; left: 3%;  z-index: 10; width: 32%; height: 15%; position: absolute;" />
			<div class='sunset_text' id='sunset_text'         style="top: 40.0%; left: 40%; z-index: 10; width: 50%; height: 15%; line-height: 1.0em; position: absolute;"></div>

			<img src="./img/zon/daglengte.gif"                style="top: 45.1%; left: 3%;  z-index: 10; width: 32%; height: 15%; position: absolute;" />
			<div class='daglengte_text' id='daglengte_text'   style="top: 55.0%; left: 40%; z-index: 10; width: 40%; height: 15%; line-height: 1.0em; position: absolute;"></div>

			<img src="./img/maan/maan_th_mask1.gif"           style="top: 60.0%; left: 7%; z-index: 20; width: 25%; position: absolute;" />
			<img class="maan_th" id="maan_th" src=""          style="top: 60.6%; left: 7%; z-index: 10; width: 25%; position: absolute;" />
			<div class='fase_text' id='fase_text'             style="top: 69.0%; left: 40%; z-index: 10; width: 50%; height: 15%; line-height: 1.0em; position: absolute;"></div>
			<div class='verlicht_text' id='verlicht_text'     style="top: 82.0%; left: 40%; z-index: 10; width: 50%; height: 15%; line-height: 1.0em; position: absolute;"></div>
		</div>
	</div>
</body>
<script language="javascript" type="text/javascript">
	function toonDatum(datum) {
		var now = new Date();
		var yy = now.getFullYear();
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
	var P1 = '<?php echo $P1?>';
	var aantal = '<?php echo $aantal?>';
	var op_sn = [0,<?php for ($i=1; $i<=$aantal; $i++){ echo "'", $op_id[$i][0], "',";} ?>];
	var pn_sn = [0,<?php for ($i=1; $i<=$aantal; $i++){ echo "'", $op_id[$i][3], "',";} ?>];
	var op_id = [0,<?php for ($i=1; $i<=$aantal; $i++){ echo "'", $op_id[$i][1], "',";} ?>];
	var rpan =  [0,<?php for ($i=1; $i<=$aantal; $i++){ echo "'", $op_id[$i][2], "',";} ?>];
	var vpan =  [0,<?php for ($i=1; $i<=$aantal; $i++){ echo $op_id[$i][4], ",";} ?>];
	var PVGtxt = "<?php echo $PVGtxt; ?>";
	var PVGis = [0<?php for ($i=0; $i<=11; $i++){ echo ",", $PVGis[$i];} ?>];
	var u = [22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47];
	var data_p = [];
	var data_i = [];
	var chart_1 = "chart_energy";
	var chart_2 = "chart_vermogen";
	var productie = [<?php echo "'$productie[14]','$productie[13]','$productie[12]','$productie[11]','$productie[10]','$productie[9]','$productie[8]','$productie[7]','$productie[6]','$productie[5]','$productie[4]','$productie[3]','$productie[2]','voorafgaande dagen','$productie[0]','$productie[1]'"?>];
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
		zonmaan();
		paneel();
		if (P1 == 1){ draw_p1_chart();}
		if (datum1 < tomorrow) {
			setInterval(function() {
				var now = new Date();
				if (ds == '' && tomorrow < now/1000) {
					window.location = window.location.pathname;
					return false;
				}
				if (P1 == 1){p1_update();}
			}, 10000);
			setInterval(function() {
				zonmaan();
			}, 600000);
			setInterval(function() {
				paneel();
			}, 60000);
		}
	}
	var paneelGraph = {
			'Vermogen':     { 'metric': 'p1_current_power_prd', 'tekst': 'Vermogen',    'unit': 'W' },
			'Energie':      { 'metric': 'p1_volume_prd',        'tekst': 'Energie',     'unit': 'Wh' },
			'Temperatuur':  { 'metric': 'temperature',          'tekst': 'Temperatuur', 'unit': '°C' },
			'V_in':         { 'metric': 'vin',                  'tekst': 'Spanning In', 'unit': 'V' },
			'V_out':        { 'metric': 'vout',                 'tekst': 'Spanning In', 'unit': 'V' },
			'I_in':         { 'metric': 'iin',                  'tekst': 'Stroom In',   'unit': 'A' }
		};
	function paneelFillSeries(metric, shift, x, ichart) {
		for (var i = 0; i < data_p.length; i++){
			if (data_p[i]['op_id'] !== x && data_p[i]['serie'] == 0){
				var sIdx = data_p[i]['op_id'] - 1;
				if (data_p[i]['op_id'] > x ){ --sIdx; }
				ichart.series[sIdx].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i][paneelGraph[metric]['metric']]*1], false, shift);
			} else {
				ichart.series[aantal].addPoint([Date.UTC(data_p[i]['jaar'],data_p[i]['maand'],data_p[i]['dag'],data_p[i]['uur'],data_p[i]['minuut'],data_p[i]['sec']),data_p[i][paneelGraph[metric]['metric']]*1], false, shift);
			}
		}
		ichart.setTitle(null, { text: 'Paneel: '+op_id[x]+' en alle andere panelen', x: 20});
		ichart.legend.update({x:10,y:20});
		ichart.series[aantal].update({name: paneelGraph[metric]['tekst'] + " paneel: "+op_id[x], style: {font: 'Arial', fontWeight: 'bold', fontSize: '12px' }});
		ichart.series[aantal-1].update({showInLegend: false});
		ichart.series[aantal-2].update({showInLegend: true, name: paneelGraph[metric]['tekst'] + " overige panelen"});
		ichart.yAxis[0].update({ opposite: true });
		ichart.yAxis[0].update({ title: { text: paneelGraph[metric]['tekst'] + ' (' + paneelGraph[metric]['unit'] + ')' }, });
		ichart.yAxis[1].update({ labels: { enabled: false }, title: { text: null } });
	}
	function paneelChart(event,x) {
		if (x <= aantal){
			inverter_redraw = 0;
			document.getElementById("chart_vermogen").innerHTML ="";
			document.getElementById("chart_energy").innerHTML ="";
			// #### Vermogen  #####
			var series = paneel_chartv.series[0];
			var shift = series.data.length > 86400; // shift if the series is longer than 86400(=1 dag)
			paneelFillSeries('Energie', shift, x, paneel_charte);
			if (event.shiftKey) {
				paneelFillSeries('Temperatuur', shift, x, paneel_chartv);
			} else {
				paneelFillSeries('Vermogen', shift, x, paneel_chartv);
			}
		}
	}
	function paneelChartcl() {
		inverter_redraw = 1;
		document.getElementById("panel_vermogen").innerHTML ="";
		document.getElementById("panel_energy").innerHTML ="";
		for (var i=0; i<=aantal; i++) {
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
			if (P1 == 1){
				p1_update()
			}
			s_p1CounterToday = p1CounterToday;
			s_p1CounterDelivToday = p1CounterDelivToday;
		}
		var now = new Date();
		var datesol = new Date(inv1Data[0]["IT"]);
		var dsol = datesol.getDate();
		var msol = datesol.getMonth()+1;
		var ysol = datesol.getFullYear();
		var dayssol = daysInMonth(msol, ysol);
		var tnow =  new Date("1970-01-01 "+now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds());
		if ( inv1Data[0]["IT"] == null) {inv1Data[0]["IT"] = datumz;}
		var tlast = new Date("1970-01-01 "+inv1Data[0]["IT"].substring(11));
		var mdiff = (tnow - tlast)/60000;
		if (datum1 < tomorrow) {
			if ((s_lasttimestamp != inv1Data[0]["IT"] || inv1Data[0]["MODE"] != "MPPT") || mdiff > 10) {
				if(inv1Data[0]["IVACT"] != 0){
					document.getElementById("arrow_PRD").className = "arrow_right_green";
				}else{
					document.getElementById("arrow_PRD").className = "";
				}
				s_p1CounterDelivToday = p1CounterDelivToday;
				s_p1CounterToday = p1CounterToday;
				if (inv1Data[0]["MODE"] == "MPPT") {
					s_lasttimestamp = inv1Data[0]["IT"];
				} else {
					s_lasttimestamp = "";
				}
				document.getElementById("so_text").className = "green_text";
				document.getElementById("so_text").innerHTML = inv1Data[0]["IVACT"]+ " Watt";
				if (P1 == 1){
					document.getElementById("sola_text").innerHTML = "<table width=100% class=data-table>"+
						"<tr><td colspan=2 style=\"font-size:smaller\">"+inv1Data[0]["IT"].substr(11,10)+"</td></tr>" +
						"<tr><td colspan=2><b><u>Solar vandaag</u></b></td></tr>"+
						"<tr><td>verbruik:</td><td>"+waarde(0,3,parseFloat(inv1Data[0]["IE"])-parseFloat(s_p1CounterDelivToday))+" kWh</td></tr>"+
						"<tr><td>retour:</td><td>"+waarde(0,3,parseFloat(s_p1CounterDelivToday))+" kWh</td></tr>"+
						"<tr><td></td><td>----------</td></tr>"+
						"<tr><td class=green_text>productie:</td><td class=green_text>"+waarde(0,3,inv1Data[0]["IE"])+" kWh</td></tr>"+
						"</table>";
				} else {
					document.getElementById("sola_text").innerHTML = "<table width=100% class=data-table>"+
						"<tr><td colspan=2 style=\"font-size:smaller\">"+inv1Data[0]["IT"].substr(11,10)+"</td></tr>" +
						"<tr><td colspan=2><b><u>Solar vandaag</u></b></td></tr>"+
						"<tr><td class=green_text>productie:</td><td class=green_text>"+waarde(0,3,inv1Data[0]["IE"])+" kWh</td></tr>"+
						"</table>";
				}
				document.getElementById("inverter_text").innerHTML = "<table width=100% class=data-table>"+
						"<tr><td>Date:</td><td colspan=3>"+inv1Data[0]["IT"]+"</td></tr>"+
						"<tr><td>Mode:</td><td colspan=3>"+inv1Data[0]["MODE"]+"</td></tr>"+
						"<tr><td>MaxP:</td><td colspan=3>"+inv1Data[0]["IVMAX"]+" W</td><tr>"+
						"<td>Temp:</td><td colspan=3>"+waarde(0,1,inv1Data[0]["ITACT"])+" / "+waarde(0,1,inv1Data[0]["ITMIN"])+" / "+waarde(0,1,inv1Data[0]["ITMAX"])+" °C</td></tr>"+
						"<tr><td>v_dc:</td><td colspan=3>"+waarde(0,1,inv1Data[0]["v_dc"])+"</td></tr></table>";
			}
		}else{
			s_lasttimestamp = inv1Data[0]["IT"];
			document.getElementById("inverter_text").innerHTML = "<table width=100% class=data-table>"+
					"<tr><td>Date:</td><td colspan=3>"+inv1Data[0]["IT"]+"</td></tr>"+
					"<tr><td class=green_text>prod:</td><td  colspan=3 class=green_text>"+waarde(0,3,inv1Data[0]["IE"])+" kWh</td></tr>"+
					"<tr><td>MaxP:</td><td colspan=3>"+inv1Data[0]["IVMAX"]+" W</td><tr>"+
					"<td>Temp:</td><td colspan=3>"+waarde(0,1,inv1Data[0]["ITACT"])+" / "+waarde(0,1,inv1Data[0]["ITMIN"])+" / "+waarde(0,1,inv1Data[0]["ITMAX"])+" °C</td></tr>"+
					"<tr><td></td><td colspan=3></td></tr></table>";
			document.getElementById("arrow_PRD").className = "";
		}
		var tverm = 0;
		for (i=1; i<=aantal; i++) {
			tverm += vpan[i];
		}
		if (inverter == 1){
			document.getElementById("inverter_1").title = "Inverter: "+naam+
				"\r\n\r\nS AC:	"+inv1Data[0]["i_ac"]+" A"+
				"\r\nV AC:	"+inv1Data[0]["v_ac"]+" V"+
				"\r\nFre:	"+inv1Data[0]["frequency"]+" Hz"+
				"\r\nPactive:	"+inv1Data[0]["p_active"]+" kWh"+
				"\r\nV DC:	"+waarde(0,1,inv1Data[0]["v_dc"])+" V"+
				"\r\nP(act):	"+inv1Data[0]["IVACT"]+" W"+
				"\r\nEfficiëntie: " + waarde(0,3,(inv1Data[0]["IE"]*1000/tverm))+" kWh/kWp";
		}else{
			document.getElementById("inverter_1").title = "Inverter: "+naam+
				"\r\n\r\n	L1	L2	L3"+
				"\r\nS AC:	"+inv1Data[0]["i_ac1"]+"	"+inv1Data[0]["i_ac2"]+"	"+inv1Data[0]["i_ac3"]+" A: "+
				"\r\nV AC:	"+inv1Data[0]["v_ac1"]+"	"+inv1Data[0]["v_ac2"]+"	"+inv1Data[0]["v_ac3"]+" V"+
				"\r\nFre:	"+inv1Data[0]["frequency1"]+"	"+inv1Data[0]["frequency2"]+"	"+inv1Data[0]["frequency3"]+" Hz"+
				"\r\nPactive:	"+inv1Data[0]["p_active1"]+"	"+inv1Data[0]["p_active2"]+"	"+inv1Data[0]["p_active3"]+" W"+
				"\r\nV DC:	"+waarde(0,1,inv1Data[0]["v_dc"])+" V"+
				"\r\nP(act):	"+inv1Data[0]["IVACT"]+" W"+
				"\r\nEfficiëntie: " + waarde(0,3,(inv1Data[0]["IE"]*1000/tverm))+" kWh/kWp";
		}
		update_map_fields();
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
			document.getElementById("tool_paneel_"+i).title =
					"Datum  " + inv1Data[0]["TM"+i] + "\r\n" +
					"Paneel " + op_id[i] + "\r\n" +
					"Optimizer SN           " + op_sn[i] + "\r\n" +
					"Paneel SN              " + pn_sn[i] +  "\r\n" +
					"Energie		" + inv1Data[0]["O"+i] + " Wh\r\n" +
					"Vermogen (act.)	" + inv1Data[0]["E"+i] + " W\r\n" +
					"Vermogen (max.)	" + inv1Data[0]["VM"+i] + " W\r\n" +
					"Vermogen (max.)	" + inv1Data[0]["VMT"+i] + "\r\n" +
					"Stroom in	        " + inv1Data[0]["S"+i] + " A\r\n" +
					"Spanning in        	" + inv1Data[0]["VI"+i] + " V\r\n" +
					"Spanning uit	        " + inv1Data[0]["VU"+i] + " V\r\n" +
					"Temperatuur	        " + inv1Data[0]["T"+i] + " °C\r\n" +
					"Efficientie	        " + waarde(0,3,(inv1Data[0]["O"+i]/vpan[i])) + " Wh/Wp";
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
	function p1_update(){
		var p1data = $.ajax({
			url: "<?php echo $DataURL?>?period=c",
			dataType: "json",
			type: 'GET',
			data: { "date" : datumz },
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
		if (datum1 < tomorrow) {
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
		}
		var wdate = new Date(date2);
		var cdate = new Date();
		if (datum1 < tomorrow) {
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
			if (pse+psv+pve+pvs > 0) {
				// update current day info in graphs
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
	}
	function update_map_fields() {
		if (wchart != "" && wchart.series[0].data.length>0 && ychart.series[0].data.length>0) {
			//var cd = new Date();
			var cdate = new Date(date2);
			var cd = cdate.getDate();
			var cm = cdate.getMonth()+1;
			var cy = cdate.getFullYear();
			var ve = 0;
			var vs = 0;
			var se = 0;
			var sv = 0;
			var mse = 0;
			var msv = 0;
			var mve = 0;
			var mvs = 0;
			$.each(wchart.series[2].data, function(i, point) {
				var pdate = new Date(point.x);
				var pd = pdate.getDate();
				var pm = pdate.getMonth()+1;
				var py = pdate.getFullYear();
				if(cy == py && cm == pm && cd >= pd) {
					if (cd == pd) {
						se += wchart.series[0].data[i].y;
						sv += wchart.series[1].data[i].y;
						ve += wchart.series[2].data[i].y;
						vs += wchart.series[3].data[i].y;
					}
					mse += wchart.series[0].data[i].y;
					msv += wchart.series[1].data[i].y;
					mve += wchart.series[2].data[i].y;
					mvs += wchart.series[3].data[i].y;
				}
			});
			var yse = mse;
			var ysv = msv;
			var yve = mve;
			var yvs = mvs;
			$.each(ychart.series[2].data, function(i, point) {
				var pdate = new Date(point.x);
				var pd = pdate.getDate();
				var pm = pdate.getMonth()+1;
				var py = pdate.getFullYear();
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
					if (datum1 < tomorrow) {document.getElementById("p1_huis").innerHTML = waarde(0,3,sv + s_p1CounterToday)+" kWh"};
					document.getElementById("huis_1").title = "thuis verbruik: "+s_lasttimestamp.substr(1,10)+
							"\r\nVandaag\r\nZonne energie: " + waarde(0,3,vs)+" kWh"+
							"\r\n<?php echo $ElecLeverancier?> energie: " + waarde(0,3,ve)+" kWh"+
							"\r\nTotaal verbruik: "+ waarde(0,3,vs+ve)+" kWh" +
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
			var cur = document.getElementById("p1_text").innerHTML;
			if ( document.getElementById("p1_text").className == "red_text") {
				cur = "\r\nHuidig\r\nverbruik:	" + cur;
			} else {
				cur = "\r\nHuidig\r\nretour:	" + cur;
			}
			if (datum1 >= tomorrow) {cur = "";}
			document.getElementById("meter_1").title = "<?php echo $ElecLeverancier?> P1 meter" +
					cur+
					"\r\n\r\nVandaag\r\nverbruik:	"+waarde(0,3,ve)+" kWh\r\nretour:  	"+waarde(0,3,se)+" kWh\r\nnetto:   	"+waarde(0,3,ve-se)+" kWh"+
					"\r\n\r\nMaand\r\nverbruik:	"+waarde(0,2,mve)+" kWh\r\nretour:  	"+waarde(0,2,mse)+" kWh\r\nnetto:   	"+waarde(0,2,mve-mse)+" kWh" +
					"\r\n\r\nJaar\r\nverbruik:	"+waarde(0,1,yve)+" kWh\r\nretour:  	"+waarde(0,1,yse)+" kWh\r\nnetto:   	"+waarde(0,1,yve-yse)+" kWh";
			var curtext=document.getElementById("inverter_1").title;
			var ins = curtext.indexOf("Vandaag")-4;
			if (ins>0) {curtext = curtext.substring(0,ins);}
			var datesol = new Date(date2);
			var dsol = datesol.getDate();
			var msol = datesol.getMonth()+1;
			var ysol = datesol.getFullYear();
			var dayssol = daysInMonth(msol, ysol);
			var PVGisd = "";
			var PVGism = "";
			var PVGisj = "";
			if (PVGis[cm] > 0) {
				PVGisd = " ("+waarde(0,0,PVGis[cm]/dayssol)+" kWh)" ;
				PVGism = " ("+waarde(0,0,PVGis[cm]/dayssol*dsol)+" kWh)" ;
				var tPVGis=0;
				for (i=1; i<msol; i++) {
					tPVGis += PVGis[i];
				}
				PVGisj = " (" + waarde(0,0,tPVGis+(PVGis[cm]/dayssol*dsol))+" kWh)";
			}
			document.getElementById("inverter_1").title = curtext+
					"\r\n\r\nVandaag:     " + waarde(0,2,SolarProdToday)+" kWh"+
					PVGisd+
					"\r\nMaand:    " + waarde(0,2,mse + msv)+" kWh"+
					PVGism+
					"\r\nJaar:   	" + waarde(0,2,yse + ysv)+" kWh"+
					PVGisj;
			if (datum1 >= tomorrow) {
				var ddiff=ve-se;
				var mdiff=mve-mse;
				var ydiff=yve-yse;
				var dcdiff  = "red_text";
				var mcdiff  = "red_text";
				var ycdiff  = "red_text";
				if (ddiff < 0) {
					dcdiff  = "green_text";
					ddiff = ddiff * -1;
				}
				if (mdiff < 0) {
					mcdiff  = "green_text";
					mdiff = mdiff * -1;
				}
				if (ydiff < 0) {
					ycdiff  = "green_text";
					ydiff = ydiff * -1;
				}
				document.getElementById("sum_text").innerHTML = "<table width=100% class=data-table>"+
						"<tr><td colspan=5><b>&nbsp;&nbsp;&nbsp;Totaal overzicht "+datev+"</b></td></tr>" +
						"<tr><td colspan=2></td><td><u>Dag</u></td><td><u>MTD</u></td><td><u>JTD</u></td></tr>" +
						"<tr><td colspan=2><u>Solar prod:</u></td><td>"+waarde(0,0,SolarProdToday)+"</td><td>"+waarde(0,0,mse + msv)+"</td><td>"+waarde(0,0,yse + ysv)+"</td></tr>"+
						`${PVGis[cm] == 0 ? '' : '<tr><td colspan=2>'+PVGtxt+':</td><td>'+waarde(0,0,PVGis[cm]/dayssol)+'</td><td>'+waarde(0,0,PVGis[cm]/dayssol*dsol)+ '</td><td>'+waarde(0,0,tPVGis+(PVGis[cm]/dayssol*dsol))+'</td></tr>'}` +
						"<tr><td colspan=5><br><u>Huis verbruik</u></td></tr>" +
						"<tr><td colspan=2>solar:</td><td>"+waarde(0,0,sv)+"</td><td>"+waarde(0,0,msv)+"</td><td>"+waarde(0,0,ysv)+"</td></tr>"+
						"<tr><td colspan=2>net:</td><td>"+waarde(0,0,ve)+"</td><td>"+waarde(0,0,mve)+"</td><td>"+waarde(0,0,yve)+"</td></tr>"+
						"<tr><td colspan=2><b>Totaal:</b></td><td><b>"+waarde(0,0,ve+sv)+"</b></td><td><b>"+waarde(0,0,mve+msv)+"</b></td><td><b>"+waarde(0,0,yve+ysv)+"</b></td></tr>"+
						"<tr><td colspan=5><br><u><?php echo $ElecLeverancier?></u></td></tr>" +
						"<tr><td colspan=2>verbruik:</td><td>"+waarde(0,0,ve)+"</td><td>"+waarde(0,0,mve)+"</td><td>"+waarde(0,0,yve)+"</td></tr>"+
						"<tr><td colspan=2>retour:</td><td>"+waarde(0,0,se)+"</td><td>"+waarde(0,0,mse)+"</td><td>"+waarde(0,0,yse)+"</td></tr>"+
						"<tr><td colspan=2><b>Netto:</b></td><td class="+dcdiff+"><b>"+waarde(0,0,ddiff)+"</b></td><td class="+mcdiff+"><b>"+waarde(0,0,mdiff)+"</b></td><td class="+ycdiff+"><b>"+waarde(0,0,ydiff)+"</b></td></tr>"+
						"</table>";
			}
		}
	}
	function zonmaan(){
		if (date2 >= date3){
			document.getElementById("NextDay").disabled = true;
			document.getElementById("Today").disabled = true;
		}else{
			document.getElementById("NextDay").disabled = false;
			document.getElementById("Today").disabled = false;
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
		document.getElementById("panel_vermogen").innerHTML ="";
		document.getElementById("panel_energy").innerHTML ="";
		document.getElementById("sunrise_text").innerHTML = sunrise+" uur";
		document.getElementById("solar_noon_text").innerHTML = solar_noon+" uur";
		document.getElementById("sunset_text").innerHTML = sunset+" uur";
		document.getElementById("daglengte_text").innerHTML = daglengte+" uur";
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
				error : function(xhr, textStatus, errorThrown ) {
					   setTimeout(requestData1, 1000*10);
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
				error : function(xhr, textStatus, errorThrown ) {
					   setTimeout(requestData2, 1000*10);
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
					if(inverter_redraw == 1) {
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
						inverter_chart.redraw();
						vermogen_chart.redraw();
					}
					if (datum1 < tomorrow) {
					   setTimeout(requestDatai, 1000*60);
					} else {
					   setTimeout(requestDatai, 1000*86400);
					}
				},
				error : function(xhr, textStatus, errorThrown ) {
					   setTimeout(requestDatai, 1000*10);
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
						if (this.dataMax == this.dataMin) {
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
						if (this.dataMax == this.dataMin) {
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
				<?php e_panelen($aantal); ?>
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
						if (this.dataMax == this.dataMin) {
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
				<?php e_panelen($aantal); ?>
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
						if (this.dataMax == this.dataMin) {
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
						var s = '-> <u><b>' + Highcharts.dateFormat(' %H:%M', this.x)+ '</b></u>';
						var sortedPoints = this.points.sort(function(a, b){
							return ((a.y > b.y) ? -1 : ((a.y < b.y) ? 1 : 0));
						});
						$.each(sortedPoints, function () {
							for (i=0; i<=14; i++){
								if (this.series.name == productie[i]) {
									this.point.series.options.marker.states.hover.enabled = false;
									if (s != ""){ s += '<br>'}
									if (this.series.state == "hover") {
										s += '<b>*</b>';
										this.point.series.options.marker.states.hover.enabled = true;
										this.point.series.options.marker.states.hover.lineColor = 'red';
									}
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
									enabled: false
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
									enabled: false,
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
					text: "Vermogen op <?php echo $datev;?> en 14 voorafgaande dagen",
					align: 'left',
					x: 20,
					y: 20,
					style: {
						font: 'Arial',
						fontWeight: 'bold',
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
						if (this.dataMax == this.dataMin) {
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
									this.point.series.options.marker.states.hover.enabled = false;
									if (s != ""){ s += '<br>'}
									if (this.series.state == "hover") {
										s += '<b>*</b>';
										this.point.series.options.marker.states.hover.enabled = true;
										this.point.series.options.marker.states.hover.lineColor = 'red';
									}
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
					},
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
			if (P1 == "0"){
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
			}
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
	$('#Today').click(function() {
		var date = new Date();
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
		event.stopPropagation();
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
		event.stopPropagation();
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
		event.stopPropagation();
	});
	document.getElementById("box_Zonnepanelen").addEventListener("click", function() {
		this.classList.toggle("box_Zonnepanelen-is-clicked");
	});
	document.getElementById("box_inverter").addEventListener("click", function() {
		this.classList.toggle("box_inverter-is-clicked");
	});
	document.getElementById("box_chart_energy").addEventListener("click", function() {
		this.classList.toggle("box_chart_energy-is-clicked");
		document.getElementById("box_panel_energy").classList.toggle("box_panel_energy-is-clicked");
		paneel_charte.reflow();
		inverter_chart.reflow();
		inverter_chart.reflow();
	});
	document.getElementById("box_chart_vermogen").addEventListener("click", function() {
		this.classList.toggle("box_chart_vermogen-is-clicked");
		document.getElementById("box_panel_vermogen").classList.toggle("box_panel_vermogen-is-clicked");
		paneel_chartv.reflow();
		vermogen_chart.reflow();
		vermogen_chart.reflow();
	});
	document.getElementById("box_daygraph").addEventListener("click", function() {
		this.classList.toggle("box_daygraph-is-clicked");
		wchart.reflow();
		wchart.reflow();
	});
	document.getElementById("box_monthgraph").addEventListener("click", function() {
		this.classList.toggle("box_monthgraph-is-clicked");
		ychart.reflow();
		ychart.reflow();
	});
	window.addEventListener('resize', function(){
		paneel_charte.reflow();
		inverter_chart.reflow();
		paneel_chartv.reflow();
		vermogen_chart.reflow();
		wchart.reflow();
		ychart.reflow();
	}, true);
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
					sPVG = 0;
					sPVGm = 0;
					$.each(this.points, function(i, point) {
						if(point.series.name == 'Solar Retour <?php echo $ElecLeverancier?>') {
							sRE = point.y;
						} else if(point.series.name == 'Solar verbruik') {
							sVS = point.y;
						} else if(point.series.name == PVGtxt+' schatting') {
							sPVG = point.y;
							if (point.series.chart.renderTo.id == "monthgraph" && point.point.index == point.series.yData.length-2) {
								sPVG = point.series.yData[point.series.yData.length-1];
								sPVGm = point.y;
							}
						} else if(point.series.name == 'Verbruik <?php echo $ElecLeverancier?>') {
							sVE = point.y;
						}
					});
					if (this.points[0].series.chart.renderTo.id == "monthgraph") {
						s += Highcharts.dateFormat('%B %Y', this.x);
					} else {
						s += Highcharts.dateFormat('%A %d-%m-%Y', this.x);
					}
					s += '</u></b><br/>';
					//
					if(sVS+sRE>0){
						s += 'Solar verbruik&nbsp;: ' + Highcharts.numberFormat(sVS,1) + ' kWh<br/>' +
						     '<b>Solar totaal&nbsp;&nbsp;&nbsp;&nbsp;: ' + Highcharts.numberFormat(sVS+sRE,1) + '</b> kWh<br/>';
						if (this.points[0].series.chart.renderTo.id == "monthgraph") {
							if ( sPVG > 0) {
								if ( sPVGm > 0) {
									s += '<b>'+PVGtxt+' MTD&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' + sPVGm + '</b> kWh<br/>';
								}
								s += '<b>'+PVGtxt+' maand&nbsp;: ' + sPVG + '</b> kWh<br/>';
							}
						} else {
								s += '<b>'+PVGtxt+' dag gem.: ' + sPVG + '</b> kWh<br/>';
						}
						s += '----------------------<br/>';
						s += '<?php echo $ElecLeverancier?> verbruik: ' + Highcharts.numberFormat(sVE,1) + ' kWh<br/>';
						s += '<?php echo $ElecLeverancier?> retour&nbsp;-: ' + Highcharts.numberFormat(sRE,1) + ' kWh<br/>';
						s += '<b><?php echo $ElecLeverancier?> Netto&nbsp;&nbsp;&nbsp;: <b>' + Highcharts.numberFormat(sVE-sRE,1) + '</b> kWh<br/>';
						s += '&nbsp;<br/>';
						s += '<b>Totaal verbruik: <b>' + Highcharts.numberFormat(sVE+sVS,1) + '</b> kWh';
					} else {
						if (this.points[0].series.chart.renderTo.id == "monthgraph") {
							if ( sPVG > 0) {
								s += '<b>'+PVGtxt+' maand&nbsp;: ' + sPVG + '</b> kWh<br/>';
							}
						}
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
		var url='<?php echo $DataURL?>?period='+gtype+'&aantal='+periods+"&date="+datumz;
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
		var datatableSolarPVGis = [];
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
			var datesol = new Date(date2);
			var dsol = datesol.getDate();
			var msol = datesol.getMonth()+1;
			var ysol = datesol.getFullYear();
			var dayssol = daysInMonth(msol, ysol);
			var d = new Date(item.idate);
			var dm = d.getMonth()+1;
			var dy = d.getFullYear();
			if (PVGis[dm] > 0) {
				if (chart.renderTo.id == "monthgraph") {
					if ( dm == msol && dy == ysol) {
						datatableSolarPVGis.push([cdate, parseInt(PVGis[dm]/dayssol*dsol)]);
						if (chart.renderTo.id == "monthgraph") {
							datatableSolarPVGis.push([cdate, PVGis[dm]]);
						}
					} else {
						datatableSolarPVGis.push([cdate, PVGis[dm]]);
					}
				} else {
					datatableSolarPVGis.push([cdate, parseInt(PVGis[dm]/daysInMonth(dm,dy))]);
				}
			}
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
		//if (chart.renderTo.id == "monthgraph" && chart.get('SolarPVGis') != null) {
		if (chart.get('SolarPVGis') != null) {
			series = chart.get('SolarPVGis');
			series.setData(datatableSolarPVGis, false);
		}
	}
	//
	function AddSeriestoChart(chart, switchtype) {
		totDecimals = 0;
		chart.addSeries({
			id: 'SolarElecNet',
			type: 'area',
			name: 'Solar Retour <?php echo $ElecLeverancier?>',
			color: 'rgba(30,242,110,1)',
			stack: 'sreturn',
		}, false);
		chart.addSeries({
			id: 'SolarVerbruik',
			type: 'area',
			name: 'Solar verbruik',
			showInLegend: true,
			color: 'rgba(3,222,190,1)',
			stack: 'sreturn',
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
				valueDecimals: totDecimals
			},
			color: 'rgba(60,130,252,0.5)',
			stack: 'susage',
		}, false);
		chart.addSeries({
			id: 'verbruikSolar',
			name: 'Verbruik Solar',
			color: 'rgba(3,190,252,0.5)',
			stack: 'susage',
		}, false);
		if (PVGis[1] > 0) {
			chart.addSeries({
				id: 'SolarPVGis',
				type: 'line',
				name: PVGtxt+' schatting',
				color: 'rgba(255,0,0,0.6)',
				lineWidth: 1,
				marker: {
					radius: 2,
					enabled: true
				},
			}, false);
		}
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
	function daysInMonth (month, year) { // Use 1 for January, 2 for February, etc.
	  return new Date(year, month, 0).getDate();
	}
</script>
</html>
