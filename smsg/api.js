#!/usr/bin/nodejs

var express = require('express');
var https = require('https');
var fs = require('fs');
var bodyParser = require('body-parser');
var shell = require('shelljs');
var q = require('q');
var shellEscape = shell.shellEscape;


var options = {
	key: fs.readFileSync('/opt/smsg/server.key'),
	cert: fs.readFileSync('/opt/smsg/server.crt'),
};

var app = express()
	.use(bodyParser.json())
	.post('/send', (req, res) => {
		if (req.body.key !== 'CHANGEME') {
			res.status(403).send({'error':'invalid access token'});

		} else {
			var message = req.body.message,
				number = req.body.number;
			
			sendSms(number, message)
				.then(_ => res.status(200).send("Ok") )
				.catch(err => res.status(400).send("Message failed to send: " + err) )
				.progress(msg => console.log(msg));
		}
	});

var server = https
	.createServer(options, app)
	.listen(process.env.PORT);

var sendSms = function(number, message) {
	return q.promise((resolve, reject, notify) => {
		q.nextTick(_ => {
			notify('Send message to '+number);
			notify('Message text '+message);
			var cmd = "/opt/smsg/send_sms.sh " + esa(number) + ' ' + esa(message);
			var ret = shell.exec(cmd);
			if (ret.code !== 0) {
				return reject(ret.stderr.trim());
			} else {
				return resolve();
			}
		});
	});
};

var esa = function (cmd) { return '"' + cmd.replace(/"/g, "'") + '"' }
