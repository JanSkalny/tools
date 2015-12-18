#!/usr/bin/env php
<?php

$slovesa = file('defs/slovesa');
$podstatne = file('defs/podstatne_mena');
$pridavne = file('defs/pridavne_mena');

$SYMBOLS = array('!',',','.','-','+','/','@','?');

$PARTS = array(
	array( 'sloveso'=>0.0 ),
	array( 'pridavne'=>1.0 ),
	array( 'symbol'=>0.3 ),
	array( 'podstatne'=>1.0 ),
	array( 'cislo'=>1.0 ),
	array( 'cislo'=>0.7 ),
	array( 'cislo'=>0.3 ),
	);

//shuffle_assoc($PARTS);
for ($i=0; $i!=10; $i++) {
	foreach ($PARTS as $xxx) {
		list($part,$prob) = each($xxx);
		if (mt_rand()/mt_getrandmax() <= $prob) {
			switch($part) {
			case 'cislo':
				echo "".mt_rand(0,9);
				break;
			case 'symbol':
				echo $SYMBOLS[mt_rand(0,sizeof($SYMBOLS))];
				break;
			case 'pridavne':
			case 'podstatne':
			case 'sloveso':
				echo fixed($$part);
				break;
			}
		}
	}
	echo "\n";
}

function fixed($array) {
	do {
		$word = $array[array_rand($array)];
		$word = ansiify($word);
		$word = caseify($word);
		$word = destroy($word);
	} while (strlen($word) < 4 || strlen($word) > 9);
	return $word;
}

function destroy($word) {
	global $dm_prev;

	$x = mt_rand(2, strlen($word)-1);
	
	do {
		$dm = mt_rand()%3;
	} while ($dm_prev == $dm);
	$dm_prev = $dm;

	switch($dm) {
	case 1:
		$res = substr($word,0,$x).substr($word,$x+1,strlen($word)-1);
		break;

	case 2:
		$res = substr($word,0,$x).$word[$x].$word[$x+1].substr($word,$x,strlen($word)-1);
		break;
	}

	return $res;
}

function caseify($str) {
	global $cm_prev;

	$str = strtolower($str);
	$len = strlen($str);

	do {
		$cm = mt_rand()%4;
	} while ($cm == $cm_prev);
	$cm_prev = $cm;

	switch($cm) {
	case 1:
		$str = strtoupper($str);
		break;
	case 2:
	case 3:
		$str[0] = flip($str[0]);
		break;
	//case 2:
	//	$str[$len-1] = flip($str[$len-1]);
	//	break;
	}

	return $str;
}

function flip($ch) {
	$x = ord($ch);
	if ($x >= 65 && $x <= 90)
		return chr($x+(ord('a')-ord('A')));
	if ($x >= 97 && $x <= 122)
		return chr($x-(ord('a')-ord('A')));
	return $chr;
}

function ansiify($word) {
	$word = mb_convert_encoding($word,'UTF-8','iso-8859-2');
	$word = iconv("utf-8", "ascii//TRANSLIT", $word);
	$word = preg_replace('/[\^\'"]/','',$word);
	$word = preg_replace('/^(.*)\/.*$/','\1',$word);

	return trim($word);
}

function shuffle_assoc($list) {
	if (!is_array($list)) 
		return $list;

	$keys = array_keys($list);
	shuffle($keys);
	$random = array();
	foreach ($keys as $key)
		$random[$key] = $list[$key];

	return $random;
} 

?>
