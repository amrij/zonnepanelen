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
// versie: 1.25
// auteur: André Rijkeboer
// datum:  29-03-2019
// omschrijving: configuratie bestand

// gegevens voor het openen van de database
$host = '192.168.1.181'; // IP adres waar de database staat (standaard localhost of 127.0.0.1)
$port = '3306'; // poort database (standaard 3306)
$user = 'gebruiker'; // gebruiker
$passwd = 'password'; // paswoord gebruiker
$db = 'solaredge'; // database naam

// gegeven van de plaats waar de zonnepanelen staan
$lat = 51.9515885; //Latitude North
$long = 6.0045953; //Longitude East

//#### Toegevoegd voor P1 ElectriciteitsMeter informatie van Domoticz
$domohost = '192.168.0.??:8080';                        // ip:poort van domoticz
$domoidx = "123";                                       // device IDX voor de Electriciteits P1 meter
$ElecLeverancier = "Delta";                            // naam electra leverancier
$ElecDagGraph = '60';                                   // aantal dagen in grafiek
$ElecMaandGraph = '13';                                 // aantal maanden in grafiek
$DataURL = 'live-server-data-electra-p1_meter_table.php';     // URL voor ophalen electra&Converter data
$zonnesysteem_electra = "zonnesysteem-electra.gif";
//#### einde aanpassing

// Achtergrond image (in de img directory)
$zonnesysteem = "zonnesysteem.gif";

// aangeven vermogen op het paneel
$vermogen = 1; // 0 = nee, 1 = ja

// gegevens van het zonnepanelensysteem
$inverter = 3; // 1 voor enkel fase en 3 voor 3 fase inverter
$naam = "SolarEdge SE7k"; //naam van de inverter
// optimizer id en positie paneel, de richting van de panelen Vertikaal = 0, Horizontaal = 1
// en het serinummer van het paneel
//$op_id[id optimizer][inverter.string.paneelnummer][richting][id paneel][vermogen paneel]
$op_id[1] = ['2020B2E3','1.1.1',1,'D5',300];
$op_id[2] = ['2020B18C', '1.1.2',1,'D7',300];
$op_id[3] = ['2020B1B2', '1.1.3',1,'A3',300];
$op_id[4] = ['2020B353', '1.1.4',1,'46',300];
$op_id[5] = ['2020B202', '1.1.5',1,'F4',300];
$op_id[6] = ['2020B1FE', '1.1.6',1,'EF',300];
$op_id[7] = ['2020B2A5', '1.1.7',1,'97',300];
$op_id[8] = ['2020B1B0', '1.1.8',1,'A1',300];
$op_id[9] = ['2020B25B', '1.1.9',1,'4D',300];
$op_id[10] = ['2020B14E', '1.1.10',1,'3F',300];
$op_id[11] = ['2020B2CB', '1.1.11',1,'BD',300];
$op_id[12] = ['2020B1ED', '1.1.12',1,'DE',300];
$op_id[13] = ['2020B369', '1.1.13',1,'5C',300];
$op_id[14] = ['2020B34F', '1.1.14',1,'42',300];
$op_id[15] = ['2020B22F', '1.1.15',1,'21',300];
$op_id[16] = ['2020B161', '1.1.16',1,'52',300];
$op_id[17] = ['2020B3A4', '1.1.17',1,'97',300];
$op_id[18] = ['2020B25F', '1.1.18',1,'51',300];
$op_id[19] = ['2020B387', '1.1.19',1,'7A',300];
$op_id[20] = ['2020B362', '1.1.20',1,'55',300];
$op_id[21] = ['20212017', '1.1.21',1,'78',300];
$op_id[22] = ['20212166', '1.1.22',1,'C8',300];
$op_id[23] = ['20211F32', '1.1.23',1,'92',300];
$op_id[24] = ['20211FD6', '1.1.24',1,'36',300];
$aantal = count($op_id); // aantal zonnepanelen dat in database is opgenomen
?>
