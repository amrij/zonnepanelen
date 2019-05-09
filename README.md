# Zonnepanelen
This Website will show telemetry data from the TCP traffic of SolarEdge PV inverters or both the SolarEdge and P1 meter information.
The website is based on se-logger (https://github.com/jbuehl/solaredge/)
It is suitable for single-phase inverters and 3-phase inverters.
It also contains P1 information retrieval scripts for Domoticz, DSMR and an extra P1_Meter table which can to be added to the SolarEdge database.

## How it works
The website is based on the database of se-logger.  
To configure the website, both config.php and css/zonnepanelen-local.css have to be modified.
After configuration, the website can be started by going to the root of the Website (index.htm) or zonnepanelen.php.

## Installation steps
1. Unpack the file img/maan/maan.zip.
2. Customize config.php  
In config.php the following information is defined:  
   - Data for access to the database;
   - The latitude and longitude of the location of the panels;
   - Background image to use;
   - Indicate power on the panels;
   - Type of inverter (1 or 3 phase);
   - The name of the inverter (type number);
   - The optimizer id, number of the panel, the direction, panel SN and panel power in pW.
   - Additional information voor lay-out chart inverter and solar panels;
   - Additional information for p1:
   - Indicate to show P1 values;
   - P1 script to be used to retrieve the information;
   - Electricity supplier;
   - Electricity contract start date, which will be used to show the totals for the contract period in stead of the current year.
   - Number of Days and Months to show in the P1 graphs;
   - PVGis information when you want to have that included in the graphs.
3. Customize css/zonnepanelen-local.css for zonnepanelen.php by copying the zonnepanelen-local-example.css to zonnepanelen-local.css.  The following is defined in this file:  
   - div.box_Zonnepanelen: place, dimensions and possible rotation;
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
