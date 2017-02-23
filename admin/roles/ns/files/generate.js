#!/usr/bin/node
        
var request = require('request')
var fs = require('fs')
var q = require('q')
var https = require('https')
var dateformat = require('dateformat')
var path = require('path')
var conf = require('nconf')
var url = require('url')
var extend = require('util')._extend

conf.file({ file: path.join(__dirname, 'config.json') })

var api_url = conf.get('api_url')
var api_key = conf.get('api_key')

var agent = new https.Agent
agent.maxSockets = 1

var ts=dateformat(new Date, 'yymmddHHMM')

let opts = {
	url: api_url+'/api/v1/dns-zones',
	headers: { "X-Auth-Key": api_key },
	json: true, 
}
request(opts)
	.on('data', (data) => { 
		let zones = JSON.parse(data)
		if (!zones || !zones.zones)
			throw "no zones retreived!"
		get_next_zone(zones.zones)
		create_master(zones.zones)
	})

function get_next_zone(zones) {
	if (zones.length == 0)
		return

	let zone = zones.shift()
	let uuid = zone.uuid

	let opts = extend(url.parse(api_url), {
		path:'/api/v1/dns-zones/'+uuid,
		headers: { "X-Auth-Key": api_key },
		agent: agent
	})

	var req = https.get(opts, function(res) {
		if (res.statusCode != 200) 
			throw "failed to get zone: "+JSON.stringify(res.headers)
		res.setEncoding('utf8')
		res.on('data', (data) => { saveZone(zone.zone, JSON.parse(data).records)})
		res.on('end', (_) => { get_next_zone(zones); })
	})
	req.on('error', (e) => { throw "failed to get zone: "+e.message })
	req.end()
}

function saveZone(zone, records) {
	console.log("saving zone", zone)

	let s = fs.createWriteStream("/etc/bind/master/"+zone+".conf")
	s.once('open', function(fd) {
		s.write("$TTL 1h\n")
		s.write(zone+". IN SOA ns.netvor.sk. admin.netvor.sk. ( "+ts+" 10800 3600 432000 38400 )\n")
		for (let i=0; i!=records.length; i++) {
			let r = records[i]
			s.write(r.name+" IN "+r.type+" "+r.value+"\n")
		}
		s.end()
	})
}

function create_master(zones) {
	console.log("generate master.conf")

	let s = fs.createWriteStream("/etc/bind/master.conf")
	s.once('open', function(fd) {
		for (let i=0; i!=zones.length; i++) {
			let zone = zones[i].zone
			s.write("zone \""+zone+"\" {\n")
			s.write("  zone-statistics yes;\n")
			s.write("  type master;\n")
			s.write("  file \"master/"+zone+".conf\";\n")
			s.write("};\n")
		}
		s.end()
	})

}
