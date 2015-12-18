<?php
/** 
 * custom error handler
 *
 * $Id: error_handler.inc.php 53 2015-10-04 18:34:02Z johnny $
 */

if(!defined('E_STRICT')) 		define('E_STRICT', 2048);
if(!defined('E_RECOVERABLE_ERROR')) 	define('E_RECOVERABLE_ERROR', 4096);
if(!defined('E_DEPRECATED')) 		define('E_DEPRECATED',8192);

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

set_error_handler("error_handler",E_ALL^E_NOTICE^E_DEPRECATED);

function error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
	global $CONF;

	if ($errno == E_STRICT)
		return;

	$msg = "<pre>\n<b>";
	switch($errno){
	case E_ERROR:		
		$msg .= "Error";			
		break;
	case E_WARNING:		
		$msg .= "Warning";		
		break;
	case E_PARSE:		
		$msg .= "Parse Error";	
		break;
	case E_NOTICE:		
		$msg .= "Notice";			
		break;
	case E_CORE_ERROR:	
		$msg .= "Core Error";		
		break;
	case E_CORE_WARNING:
		$msg .= "Core Warning";	
		break;
	case E_COMPILE_ERROR:
		$msg .= "Compile Error";	
		break;
	case E_COMPILE_WARNING:
		$msg .= "Compile Warning";
		break;
	case E_USER_ERROR:	
		$msg .= "User Error";		
		break;
	case E_USER_WARNING:
		$msg .= "User Warning";	
		break;
	case E_USER_NOTICE:	
		$msg .= "User Notice";	
		break;
	case E_STRICT:		
		$msg .= "Strict Notice";	
		break;
	case E_RECOVERABLE_ERROR: 
		$msg .= "Recoverable Error";
		break;
	case E_DEPRECATED:
		$msg .= "Deprecated";
		break;
	default:				
		$msg .= "Unknown error ($errno)"; break;
	}

	$msg .= ":</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n";

	if(function_exists('debug_backtrace')){
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		foreach($backtrace as $i=>$l){
			$msg .= "[$i] in function <b>{$l['class']}{$l['type']}{$l['function']}</b>";
			if($l['file']) 
				$msg .= " in <b>{$l['file']}</b>";
			if($l['line']) 
				$msg .= ":<b>{$l['line']}</b>";
			$msg .= "\n";
		}
	}

	$msg .= "\n</pre>";

	// ak mame vypnuty debug, nechame problem len tak vyhnit (TODO: asi by sme mali robit mail)
	if (!DEBUG) 
		return;

	if (!empty($_SERVER['HTTP_HOST'])) {
		echo $msg;
	} else {
		echo strip_tags($msg);
	}
}

?>
