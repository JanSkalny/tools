<?php
/**
 * custom exception handler 
 *
 * $Id: exception_handler.inc.php 53 2015-10-04 18:34:02Z johnny $
 */

// install hook
set_exception_handler("exceptionHandler");

function exceptionHandler($e) {
	global $CONF;

	$msg = 
		"<div style=\"overflow:auto;\">".
		"<h1>system made a boo-boo</h1><br/>\n";
	
	$debug =
		"<pre>".
		"<b>".date("r")."</b>\n".
		"<h3>*Unhandled exception*</h3>\n".
		"in file: <b>{$e->getFile()}</b>:<b>{$e->getLine()}</b>\n".
		"message: <b>{$e->getMessage()}</b>\n".
		"code: <b>{$e->getCode()}</b>\n".
		"stack trace:\n<b>".$e->getTraceAsString()."</b>\n\n\n".
		"<b><u>and some more info, just for you!</u></b>\n\n".
		"<b>GET:</b>\n".var_export($_GET, true)."\n\n".
		"<b>POST:</b>\n".var_export($_POST, true)."\n\n".
		"<b>FILES:</b>\n".var_export($_FILES, true)."\n\n".
		"<b>COOKIE:</b>\n".var_export($_COOKIE, true)."\n\n".
		"<b>SERVER:</b>\n".var_export($_SERVER, true)."\n\n".
		"<b>SESSION:</b>\n".var_export($_SESSION, true)."\n\n".
		"</pre>";

	//TODO: write to log
	$text = str_replace("\n.\n", "\n..\n", strip_tags ($debug));

	if (isset($CONF['debug_mail_from']) && isset($CONF['debug_mail_to']))
		mail($CONF['debug_mail_to'],'unhandled exception',$text,'From: <'.$CONF['debug_mail_from'].'>');


	// if debugging is disabled, delete error message details and replace it with more convinient junk
	if (!DEBUG) {
		// make sure shared emssage encryption key is set.. otherwise use "default"
		if (empty($CONF['msg_enc_key']))
			$CONF['msg_enc_key'] = "default";

		$debug = @mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $CONF['msg_enc_key'], $debug, MCRYPT_MODE_ECB);
		$debug = "<pre>".wordwrap (base64_encode($debug), 70, "\n", true)."</pre>";
		//to reverse it:
		//TODO: somewhere in privacy
		// $debug = mcrypt_decrypt (MCRYPT_RIJNDAEL_256,$CONF['msg_enc_key'],base64_decode($debug),MCRYPT_MODE_ECB);
	}

	$msg .= $debug;
	$msg .= "</div>";

	// zobrazime vysledok

	ob_clean();
	ob_end_clean();

	if (!empty($_SERVER['HTTP_HOST'])) {
		header("HTTP/1.0 500 Internal Server Error(Unhandled Exception)");
		echo $msg;
	} else {
		echo strip_tags($debug);
	}

	return 1;
}

?>
