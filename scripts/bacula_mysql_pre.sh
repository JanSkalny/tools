#! /bin/sh
#
# incremental mysqldump backup script for bacula
#
# johnny ^_^ <johnny@netvor.sk>
# 2010-08-19
# kofolaware (http://netvor.sk/~johnny/kofola-ware)
#

LEVEL="$1"
MYSQLARGS="-uuser -ppass"
DESTDIR=/var/backups/mysql/

mysql_enum_dbs() {
        mysql $MYSQLARGS -B -e "SHOW DATABASES;" | grep -v '\(information_schema\|Database\)' | xargs -L 1 -I% echo -n "% "
}


# zrotujme logy
mysqladmin $MYSQLARGS flush-logs

# ak chceme full alebo diff backup, spravme plnu zalohu:
# odstranime stare logy a zalohujeme vsetko aktualne
if [ "$LEVEL" = "Full" ] || [ "$LEVEL" = "Differential" ]
then
        mysql $MYSQLARGS -e "PURGE BINARY LOGS BEFORE NOW();"
	for db in $(mysql_enum_dbs);  do
		fname="$DESTDIR/$db.sql.lzma"
		mysqldump $MYSQLARGS --master-data=2 $db | lzma -1 > $fname
	done
fi


