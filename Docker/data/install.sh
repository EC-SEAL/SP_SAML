#!/bin/bash

#yum -y install php-mbstring php httpd initscripts net-tools wget php-pdo php-xml php-ldap php-pecl-memcache
#yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
#yum -y install php73 httpd php73-php wget php73-php-mbstring php73-php-ldap php73-php-pdo php73-php-xml php73-php-pecl-redis4 php73-php-pecl-memcache

INSTANCE="ESMO"
BASEDIR="/var/www"
PUBDIR="/var/www/html"
BACKUPDIR="$BASEDIR"
BASEDIRNAME="${INSTANCE}Bridge"
HTTPDUSER="apache"
HTTPDGRP="apache"
SSPHPURL="https://simplesamlphp.org/download?latest"
#### SSPHP config variables (for a new deployment) ####
SSBASEPATH="$INSTANCE/"
SSADMINPWD=$(head -c 20 /dev/urandom | base64)
SSSALT=$(head -c 15 /dev/urandom | base64)
SSTIMEZONE="Europe/Madrid"
SSDEBUG=true
SSLOGLEVEL="SimpleSAML_Logger::DEBUG"
SSLOGHANDLER="file"
SSENABLESAMLSP=true
SSENABLESAMLIDP=true
SSCONTACT="UJI"
SSCONTACTMAIL="farago@uji.es"

###### MAIN ######

#We create a working dir
TMPDIR="$BASEDIR/$(date +%s)"
mkdir -p "$TMPDIR"
[ $? -ne 0 ] && echo "[!] Error creating temporal directory $TMPDIR" && exit 1

#Download SSPHP
wget "$SSPHPURL" -O $TMPDIR/ssphp.tgz
[ $? -ne 0 ] && echo "[!] Error downloading SSPHP from $SSPHPURL" && exit 1

pushd $TMPDIR

#Deploy SSPHP
tar xzf ssphp.tgz
[ -e "./$BASEDIRNAME/" ] && rm -rf "./$BASEDIRNAME/"
mv simplesamlphp-* "./$BASEDIRNAME"

#Set proper owner and permissions
chown -R $HTTPDUSER:$HTTPDGRP "$BASEDIRNAME"
chmod -R o-rwx "$BASEDIRNAME"
chmod -R g-w "$BASEDIRNAME"
chmod -R g+w "$BASEDIRNAME"/log

#Set basic configuration for a new deployment
CFGFILE="$BASEDIRNAME/config/config.php"

sed -i $CFGFILE -re "s|^(.*'baseurlpath'\s+=>\s+)[^,]+,|\1'$SSBASEPATH',|g"
#sed -i $CFGFILE -re "s|^(.*'debug'\s+=>\s+)[^,]+,|\1$SSDEBUG,|g"
sed -i $CFGFILE -re "/^.*'debug'/{n;s|^(.*'saml'\s+=>\s+)[^,]+,|\1$SSDEBUG,|g}"
sed -i $CFGFILE -re "s|^(.*'auth.adminpassword'\s+=>\s+)[^,]+,|\1'$SSADMINPWD',|g"
sed -i $CFGFILE -re "s|^(.*'secretsalt'\s+=>\s+)[^,]+,|\1'$SSSALT',|g"
sed -i $CFGFILE -re "s|^(.*'technicalcontact_name'\s+=>\s+)[^,]+,|\1'$SSCONTACT',|g"
sed -i $CFGFILE -re "s|^(.*'technicalcontact_email'\s+=>\s+)[^,]+,|\1'$SSCONTACTMAIL',|g"
sed -i $CFGFILE -re "s|^(.*'timezone'\s+=>\s+)[^,]+,|\1'$SSTIMEZONE',|g"
sed -i $CFGFILE -re "s|^(.*'logging.level'\s+=>\s+)[^,]+,|\1$SSLOGLEVEL,|g"
sed -i $CFGFILE -re "s|^(.*'logging.handler'\s+=>\s+)[^,]+,|\1'$SSLOGHANDLER',|g"
sed -i $CFGFILE -re "s|^(.*'enable.saml20-sp' \s+=>\s+)[^,]+,|\1$SSENABLESAMLSP,|g"
sed -i $CFGFILE -re "s|^(.*'enable.saml20-idp'\s+=>\s+)[^,]+,|\1$SSENABLESAMLIDP,|g"

#We move the instance to its destination
mv -v $BASEDIRNAME  $BASEDIR/$BASEDIRNAME

#Link the public directory to its location
if [ ! -e "$PUBDIR/$INSTANCE" ] ; then
    echo "[] Linking the public directory: $PUBDIR/$INSTANCE --> $BASEDIR/$BASEDIRNAME/www/"
    ln -s $BASEDIR/$BASEDIRNAME/www/ $PUBDIR/$INSTANCE
fi

popd

# TODO: que al final se haga el chown al user y group que toca

#Delete the tmp dir and anything remaining
rm -rf "$TMPDIR"

#apachectl -k start -f /etc/httpd/conf/httpd.conf


echo **************************++
echo ** $SSADMINPWD
echo **************************++

