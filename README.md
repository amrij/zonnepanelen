# zonnepanelen
Website which shows telemetry data from the TCP traffic of SolarEdge PV inverters
1. Inleiding
@Jerrythafast heeft een heel goed werkend programma gemaakt voor het vastleggen van de gegevens van SolarEdge omvormers. Het blijkt dat het weergeven van de data op een website voor niet iedereen even eenvoudig is. Daarom heb ik mijn zeer uitgebreide website vereenvoudigd, modulair gemaakt, en geschikt voor enkel- en 3fase omvormers gemaakt.

Om het hanteerbaar te houden heb ik het maximum aantal panelen beperkt tot 33. Indien dit niet te weinig is, is dit misschien iets voor u.


1.1 Hoe werkt het?
De website is gebaseerd op de database van dit topic. Om de website te configureren moeten config.php en css/zonnepanelen.css worden aangepast. Daarna kan de website gestart worden met zonnepanelen.php.


2. Aanpassen config.php
in config.php is het volgende vastgelegd:
gegevens voor de toegang tot de database;
de latitude en longitude van de plaats waar de panelen staan;
de startdatum van de registratie van de gegevens;
type van de inverter (1 of 3 fase);
de naam van de inverter (typenummer);
het aantal panelen;
de optimizer id en nummer van het paneel en de richting.
De config.php ziet er volgt uit.

code:
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
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
// versie: 1.20
// auteur: André Rijkeboer
// datum:  19-01-2019
// omschrijving: configuratie bestand

// gegevens voor het openen van de database
$host = '192.168.1.81'; // IP adres waar de database staat (standaard localhost of 127.0.0.1)
$port = '3306'; // poort database (standaard 3306)
$user = 'gebruiker'; // gebruiker
$passwd = 'paswoord'; // paswoord gebruiker
$db = 'p1'; // database naam
$begin = '2015-12-18'; // Data beschikbaar vanaf 2015-12-18

// gegeven van de plaats waar de zonnepanelen staan
$lat = 51.9515885; //Latitude North
$long = 6.0045953; //Longitude East

// gegevens van het zonnepanelensysteem
$inverter = 3; // 1 voor enkel fase en 3 voor 3 fase inverter
$naam = "SolarEdge SE7k"; //naam van de inverter
$aantal = 22; // aantal zonnepanelen dat in database is opgenoen 
// (max = 33 bij groter aantal moet in het html blok van zonnepanelen het aantal verhoogd worden)
// optimizer id en positie paneel en richting van de panelen Vertikaal = 0, Horizontaal = 1
$op_id[1] = ['2020B2E3','1.1.1',0]; //$op_id[id optimizer][inverter.string.paneelnummer][richting] 
$op_id[2] = ['2020B18C', '1.1.2',0];
$op_id[3] = ['2020B1B2', '1.1.3',0];
$op_id[4] = ['2020B353', '1.1.4',0];
$op_id[5] = ['2020B202', '1.1.5',0];
$op_id[6] = ['2020B1FE', '1.1.6',0];
$op_id[7] = ['2020B2A5', '1.1.7',0];
$op_id[8] = ['2020B1B0', '1.1.8',0];
$op_id[9] = ['2020B25B', '1.1.9',0];
$op_id[10] = ['2020B14E', '1.1.10',0];
$op_id[11] = ['2020B2CB', '1.1.11',0];
$op_id[12] = ['2020B1ED', '1.1.12',0];
$op_id[13] = ['2020B369', '1.1.13',0];
$op_id[14] = ['2020B34F', '1.1.14',0];
$op_id[15] = ['2020B22F', '1.1.15',0];
$op_id[16] = ['2020B161', '1.1.16',0];
$op_id[17] = ['2020B3A4', '1.1.17',0];
$op_id[18] = ['2020B25F', '1.1.18',0];
$op_id[19] = ['2020B387', '1.1.19',1];
$op_id[20] = ['2020B362', '1.1.20',1];
$op_id[21] = ['20212017', '1.1.21',1];
$op_id[22] = ['20212166', '1.1.22',1];
$op_id[23] = ['20211F32', '1.1.23',0];
$op_id[24] = ['20211FD6', '1.1.24',0];
$op_id[25] = ['0', '1.1.25',1]; 
$op_id[26] = ['0', '1.1.26',1];
$op_id[27] = ['0', '1.1.27',1];
$op_id[28] = ['0', '1.1.28',1];
$op_id[29] = ['0', '1.1.29',1];
$op_id[30] = ['0', '1.1.30',1];
$op_id[31] = ['0', '1.1.31',1];
$op_id[32] = ['0', '1.1.32',1];
$op_id[33] = ['0', '1.1.33',1];
?>



3. Aanpassen css/zonnepanelen.css
in css/zonnepanelen.css is het volgende vastgelegd:
[list]
• div.box_Zonnepanelen : plaats, afmetingen en eventuele rotatie; 
• div.box_Zonnepaneel_x: plaats en afmetingen in % van de afmetingen van div.box_Zonnepanelen 
/list]

Een gedeelte van css/zonnepanelen.css ziet er volgt uit:

code:
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
div.box_Zonnepanelen {
    
    left: 25px; 
    top: 40px;
    width: 202px;
    height: 599px;
    position: absolute;
    -webkit-transform:rotate(0deg); 
    transform:rotate(0deg); 

}

div.box_Zonnepaneel_1 {
    left: 75%;
    top: 0%;
    width: 23.70%;
    height: 12.02%;
    position: absolute;
    z-index: -90;
    
}



