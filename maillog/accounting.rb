#! /usr/bin/ruby

require 'pp'
require 'time'

class UserStats
	:connection

	def initialize 
		@connection = {}
	end

	def connect user, ip, date
		@connection[user] = {} if not @connection[user]
		@connection[user][ip] = [] if not @connection[user][ip]
		@connection[user][ip].push date
	end

	def disconnect user, ip, time
		return if not @connection[user] or not @connection[user][ip]
		start_time = @connection[user][ip].pop
		puts "#{time} user #{user} @ #{ip} disconnected after " + (time - start_time).to_s + "seconds"
	end
end

f = File.new("mail.log","r");
stats = UserStats.new

while line = f.readline 
	next if not line =~ /(\w{3} \d+ \d{2}:\d{2}:\d{2}).*imapd-ssl: ([a-z]*),( user=([^,]+),?)?( ip=\[([^\]]+)\],?)?( port=\[([^\]]+)\],?)?/i

	cmd = $2
	date = Time.parse($1)
	user = $4 ? $4 : ""
	ip = $6 ? $6 : ""

	# pripojenia nas nezauijmaju
	case cmd
	when "Connect" then
		next
	when "LOGIN" then
		stats.connect(user, ip, date)
	when "LOGOUT", "TIMEOUT", "DISCONNECTED" then
		stats.disconnect(user, ip, date)
	end
end

