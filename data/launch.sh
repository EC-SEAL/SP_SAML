#!/bin/bash

INSTANCES="auth query"


#Generate secure random admin password for SSP interface
SSPADMPW="$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)"

#Copy input config to its proper location

# This caused the host dir to change owner. Now doing it on the local copies
#  and more fine-grained
#chown -R apache.apache /data/*

echo "*************************"
ls -lR /data
echo "*************************"




for INSTANCE in $INSTANCES; do

  sed -i -e "s/'auth.adminpassword' => '..*',/'auth.adminpassword' => '$SSPADMPW',/" "/var/www/${INSTANCE}Bridge/config/config.php"

#  cp -pr /data/esmo "/var/www/${INSTANCE}Bridge/modules/esmo"
#  cp -pr /data/clave /var/www/${INSTANCE}Bridge/modules/clave

  cp -pr /data/varwww${INSTANCE}Bridge/cert/*     /var/www/${INSTANCE}Bridge/cert/
  cp -pr /data/varwww${INSTANCE}Bridge/config/*   /var/www/${INSTANCE}Bridge/config/
  cp -pr /data/varwww${INSTANCE}Bridge/metadata/* /var/www/${INSTANCE}Bridge/metadata/
  chown -R root.apache /var/www/${INSTANCE}Bridge/cert/
  chown -R root.apache /var/www/${INSTANCE}Bridge/config/
  chown -R root.apache /var/www/${INSTANCE}Bridge/metadata/
  chmod 640 /var/www/${INSTANCE}Bridge/cert/*
  chmod 640 /var/www/${INSTANCE}Bridge/config/*
  chmod 640 /var/www/${INSTANCE}Bridge/metadata/*

done


cp -pr /data/httpdssl.crt /etc/pki/tls/certs/localhost.crt
cp -pr /data/httpdssl.key /etc/pki/tls/private/localhost.key
chown -R root.apache /etc/pki/tls/certs/localhost.crt
chmod 644 /etc/pki/tls/certs/localhost.crt
chown -R root.apache /etc/pki/tls/private/localhost.key
chmod 640  /etc/pki/tls/private/localhost.key



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
