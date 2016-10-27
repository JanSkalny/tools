<VirtualHost *:80>
  ServerName unifycore.net
  ServerAlias *.unifycore.net
  ServerAlias unifycore.org
  ServerAlias *.unifycore.org
  ServerAlias unifycore.fiit.stuba.sk
  Redirect / http://www.unifycore.com/
</VirtualHost>

