#!/bin/bash

INSTANCES="auth query"


#Generate secure random admin password for SSP interface
SSPADMPW="$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)"

#Copy input config to its proper location
chown -R apache.apache /data/*


for INSTANCE in $INSTANCES; do
  sed -i -e "s/'auth.adminpassword' => '..*',/'auth.adminpassword' => '$SSPADMPW',/" "/var/www/${INSTANCE}Bridge/config/config.php"

#  cp -pr /data/esmo "/var/www/${INSTANCE}Bridge/modules/esmo"
#  cp -pr /data/clave /var/www/${INSTANCE}Bridge/modules/clave

  cp -pr "/data/varwww${INSTANCE}Bridge/cert/*" "/var/www/${INSTANCE}Bridge/cert/"
  cp -pr "/data/varwww${INSTANCE}Bridge/config/*" "/var/www/${INSTANCE}Bridge/config/"
  cp -pr "/data/varwww${INSTANCE}Bridge/metadata/*" "/var/www/${INSTANCE}Bridge/metadata/"
done

cp -pr /data/httpdssl.crt /etc/pki/tls/certs/localhost.crt
cp -pr /data/httpdssl.key /etc/pki/tls/private/localhost.key


#Launch Cron daemon, copy crontab and first run
/usr/sbin/crond
cp -pr /data/esmo.cron  /var/spool/cron/root
/bin/crontab /var/spool/cron/root

#Add CAs to the bundle
CA_BUNDLE="/etc/ssl/certs/ca-bundle.crt"
IFS_BAK="$IFS"
IFS="$(printf '\n\t')"   # Remove space.
for file in /data/ca_dir/* ; do
    if [ -e "$file" ] ; then   # Check whether file exists.
      echo "Adding CA: $file"
      echo -e "\n# $file" >> $CA_BUNDLE
      cat "$file" >> $CA_BUNDLE
      echo "" >> $CA_BUNDLE
    fi
done
IFS="$IFS_BAK"

#Launch apache httpd server
/usr/sbin/apachectl -k start -f /etc/httpd/conf/httpd.conf && /usr/bin/tail -f /var/log/httpd/*

