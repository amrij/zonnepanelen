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


## Website showing both the Solar and the P1_meter information from Domoticz or other source
The main webpage is zonnepanelen-electra.php which will be automatically opened by index.htm.
This version also uses a customized CSS, zonnepanelen-electra.css, to allow it to be viewed on a PC, Laptop, Mobile in portrait and Landscape. This means you need to update it with your config for the solar panels as described above for the standard site.
This is handled by means of the  containing the definition for each of these options.

The Config.php contains extra set of variables required for the extra's needed by this version of the website. Their purpose is described at the end of the line in this section of config.php:
```
//#### Toegevoegd voor zonnepanelen-electra.php tbv informatie ophalen van electra
//** Algemene velden
$ElecLeverancier = "Essent";        					// naam electra leverancier
$ElecDagGraph = '45';               					// aantal dagen in grafiek
$ElecMaandGraph = '14';             					// aantal maanden in grafiek
$zonnesysteem_electra = "zonnesysteem-electra.gif";     // achtergrond. Let op: dit is een ander formaat dan het orgineel om het ook op Mobiel te kunnen laten zien.
//** velden die worden gebruikt om de PVGis schatting in de website te laten zien. Wordt alleen getoond als ze invult zijn.
$PVGtxt = "PVGis";                                           // Tekst waar de schatting vandaan komt bv: "PVGis"
$PVGis = [144,233,461,628,660,641,630,574,440,296,154,116];  // schatting opbrengst iedere maand voor de installatie

//** velden voor Electra info van Domoticz server
$domohost = '192.168.0.??:8080';						// ip:poort van domoticz
$domoidx = "123";    									// device IDX voor de Electriciteits P1 meter
$DataURL = 'live-server-data-electra-domoticz.php'; 	// URL voor ophalen electra&Converter data tbv zonnepanelen-electra.php

//** velden voor Electra info van DSMR server(verwijder // om te activeren)
// $dsmr_url='http://host-ip:1234';                     // URL voor DSMR inclusief
// $dsmr_apikey='IDkdjqljwdlkqjwdoiiqjdpockskskdxpF';   // APIKEY voor DSMR
// $DataURL = 'live-server-data-electra-dsmr.php'; 	    // URL voor ophalen electra&Converter data tbv zonnepanelen-electra.php

//#### einde aanpassing
```
screenshots:</b>
  ![Alt text](/docs/zonnepanelen-electra_LT_new.PNG?raw=true "Laptop")
  ![Alt text](/docs/zonnepanelen-electra_Mobiel.jpg?raw=true "Mobile portrait")

For more information see https://gathering.tweakers.net/forum/list_message/54439825#54439825
