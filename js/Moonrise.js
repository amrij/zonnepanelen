/*
	Calculate Moonrise and Moonset
	Copyright © 2006-2012 Harry Whitfield

	This program is free software; you can redistribute it and/or modify it
	under the terms of the GNU General Public License as published by the
	Free Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful, but
	WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	General Public License for more details.

	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
	Calculate Moonrise and Moonset - version 2.2
	14 March, 2012
	Copyright © 2006-2012 Harry Whitfield
	mailto:g6auc@arrl.net
*/

/*
	This code module is a modified version of the
	Moonrise, Moonset solver by Stephen R. Schmitt
	who wrote:

	"You may use or modify this source code in any way you find useful, provided 
	that you agree that the author has no warranty, obligations or liability.
	You must determine the suitability of this source code for your use."
	
	Copyright © 2004, Stephen R. Schmitt
*/

/*properties PI, atan, atan2, charAt, cos, floor, getDate, getFullYear, 
    getMonth, length, sin, sqrt, substring, toString
*/

var PI = Math.PI;
var DR = PI / 180;
var K1 = 15 * DR * 1.0027379;

var moonrise = false;
var moonset	 = false;

var riseTime = [0, 0, 0];
var setTime  = [0, 0, 0];
var riseAz = 0.0;
var setAz	= 0.0;

var sky = [0.0, 0.0, 0.0];
var ran = [0.0, 0.0, 0.0];
var dec = [0.0, 0.0, 0.0];
var vhz = [0.0, 0.0, 0.0];

function toHours12(time) {	// convert time from 24-hour to 12-hour format
	var hours = Number(time.substring(0, 2)), ampm = 'AM';
	
	if (hours > 11) { hours -= 12; ampm = 'PM'; }
	if (hours === 0) {hours = 12; }
	hours = String(hours);
	if (hours.length === 1) { hours = '0' + hours; }
	return hours + time.substring(2) + ampm;
}

// determine Julian day from calendar date
// (Jean Meeus, "Astronomical Algorithms", Willmann-Bell, 1991)
function julianDay(date) {
	var a, b,
		month = date.getMonth() + 1,
		day	  = date.getDate(),
		year  = date.getFullYear(),
		gregorian = (year >= 1583);
		
	if ((month === 1) || (month === 2)) { year -= 1; month += 12; }
	a = Math.floor(year / 100);
	b = gregorian ? 2 - a + Math.floor(a / 4) : 0;

	return Math.floor(365.25 * (year + 4716)) + Math.floor(30.6001 * (month + 1)) + day + b - 1524.5;
}

// Local Sidereal Time for zone
function lst(lon, jd, z) {
	var s = 24110.5 + 8640184.812999999 * jd / 36525 + 86636.6 * z + 86400 * lon;
	s = s / 86400;
	s = s - Math.floor(s);
	return s * 360 * DR;
}

// moon's position using fundamental arguments 
// (Van Flandern & Pulkkinen, 1979)
function monPosn(jd) {
	var d, f, g, h, m, n, s, u, v, w;

	h = 0.606434 + 0.03660110129 * jd;
	m = 0.374897 + 0.03629164709 * jd;
	f = 0.259091 + 0.0367481952  * jd;
	d = 0.827362 + 0.03386319198 * jd;
	n = 0.347343 - 0.00014709391 * jd;
	g = 0.993126 + 0.0027377785  * jd;

	h = h - Math.floor(h);
	m = m - Math.floor(m);
	f = f - Math.floor(f);
	d = d - Math.floor(d);
	n = n - Math.floor(n);
	g = g - Math.floor(g);

	h = h * 2 * PI;
	m = m * 2 * PI;
	f = f * 2 * PI;
	d = d * 2 * PI;
	n = n * 2 * PI;
	g = g * 2 * PI;

	v = 0.39558 * Math.sin(f + n);
	v = v + 0.082   * Math.sin(f);
	v = v + 0.03257 * Math.sin(m - f - n);
	v = v + 0.01092 * Math.sin(m + f + n);
	v = v + 0.00666 * Math.sin(m - f);
	v = v - 0.00644 * Math.sin(m + f - 2 * d + n);
	v = v - 0.00331 * Math.sin(f - 2 * d + n);
	v = v - 0.00304 * Math.sin(f - 2 * d);
	v = v - 0.0024  * Math.sin(m - f - 2 * d - n);
	v = v + 0.00226 * Math.sin(m + f);
	v = v - 0.00108 * Math.sin(m + f - 2 * d);
	v = v - 0.00079 * Math.sin(f - n);
	v = v + 0.00078 * Math.sin(f + 2 * d + n);
	
	u = 1 - 0.10828 * Math.cos(m);
	u = u - 0.0188  * Math.cos(m - 2 * d);
	u = u - 0.01479 * Math.cos(2 * d);
	u = u + 0.00181 * Math.cos(2 * m - 2 * d);
	u = u - 0.00147 * Math.cos(2 * m);
	u = u - 0.00105 * Math.cos(2 * d - g);
	u = u - 0.00075 * Math.cos(m - 2 * d + g);
	
	w = 0.10478 * Math.sin(m);
	w = w - 0.04105 * Math.sin(2 * f + 2 * n);
	w = w - 0.0213  * Math.sin(m - 2 * d);
	w = w - 0.01779 * Math.sin(2 * f + n);
	w = w + 0.01774 * Math.sin(n);
	w = w + 0.00987 * Math.sin(2 * d);
	w = w - 0.00338 * Math.sin(m - 2 * f - 2 * n);
	w = w - 0.00309 * Math.sin(g);
	w = w - 0.0019  * Math.sin(2 * f);
	w = w - 0.00144 * Math.sin(m + n);
	w = w - 0.00144 * Math.sin(m - 2 * f - n);
	w = w - 0.00113 * Math.sin(m + 2 * f + 2 * n);
	w = w - 0.00094 * Math.sin(m - 2 * d + g);
	w = w - 0.00092 * Math.sin(2 * m - 2 * d);

	s = w / Math.sqrt(u - v * v);						// compute moon's right ascension ...  
	sky[0] = h + Math.atan(s / Math.sqrt(1 - s * s));

	s = v / Math.sqrt(u);								// declination ...
	sky[1] = Math.atan(s / Math.sqrt(1 - s * s));

	sky[2] = 60.40974 * Math.sqrt(u);					// and parallax
}

// 3-point interpolation
function interpolate(f0, f1, f2, p) {
	var a = f1 - f0, b = f2 - f1 - a, f = f0 + p * (2 * a + b * (2 * p - 1));

	return f;
}

// returns value for sign of argument
function sgn(x) {
	var rv;
	
	if (x > 0.0) { rv =  1; } else if (x < 0.0) { rv = -1; } else { rv =  0; }
	return rv;
}

// test an hour for an event
function test_moon(k, zone, t0, lat, plx) {
	var ha = [0.0, 0.0, 0.0], a, b, c, d, e, s, z,
		hr, min, sec, time, res, az, hz, nz, dz;

	if (ran[2] < ran[0]) { ran[2] = ran[2] + 2 * PI; }
	
	ha[0] = t0 - ran[0] + k * K1;
	ha[2] = t0 - ran[2] + k * K1 + K1;
	
	ha[1]  = (ha[2] + ha[0]) / 2;				// hour angle at half hour
	dec[1] = (dec[2] + dec[0]) / 2;				// declination at half hour

	s = Math.sin(DR * lat);
	c = Math.cos(DR * lat);

	// refraction + sun semidiameter at horizon + parallax correction
	z = Math.cos(DR * (90.567 - 41.685 / plx));

	if (k <= 0)	{								// first call of function
		vhz[0] = s * Math.sin(dec[0]) + c * Math.cos(dec[0]) * Math.cos(ha[0]) - z;
	}

	vhz[2] = s * Math.sin(dec[2]) + c * Math.cos(dec[2]) * Math.cos(ha[2]) - z;
	
	if (sgn(vhz[0]) === sgn(vhz[2])) { return vhz[2]; }		// no event this hour
	
	vhz[1] = s * Math.sin(dec[1]) + c * Math.cos(dec[1]) * Math.cos(ha[1]) - z;

	a = 2 * vhz[2] - 4 * vhz[1] + 2 * vhz[0];
	b = 4 * vhz[1] - 3 * vhz[0] - vhz[2];
	d = b * b - 4 * a * vhz[0];

	if (d < 0) { return vhz[2]; }				// no event this hour
	
	d = Math.sqrt(d);
	e = (-b + d) / (2 * a);

	if ((e > 1) || (e < 0)) { e = (-b - d) / (2 * a); }

	time = k + e + 1 / 7200;					// time of an event in hours plus round up of half second
	hr	 = Math.floor(time);
	res	 = (time - hr) * 60;
	min	 = Math.floor(res);
	res	 = (res - min) * 60;
	sec = Math.floor(res);

	hz = ha[0] + e * (ha[2] - ha[0]);			// azimuth of the moon at the event
	nz = -Math.cos(dec[1]) * Math.sin(hz);
	dz = c * Math.sin(dec[1]) - s * Math.cos(dec[1]) * Math.cos(hz);
	az = Math.atan2(nz, dz) / DR;
	if (az < 0) { az = az + 360; }
	
	if ((vhz[0] < 0) && (vhz[2] > 0)) {
		riseTime[0] = hr;
		riseTime[1] = min;
		riseTime[2] = sec;
		riseAz = az;
		moonrise = true;
	}
	
	if ((vhz[0] > 0) && (vhz[2] < 0)) {
		setTime[0] = hr;
		setTime[1] = min;
		setTime[2] = sec;
		setAz = az;
		moonset = true;
	}

	return vhz[2];
}

// format a positive integer with leading zeroes
function zintstr(num, width) {
	var str = num.toString(10),
		len = str.length,
		intgr = "",
		i;

	for (i = 0; i < width - len; i += 1) { intgr += '0'; }		// append leading zeroes
	for (i = 0; i < len; i += 1) { intgr += str.charAt(i); }	// append digits
	return intgr;
}

// calculate moonrise and moonset times
function moonRiseSet(lat, lon, date, zone, sr, h12) {
	var i, j, k, ph, tz, t0, calc_moonrise_value, calc_moonrise_value24,
		calc_moonset_value, calc_moonset_value24,
	// var zone = Math.round(date.getTimezoneOffset() / 60);	// time zone offset in hours
		jd = julianDay(date) - 2451545,							// Julian day relative to Jan 1.5, 2000
	// if ((sgn(zone) === sgn(lon))&&(zone !== 0)) { alert("WARNING: time zone and longitude are incompatible!"); }
		mp = [];					 // create a 3x3 array
		
	for (i = 0; i < 3; i += 1) { mp[i] = []; for (j = 0; j < 3; j += 1) { mp[i][j] = 0.0; } }

	lon = lon / 360;
	tz = zone / 24;
	t0 = lst(lon, jd, tz);				   		// local sidereal time

	jd = jd + tz;								// get moon position at start of day

	for (k = 0; k < 3; k += 1) {
		monPosn(jd);
		mp[k][0] = sky[0];
		mp[k][1] = sky[1];
		mp[k][2] = sky[2];
		jd = jd + 0.5;		
	}	

	if (mp[1][0] <= mp[0][0]) { mp[1][0] = mp[1][0] + 2 * PI; }
	if (mp[2][0] <= mp[1][0]) { mp[2][0] = mp[2][0] + 2 * PI; }

	ran[0] = mp[0][0];
	dec[0] = mp[0][1];

	moonrise = false;						   // initialize
	moonset	 = false;
	
	for (k = 0; k < 24; k += 1)	{			   // check each hour of this day
		ph = (k + 1) / 24;
		
		ran[2] = interpolate(mp[0][0], mp[1][0], mp[2][0], ph);
		dec[2] = interpolate(mp[0][1], mp[1][1], mp[2][1], ph);
		
		vhz[2] = test_moon(k, zone, t0, lat, mp[1][2]);

		ran[0] = ran[2];					   // advance to next hour
		dec[0] = dec[2];
		vhz[0] = vhz[2];
	}

	// display results

	calc_moonrise_value	  = zintstr(riseTime[0], 2) + ":" + zintstr(riseTime[1], 2) + ":" +
		zintstr(riseTime[2], 2); // + ", az = " + frealstr(riseAz, 5, 1) + "¬∞ÔøΩ";
	calc_moonrise_value24 = calc_moonrise_value;
	if (h12) { calc_moonrise_value = toHours12(calc_moonrise_value); }

	calc_moonset_value	 = zintstr(setTime[0], 2) + ":" + zintstr(setTime[1], 2) + ":" +
		zintstr(setTime[2], 2);	// + ", az = " + frealstr(setAz, 5, 1) + "¬∞ÔøΩ";
	calc_moonset_value24 = calc_moonset_value;
	if (h12) { calc_moonset_value = toHours12(calc_moonset_value); }

	if ((!moonrise) && (!moonset)) {			// neither moonrise nor moonset
		if (vhz[2] < 0) { return "Down all day"; }
		return "Up all day";
	}								// moonrise or moonset
	if (!moonrise) {
		calc_moonrise_value = "None";
	} else if (!moonset) { 					// No moonrise this date
		calc_moonset_value	= "None";
	}										// No moonset this date

	if (sr == 'r'){return calc_moonrise_value;} 
	if (sr == 's'){return calc_moonset_value;}
	return sr;
	return '{S}' + calc_moonset_value + '/{R}' + calc_moonrise_value;
}

// test an hour for an event
function test_sun(k, zone, t0, lat) {
	var ha = [], a, b, c, d, e, s, z,
		hr, min, sec, time, res,
		az, dz, hz, nz;
	
	ha[0] = t0 - ran[0] + k * K1; 
	ha[2] = t0 - ran[2] + k * K1 + K1; 

	ha[1]  = (ha[2]	 + ha[0]) / 2;			   // hour angle at half hour
	dec[1] = (dec[2] + dec[0]) / 2;			   // declination at half hour
	
	s = Math.sin(lat * DR);
	c = Math.cos(lat * DR);
	z = Math.cos(90.833 * DR);				   // refraction + sun semidiameter at horizon

	if (k <= 0) {
		vhz[0] = s * Math.sin(dec[0]) + c * Math.cos(dec[0]) * Math.cos(ha[0]) - z;
	}

	vhz[2] = s * Math.sin(dec[2]) + c * Math.cos(dec[2]) * Math.cos(ha[2]) - z;
	
	if (sgn(vhz[0]) === sgn(vhz[2])) { return vhz[2]; } // no event this hour
	
	vhz[1] = s * Math.sin(dec[1]) + c * Math.cos(dec[1]) * Math.cos(ha[1]) - z;
	
	a =	 2 *  vhz[0] - 4 * vhz[1] + 2 * vhz[2]; 
	b = -3 *  vhz[0] + 4 * vhz[1] - vhz[2];	  
	d = b * b - 4 * a * vhz[0];

	if (d < 0) { return vhz[2]; }			   // no event this hour
	
	d = Math.sqrt(d);	 
	e = (-b + d) / (2  *  a);
	
	if ((e > 1) || (e < 0)) { e = (-b - d) / (2 * a); }

	time = k + e + 1 / 7200;						// time of an event in hours plus round up of half second
	hr	 = Math.floor(time);
	res	 = (time - hr) * 60;
	min	 = Math.floor(res);
	res	 = (res - min) * 60;
	sec = Math.floor(res);

	hz = ha[0] + e * (ha[2] - ha[0]);			   // azimuth of the sun at the event
	nz = -Math.cos(dec[1]) * Math.sin(hz);
	dz = c * Math.sin(dec[1]) - s * Math.cos(dec[1]) * Math.cos(hz);
	az = Math.atan2(nz, dz) / DR;
	if (az < 0) { az = az + 360; }
	
	if ((vhz[0] < 0) && (vhz[2] > 0)) {
		riseTime[0] = hr;
		riseTime[1] = min;
		riseTime[2] = sec;
		riseAz = az;
		moonrise = true;
	}
	
	if ((vhz[0] > 0) && (vhz[2] < 0)) {
		setTime[0] = hr;
		setTime[1] = min;
		setTime[2] = sec;
		setAz = az;
		moonset = true;
	}

	return vhz[2];
}
