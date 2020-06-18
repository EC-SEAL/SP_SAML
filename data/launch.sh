#!/bin/bash


#Generate secure random admin password for SSP interface
SSPADMPW="$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)" && sed -i -e "s/'auth.adminpassword' => '..*',/'auth.adminpassword' => '$SSPADMPW',/" /var/www/ESMOBridge/config/config.php

#Copy input config to its proper location
chown -R apache.apache /data/*

cp -pr /data/esmo /var/www/ESMOBridge/modules/esmo
cp -pr /data/clave /var/www/ESMOBridge/modules/clave
cp -pr /data/varwwwESMOBridge/cert/* /var/www/ESMOBridge/cert/
cp -pr /data/varwwwESMOBridge/config/* /var/www/ESMOBridge/config/
cp -pr /data/varwwwESMOBridge/metadata/* /var/www/ESMOBridge/metadata/
cp -pr /data/httpdssl.crt /etc/pki/tls/certs/localhost.crt
cp -pr /data/httpdssl.key /etc/pki/tls/private/localhost.key


#Launch Cron daemon, copy crontab and first run
/usr/sbin/crond
cp -pr /data/esmo.cron  /var/spool/cron/root
/bin/crontab /var/spool/cron/root

#Launch apache httpd server
/usr/sbin/apachectl -k start -f /etc/httpd/conf/httpd.conf && /usr/bin/tail -f /var/log/httpd/*

