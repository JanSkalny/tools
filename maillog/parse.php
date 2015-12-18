#!/usr/bin/php
<?php

require_once('database.inc.php');

$CONF['db']['default'] = 'sqlite://./mail.db';
$INCOMING = array();
$DB=dbConnect('default');
$DB->query("DELETE FROM messages");

$f = fopen('php://stdin', 'r');

while (!feof($f)) {
	$line = fgets($f);	
	try {
		parseMaillogLine($line);
	} catch(Exception $ex) {
		echo "$ex\n\n";
	}
}

fclose ($f);

echo "incoming:\n";
print_r($INCOMING);

echo "messages:\n";
$messages = $DB->getAll("SELECT * FROM messages LIMIT 100");
print_r($messages);


function parseMaillogLine($line)
{
	global $INCOMING, $DB;

	// date		hostname	service		pid		message
	if (!preg_match('/([a-zA-Z]{3}[ ]+\d{1,2} \d{2}:\d{2}:\d{2}) ([^ ]*) ([^\[]*)\[(\d*)\]: (.*)$/', $line, $m))
		return false;
	
	$date = strtotime($m[1]);
	$service = $m[3];
	$msg = $m[5];

	switch ($service) {
	case 'postfix/smtpd':
		if (preg_match('/^([^:]*): client=([^ ]*)/', $msg, $m)) {
			$INCOMINT[$m[1]] = array();
			$INCOMING[$m[1]]['message_id'] = $m[1];
			$INCOMING[$m[1]]['connect'] = $m[2];
			$INCOMING[$m[1]]['arrived'] = $date;
			$INCOMING[$m[1]]['state'] = 'incoming';
		}
		break;

	case 'postfix/cleanup':
		if (preg_match('/^([^:]*): message-id=<([^>]*)>/', $msg, $m)) {
			// vytvorime si v db zaznam o tejto sprave
			try {
				$mail =& $INCOMING[$m[1]];
				dbInsert('messages', array(
					'message_id'=>es($m[2]),
					'processing_id'=>es($m[1]),
					'state'=>'amavis',
					'received'=>es($mail['arrived']),
					//'connect_from'=>es($mail['connect']),
				));
			} catch (Exception $e) {
				//echo "$e\n\n";
				// spravu uz mame v zozname, takze bud niekto spoofuje idcka a my 
				// sa len tak nechame alebo je to druha cast prvej spravy (vysledok z amavisu)
				$INCOMING[$m[1]]['clone'] = 1;
			}
		}

		// z Cc Bcc a To headerov vykradneme cielove adresy (to_addr)
		if (preg_match('/([^:]*): warning: header (Cc|Bcc|To): ([^\[]*)\[/', $msg, $m)) {
			$mail =& $INCOMING[$m[1]];
			if (!preg_match_all('/<[^>]*>/', $m[3], $addrs))
				continue;
			foreach ($addrs as $addr)
				$mail['to_addr'] .= $addr[0].',';
		}
		break;

	case 'postfix/qmgr':
		// precitame zo spravy pocet prijmatelov, odosielatela a jej velkost
		if (preg_match('/^([^:]*):.*from=<[^>]*>/', $msg, $m)) {
			$id = $m[1];

			// ak je to klonik (nieco co vypadlo z amavisu) tak to nechame tak...
			$mail =& $INCOMING[$id];
			if ($mail['clone'])
				continue;

			if (preg_match('/from=([^, ]*)[, ]/', $msg, $m))
				$mail['from_addr'] = $m[1];
			if (preg_match('/size=([\-\d]*)/', $msg, $m))
				$mail['size'] = $m[1];
			if (preg_match('/nrcpt=([\d]*)/', $msg, $m))
				$mail['nrcpt'] = $m[1];

			$DB->query(
				"UPDATE messages SET 
					size='".ei($mail['size'])."', 
					from_addr='".es($mail['from_addr'])."', 
					to_addr='".es($mail['to_addr'])."' 
				WHERE processing_id='".es($id)."'");
		}

		// sprava bola odstranena z queue... odstranime si ju z nasich zoznamov
		// INCOMING a referenciu v DB
		if (preg_match('/^([^:]*): removed/', $msg, $m)) {
			$DB->query("UPDATE messages SET processing_id = NULL WHERE processing_id='".es($m[1])."'");
			unset($INCOMING[$m[1]]);
		}
		break;

	case 'postfix/smtp':
		if (preg_match('/^([^:]*):/', $msg, $m)) {
			$id = $m[1];
			$mail =& $INCOMING[$id];

			// ak sme nepozreli este vsetkych prijimatelov tak nas stale trapia
	/*		if ($mail['nrcpt'] > 0) {
				if (preg_match('/to=(<[^>]*>)/', $msg, $m))
					$mail['to_proc'] .= $m[1].',';
				if (--$mail['nrcpt'] == 0)
					$DB->query("UPDATE messages SET to_proc='".es($mail['to_proc'])."' WHERE processing_id='".es($id)."'");
	}*/

			// ak je to lokalny relay (amavis) tak odignorujeme co sa stalo
			if (preg_match('/relay=127.0.0.1/', $msg))
				continue;

			//TODO: inac by sme mali zobrat status=(status) (status_msg)
			if (preg_match('/status=([^ ]*) \((.*)\)/', $msg, $m)) {
				$DB->query("UPDATE messages SET status='".es($m[1])."', status_msg='".es($m[2])."' WHERE processing_id='".es($id)."'");
			}

		}
		break;

	case 'amavis':
		if (preg_match('/\([^\)]*\) (Blocked|Passed) ([^,]*).*Message-ID: <([^>]*)>/', $msg, $m)) {
			$action = $m[1];
			$status = $m[2];
			$message_id = $m[3];
			if ($action == 'Blocked') {
				$state = 'done';
				$quarantine = '';
				if (preg_match('/quarantine: ([^ ,]*)[, ]/', $msg, $m))
					$quarantine = $m[1];
			} else {
				$state = 'scanned';
				$queued_as = '';
				if (preg_match('/queued_as: ([^ ,]*)[, ]/', $msg, $m))
					$queued_as = $m[1];
			}

			$DB->query( 
				"UPDATE messages SET 
					amavis_action='".es($action)."', 
					amavis_status='".es($status)."', 
					state='".es($state)."',
					quarantine='".es($quarantine)."',
					processing_id='".es($queued_as)."'
				WHERE message_id='".es($message_id)."'");
		}
		
		break;

	}
	//echo "$line\n";
}

?>
