<?php
/**
 * common database functions
 * TODO list:
 * - get rid of all dbInsert, dbReplace calls and use MDB2::autoExecute feature
 * 
 * $Id: database.inc.php 53 2015-10-04 18:34:02Z johnny $
 */

require_once('PEAR.php');
require_once('PEAR/Exception.php');
require_once('MDB2.php');

// override pear error reporting
function PEAR_ErrorToPEAR_Exception($err) {
	if ($err->getCode()) { // FIXME: toto obcas nevyjde! Undefined property: MDB2_Error::$getCode
		throw new PEAR_Exception($err->getMessage(), $err->getCode());
	}
	throw new PEAR_Exception($err->getMessage());
}
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'PEAR_ErrorToPEAR_Exception');
//TODO: PEAR::setErrorHandling(PEAR_ERROR_EXCEPTION);

/**
 * create database object 
 * 
 * TODO: don't even think about doing connection caching here... pear db|mdb2 does thah for you
 *
 * @param String $name db name, key from $CONF['db'] array
 * @return PearDB object on success
 * @throws Exception if object creation failed 
 */
function dbConnect($name='default') {
	global $CONF;

	if (!isset($CONF['db'][$name]))
		throw new Exception("db error - no such record '$name'");

	$db = dbConnectDSN($CONF['db'][$name], $options);

	return $db;
}


function dbConnectDSN($dsn, $options = false)
{
	// create database connection
	$db = MDB2::connect($dsn, $options);

	$db->loadModule('Extended');

	// and fetch result as associative arrays
	$db->setFetchMode(MDB2_FETCHMODE_ASSOC);

	$db->query("SET NAMES utf8");

	return $db;
}


/**
 * insert data into table 
 *
 * don't forget to sanitize field values before inserting.
 * THIS FUNCTION DOES NOT ESCAPE ANYTHING.
 *
 * @param String $table name of table
 * @param Array $fields array of fields (key is field name)
 *
 * @return Int ID of inserted item if successful
 * @throws Exception on error
 */
function dbInsert($table, $fields, $ignore=false) {
	global $DB;

	if (!$DB)
		return false;

	$op = "INSERT".($ignore?' IGNORE':'')." INTO $table ";
	$query = $op._dbGenerateInsertReplace($fields);

	// execute and verify
	$e = $DB->query($query);
	if (MDB2::isError($e))
		throw new Exception("$op failed:".$e->getMessage()); //todo: nemali by sme koli kompatibilite skor spravit return false?

	// fetch last inserted id
	$id = $DB->lastInsertID();

	return $id;
}

function dbInsertIgnore($table, $fields) {
	return dbInsert($table, $fields, true);
}

/**
 * insert data into table 
 *
 * don't forget to sanitize field values before inserting.
 * THIS FUNCTION DOES NOT ESCAPE ANYTHING.
 *
 * FIXME: lastInsertID nevracia spravnu hodnotu, mysql_insert_id je dratove riesenie 
 *
 * @param String $table name of table
 * @param Array $fields array of fields (key is field name)
 *
 * @return Int ID of inserted item if successful
 * @throws Exception on error
 */
function dbReplace($table, $fields)
{
	global $DB;

	if (!$DB)
		return false;

	$query = "REPLACE INTO $table "._dbGenerateInsertReplace($fields);

	// execute and verify
	$e = $DB->query($query);
	if (MDB2::isError($e)) 
		throw new Exception("replace into $table failed: ".$e->getMessage());

	// fetch last inserted id
	$id = $DB->lastInsertID();

	return $id;
}

function dbUpdate($table, $fields, $where_sql)
{
	global $DB;

	if (!$DB)
		return false;

	$query = "UPDATE $table SET ";
	foreach ($fields as $key=>$value) 
		$query .= "$key=".(($value === null) ? "NULL," : "'$value',");
	$query[strlen($query)-1] = " ";
	$query .= "WHERE $where_sql";

	// execute and verify
	$e = $DB->query($query);
	if (MDB2::isError($e)) 
		throw new Exception("update $table failed: ".$e->getMessage());

	return;
}

/** 
 * internal db function - generate data part of insert/replace query
 *
 * @param Array $fields array of fields (key is fieldname)
 * @return String part of query
 */
function _dbGenerateInsertReplace($fields) {
	$query = " (";
	foreach ($fields as $key=>$value)
		$query .= "$key,";
	$query[strlen($query)-1] = ")";

	$query .= " VALUES (";
	foreach ($fields as $key=>$value) 
		$query .= ($value === null) ? "NULL," : "'$value',";
	$query[strlen($query)-1] = ")";

	return $query;
}

/*
 * sanitize db input - escape integer value
 *
 * @param Int $value integer value
 *
 * @return intval'd $value
 */
function ei($value) {
	return intval($value);
}

function ein($value) {
	if ($value === null)
		return null;
	if (strlen($value) == 0)
		return null;

	return intval($value);
}


/*
 * sanitize db input - escape string value
 *
 * @param String $value raw value
 *
 * @return String escaped string value
 */

function es($value) {
	global $DB;
	
	if (!$DB)
		return false;

	return $DB->escape($value);
}

function esn($value) {
	global $DB;

	if (!$DB)
		return false;

	if ($value === null)
		return null;

	return $DB->escape($value);
}


/**
 * sanitize db input - escape float value
 *
 * @param Float $value float value
 *
 * @return Float floatval'd value
 */
function ef($value) {
	return floatval($value);
}
function efn($value) {
	if ($value === null)
		return null;
	if (strlen($value) == 0)
		return null;

	return floatval($value);
}

function dbRow($data, $string_fields, $int_fields=array(), $real_fields=array(), $data_prefix="")
{
	$row = array();

	foreach ($string_fields as $field) 
		$row[$field] = esn($data[$data_prefix.$field]);
	foreach ($int_fields as $field)
		$row[$field] = ein($data[$data_prefix.$field]);
	foreach ($real_fields as $field)
		$row[$field] = efn($data[$data_prefix.$field]);

	return $row;
}

?>
