# Zonnepanelen
This Website will show telemetry data from the TCP traffic of SolarEdge PV inverters with zonnepanelen.php, or both the SolarEdge and P1 meter information with zonnepanelen-electra.php.
The website is based on se-logger (https://github.com/jbuehl/solaredge/)
It is suitable for single-phase inverters and 3-phase inverters.
It also contains P1 information retrieval scripts for Domoticz, DSMR and an extra P1_Meter table which can to be added to the SolarEdge database.

## How it works
The website is based on the database of se-logger.  
To configure the website, both config.php and css/zonnepanelen(-electra).css have to be modified.
After configuration, the website can be started by going to the root of the Website (index.htm),  zonnepanelen.php or zonnepanelen-electra.php depending for which page you have chosen.

## Installation steps
1. Unpack the file img/maan/maan.zip.
2. Customize index.htm to define which of the 2 pages you want to open by default.
```
<script>
	window.location.replace("zonnepanelen.php");
	//window.location.replace("zonnepanelen-electra.php");
</script>
```
3. Customize config.php  
In config.php the following information is defined:  
   - data for access to the database;
   - the latitude and longitude of the location of the panels;
   - name of the background image;
   - indicate power on the panels;
   - type of inverter (1 or 3 phase);
   - the name of the inverter (type number);
   - the optimizer id, number of the panel, the direction, panel SN and panel power in pW.
   - Extra information for p1:  
    - P1 script to be used to retrieve the information.
    - Electricity supplier.
    - Number of Days and Months to show in the P1 graphs.
    - Background image to use.
    - PVGis information when you want to have that included in the graphs.
4. Customize css/zonnepanelen.css for zonnepanelen.php  
In css/zonnepanelen.css the following is fixed:  
   - div.box_Zonnepanelen: place, dimensions and possible rotation;
   - div.box_Zonnepaneel_x: place and dimensions in % of the dimensions of div.box_Zonnepanelen.
5. Customize css/zonnepanelen-electra.css for zonnepanelen-electra.php  
In css/zonnepanelen-electra.css the following is arranged:  
   - div.box_Zonnepaneel_x: place and dimensions in % of the dimensions of div.box_Zonnepanelen.

## screenshot zonnepanelen.php $P1 = 0:
  ![Alt text](docs/zonnepanelen.png?raw=true "zonnepanelen.php")

## screenshots zonnepanelen.php $P1 = 1:
  ![Alt text](docs/zonnepanelen-electra_LT_new.PNG?raw=true "Laptop")
  ![Alt text](docs/zonnepanelen-electra_Mobiel.jpg?raw=true "Mobile portrait")

## SQL issues
The websites will check the SQL settings and connectivity at startup, and will report the error returned in case of issues connecting to the SolarEdge database. For example:

![Alt text](docs/sql-error.png?raw=true "Mobile portrait")

For more information see https://gathering.tweakers.net/forum/list_message/54439825#54439825
