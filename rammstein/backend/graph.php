#!/usr/bin/env php
<?php

// fix path 
chdir(realpath(dirname(__FILE__)));
chdir('..');

require_once('init.inc.php');

$fuels = $DB->getAll("SELECT * FROM fuels ORDER BY name ASC");
$stations = $DB->getAll("SELECT * FROM stations ORDER BY name ASC");
foreach ($fuels as $fuel) {
	$fuel_name = ucfirst($fuel['name']);
	foreach (array('1d','1w','1m') as $duration) {
		$path = 'data/img/'.$fuel['id'].'_'.$duration.'.png';


		switch($duration) {
		case '1d': $xgrid = "HOUR:1:HOUR:6:HOUR:6:0:%D\n%A\n%H:%M"; break;
		case '1w': $xgrid = "HOUR:12:DAY:1:DAY:1:0:%D\n%A\n%H:%M"; break;
		case '1m': $xgrid = "DAY:1:DAY:7:DAY:7:0:%D\n%A\n%H:%M"; break;
		}

		switch($duration) {
		case '1d': $step = 24; break;
		case '1w': $step = 315; break;
		case '1m': $step = 45*7*5; break;
		}

		switch($duration) {
		case '1d': $t_duration = 24*3600; break;
		case '1w': $t_duration = 24*3600*7; break;
		case '1m': $t_duration = 24*3600*30; break;
		}

		$end = time();
		$end -= $end%900;
		$start = $end - $t_duration;

		$cmd = <<<EOT
		rrdtool graph $path \
			--title "Price of $fuel_name (past $duration)" \
			--vertical-label "cents/l" \
			--width '960' \
			--height '350' \
			--start '$start' \
			--end '$end' \
			--step '$step' \
			--y-grid 1:1 \
			--x-grid '$xgrid' \
			'COMMENT: \\n' \
			'COMMENT: \\n' \
			'COMMENT: \\n' \

EOT;

		//$colors = array('#F00','#0F0','#00F','#F0F','#0FF','#F00');
		$colors = array('#A0BED9','#88A61B','#F29F05','#6D0501','#D92525','#000');

		$max_len = 0;
		foreach ($stations as &$station) {
			if (strlen($station['name']) > $max_len)
				$max_len = strlen($station['name']);
		}

		$i = 0;
		foreach ($stations as &$station) {
			$color = $colors[$i++%sizeof($colors)];
			$colorx = $color.'10';
			$rrd = rammsteinGetRRDPath($station['id'], $fuel['id']);
			$id = $station['id'];
			$space = str_repeat(' ', $max_len - strlen($station['name']));
			$cmd .= <<<EOF
			'DEF:p$id=$rrd:price:LAST' \
			'CDEF:sp$id=p$id,100,*' \
			'CDEF:ap$id=sp$id,UN,PREV(sp$id),sp$id,IF' \
			'LINE1:ap$id$color:{$station['name']}' \
			'COMMENT: $space' \
			'GPRINT:p$id:LAST:Current\:%4.3lf%s' \
			'GPRINT:p$id:MIN:Minimum\:%4.3lf%s' \
			'GPRINT:p$id:MAX:Maximum\:%4.3lf%s\l' \

EOF;
//			'AREA:sp$id$colorx' \

		}
		$cmd .= " > /dev/null\n";

		unlink($path);
		system($cmd);

		echo $cmd;
	}
}

