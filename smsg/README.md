## Dirty SMS gateway using modemmanager

1. install dependencies
{{{
apt-get install git supervisor nodejs modemmanager #smstools
npm install shelljs q
}}}

2. `git clone` into /opt/smsg/

3. configure supervisor 
{{{
  cp supervisor.conf /etc/supervisor/conf.d/smsg.conf
}}}

4. generate crypto, change api keys, etc.

5. ...

6. profit?
