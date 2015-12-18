#!/usr/bin/env php
<?php

// fix path 
chdir(realpath(dirname(__FILE__)));
chdir('..');

require_once('init.inc.php');

$fuels = $DB->getAll("SELECT * FROM fuels ORDER BY name ASC");
$stations = $DB->getAll("SELECT * FROM stations ORDER BY name ASC");
foreach ($fuels as $fuel) {
	foreach($stations as &$station) {
		$prices = $DB->getAll("SELECT * FROM prices WHERE station_id=".ei($station['id'])." AND fuel_id=".ei($fuel['id'])." ORDER BY ts ASC");

		$start_t = strtotime($prices[0]['ts'])-450;
		$end_t = strtotime($prices[sizeof($prices)-1]['ts']);
		echo "start $start_t\n";

		rammsteinCreateRRD($station['id'], $fuel['id'], $start_t);

		foreach ($prices as $price) {
			$t = strtotime($price['ts']);
			$t = (intval($t/900))*900;
			rammsteinUpdateRRD($station['id'], $fuel['id'], $price['price'], $t);
		}
	}
}

?>
