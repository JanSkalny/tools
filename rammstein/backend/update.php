#!/usr/bin/env php
<?php

// fix path 
chdir(realpath(dirname(__FILE__)));
chdir('..');

require_once('init.inc.php');

$stations = $DB->getAll("SELECT id,name,external_id FROM stations");

foreach ($stations as $station) {
	$prices = fetchStationPrices($station['external_id']);
	if (empty($prices) || !array_key_exists('super', $prices)) 
		continue;

	foreach ($prices as $fuel=>$price) {
		//echo "$fuel $price {$station['name']}\n";
		$fuel_id = $DB->getOne("SELECT id FROM fuels WHERE name='".es($fuel)."' LIMIT 1");
		if (empty($fuel_id))
			$fuel_id = dbInsert('fuels', array('name'=>es($fuel)));

		$DB->query("UPDATE prices SET is_latest=0 WHERE fuel_id='".ei($fuel_id)."' AND station_id=".ei($station['id']));
		dbInsert('prices', array(
			'station_id'=>ei($station['id']),
			'fuel_id'=>ei($fuel_id),
			'price'=>ef($price),
			'is_latest'=>1,
		));

		$t = (intval(time()/900))*900;
		rammsteinUpdateRRD($station['id'], $fuel_id, $price, $t);
	}
}

function fetchStationPrices($station_id) {
	$url = 'http://www.oeamtc.at/spritapp/ShowGasStation.do?spritaction=show&gsid='.intval($station_id);
	$html = fetchHtml($url);
	#file_put_contents('dump/'.$station_id.'_'.time().'.html', $html);

	$html = preg_replace("/[\r\n\t ]+/", " ", $html);
	if (!preg_match('#pricesBox.*<tr>.*?Super.*?</tr>.*</div>#', $html, $m)) 
		return false;
	if (!preg_match('#<table class="infoTable stationDetailsInfoTable">.*?</table>#', $m[0], $m)) 
		return false;
	$m[0] = utf8_encode($m[0]);
	$xml = simplexml_load_string($m[0]);

	$prices = array();
	foreach ($xml->tr as $row) {
		$label = "".$row->td[0]->div;
		$label = strtolower(trim($label));

		$value = "".$row->td[1]->div;
		$value = strtolower(trim($value));
		$value = str_replace(',','.',$value);
		$value = floatval($value);

		if ($value != 0)
			$prices[$label] = $value;
	}
	if (empty($prices)) 
		return false;

	usleep(rand(150,450)*1000);

	return $prices;
}

?>
