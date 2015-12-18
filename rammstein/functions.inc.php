<?php

function fetchHtml($url, $referer=false) {

	$info = parse_url($url);
	if (empty($info['host']))
		return false;

	$cookies = "data/cookies_".str_replace('-','.',sanitize($info['host'])).".txt";
	$agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1';
	$headers = array(
		'ACCEPT: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'ACCEPT_LANGUAGE: en-us,en;q=0.5',
		'CACHE_CONTROL: max-age=0',
	);

	$c = curl_init();
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($c, CURLOPT_VERBOSE, false);
	@curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($c, CURLOPT_USERAGENT, $agent);
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_COOKIEJAR, $cookies);
	curl_setopt($c, CURLOPT_COOKIEFILE, $cookies);
	if ($referer !== false)
		curl_setopt($c, CURLOPT_REFERER, $url1);

	$res = curl_exec($c);
	if (curl_getinfo($c, CURLINFO_HTTP_CODE) >= 300) {
		return false;
	}

	return $res;
}

function sanitize($string, $force_lowercase = true, $anal = false) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                   "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
    return ($force_lowercase) ?
        (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
            strtolower($clean) :
        $clean;
}

define("RRD_STEP", 900);
define("RRD_SAMPLES_PER_DAY", 96);

function rammsteinCreateRRD($station_id,$fuel_id,$start=0) {
	if (!$start)
		$start = "now";

	$rrd = rammsteinGetRRDPath($station_id, $fuel_id);

	$step = RRD_STEP;
	$samples = intval(RRD_SAMPLES_PER_DAY*1.1); 
	$samples1 = $samples;
	$samples2 = 960;

	$cmd = <<<EOT
	rrdtool create "$rrd" \
		--start $start \
		--step $step \
		DS:price:GAUGE:900:0:U \
		RRA:LAST:0.99999:1:768 \
		RRA:MIN:0.99999:1:768 \
		RRA:MAX:0.99999:1:768 \
		RRA:LAST:0.99999:10:384 \
		RRA:MIN:0.99999:10:384 \
		RRA:MAX:0.99999:10:384
EOT;

	echo "$cmd\n";

	unlink($rrd);
	echo system($cmd);
}

function rammsteinUpdateRRD($station_id, $fuel_id, $price, $time=0) {
	if ($time == 0) 
		$time = "now";

	$rrd = rammsteinGetRRDPath($station_id, $fuel_id);

	$cmd = "rrdtool update $rrd $time:$price";

	echo system($cmd);
}

function rammsteinGetRRDPath($station_id, $fuel_id) {
	return 'data/rrd/'.$station_id.'_'.$fuel_id.'.rrd';
}
