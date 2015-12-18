<!DOCTYPE html>
<html lang="en">
<head>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
	<title>Gas prices in Austria</title>
</head>
<body>
	<script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<script>
		$('#navi a').click(function (e) {
			e.preventDefault()
			$(this).tab('show')
		});

		$(function() {
			$('#navi a[href="#super"]').tab('show');
		});
	</script>


<div class="container-fluid">
<?php

require_once('init.inc.php');

$fuels = $DB->getAll("SELECT * FROM fuels ORDER BY name ASC");

echo <<<EOT
<br/>
<ul id="navi" class="nav nav-tabs">
EOT;
foreach ($fuels as $fuel) {
	echo '<li role="presentation"><a href="#'.$fuel['name'].'" aria-controls="'.$fuel['name'].'" role="tab" data-toggle="tab">'.ucfirst($fuel['name']).'</a></li>';
}
echo <<<EOT
</ul>
<div class="tab-content">
EOT;


$prev_fuel = '';
$table_opened = false;
foreach ($fuels as $fuel) {
	$prices = $DB->getAll("
		SELECT ts,price,s.name AS station_name,s.address
		FROM prices p 
		LEFT JOIN stations s ON p.station_id=s.id
		WHERE is_latest=1 AND fuel_id=".ei($fuel['id'])."
		ORDER BY fuel_id, station_name ASC");

	echo <<<EOT
<div role="tabpanel" class="tab-pane" id="{$fuel['name']}">
<table class="table table-hover">
<thead>
<tr>
<th>Station</th>
<th>EUR/l</th>
<th>Last update</th>
</tr>
</thead>
EOT;

	$lowest = 10;
	foreach ($prices as $price) {
		if ($price['price'] < $lowest)
			$lowest = $price['price'];
	}

	foreach ($prices as $price) {
		$cl = ($price['price'] == $lowest) ? 'class="success"' : '';

		echo <<<EOT
<tr $cl>
<td>{$price['station_name']}</td>
<td>{$price['price']}</td>
<td>{$price['ts']}</td>
</tr>
EOT;
	}
	echo <<<EOT
</table>
<!--
<h4>Today</h4>
<img class="img-responsive" src="data/img/{$fuel['id']}_1d.png" alt="1 day price graph of {$fuel['name']}" />
-->
<h4>Last week</h4>
<img class="img-responsive" src="data/img/{$fuel['id']}_1w.png" alt="1 week price graph of {$fuel['name']}" />
<h4>Last month</h4> 
<img class="img-responsive" src="data/img/{$fuel['id']}_1m.png" alt="1 month price graph of {$fuel['name']}" />
</div>
EOT;
}

?>
</div>
</body>
</html>
