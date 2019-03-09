# Zonnepanelen
Website which shows telemetry data from the TCP traffic of SolarEdge PV inverters
The website is based on se-logger (https://github.com/jbuehl/solaredge/)
This website shows the data that is collected with the se-logger software. 
It is suitable for single-phase inverters and 3-phase inverters.

## How it works
The website is based on the database of se-logger. 
To configure the website config.php and css / zonnepanelen.css have to be modified. 
Then the website can be started with solar panels.php.

## Customize config.php
In config.php the following is fixed:
- data for access to the database;
- the latitude and longitude of the location of the panels;
- name of the background image;
- indicate power on the panels;
- type of inverter (1 or 3 phase);
- the name of the inverter (type number);
- the number of panels;
- the optimizer id and number of the panel and the direction.

## Customize css/zonnepanelen.css
In css/zonnepanelen.css the following is fixed:
- div.box_Zonnepanelen: place, dimensions and possible rotation;
- div.box_Zonnepaneel_x: place and dimensions in % of the dimensions of div.box_Zonnepanelen.

The file img/maan/maan.zip must be unpacked.

For more information see https://gathering.tweakers.net/forum/list_message/54439825#54439825
