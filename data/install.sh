#!/bin/bash

#yum -y install php-mbstring php httpd initscripts net-tools wget php-pdo php-xml php-ldap php-pecl-memcache
#yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
#yum -y install php73 httpd php73-php wget php73-php-mbstring php73-php-ldap php73-php-pdo php73-php-xml php73-php-pecl-redis4 php73-php-pecl-memcache

#INSTANCE="ESMO"
# Now we support deploying multiple instances of SSP in one run
INSTANCES="auth query"


BASEDIR="/var/www"
PUBDIR="/var/www/html"
#BACKUPDIR="$BASEDIR"
HTTPDUSER="apache"
HTTPDGRP="apache"
SSPHPURL="https://simplesamlphp.org/download?latest"
#### SSPHP config variables (for a new deployment) ####
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

pushd $TMPDIR || echo "pushd $TMPDIR failed"; exit 1

#Deploy SSPHP
tar xzf ssphp.tgz


echo "current path:"
pwd
echo "********/data/*************"
ls -l /data
echo "*********************"


# Download clave module if not present  # TODO: works?
if [ ! -d /data/clave/lib/ ] ; then
  wget https://github.com/rediris-es/simplesamlphp-clave2/archive/master.zip --no-check-certificate -O clave.zip
  unzip clave.zip
  rm -rf /data/clave
  mv simplesamlphp-clave2-master /data/clave
  chown -R $HTTPDUSER:$HTTPDGRP /data/clave
  chmod -R o-rwx /data/clave
  chmod -R g-w /data/clave
fi

# Download esmo module if not present  # TODO: works? update url
if [ ! -d /data/esmo/lib/ ] ; then
  wget https://github.com/faragom/ESMO_SAML/archive/master.zip --no-check-certificate -O esmo.zip
  unzip esmo.zip
  rm -rf /data/esmo
  mv ESMO_SAML-master/esmo /data/
  chown -R $HTTPDUSER:$HTTPDGRP /data/esmo
  chmod -R o-rwx /data/esmo
  chmod -R g-w /data/esmo
fi



for INSTANCE in $INSTANCES; do

  echo "--> Deploying instance $INSTANCE"

  BASEDIRNAME="${INSTANCE}Bridge"
  SSBASEPATH="$INSTANCE/"


  [ -e "./$BASEDIRNAME/" ] && rm -rf "./$BASEDIRNAME/"
  cp -ar simplesamlphp-* "./$BASEDIRNAME"

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

  # Set unique values to cookie names to avoid collisions between instances
  sed -i $CFGFILE -re "s|^(.*'session.cookie.name'\s+=>\s+)[^,]+,|\1SimpSAMLSessIDSEAL$INSTANCE,|g"
  sed -i $CFGFILE -re "s|^(.*'session.phpsession.cookiename'\s+=>\s+)[^,]+,|\1SimpSAMLSEAL$INSTANCE,|g"
  sed -i $CFGFILE -re "s|^(.*'session.authtoken.cookiename'\s+=>\s+)[^,]+,|\1SimpSAMLAuthTokenSEAL$INSTANCE,|g"

  #Copy modules to instance module dir (now we install the code, not on launch)  # TODO: works?
  cp -pr /data/esmo "$BASEDIRNAME/modules/esmo"
  cp -pr /data/clave "$BASEDIRNAME/modules/clave"

  #We move the instance to its destination
  mv -v $BASEDIRNAME  $BASEDIR/$BASEDIRNAME

  #Link the public directory to its location
  if [ ! -e "$PUBDIR/$INSTANCE" ] ; then
      echo "[] Linking the public directory: $PUBDIR/$INSTANCE --> $BASEDIR/$BASEDIRNAME/www/"
      ln -s $BASEDIR/$BASEDIRNAME/www/ $PUBDIR/$INSTANCE
  fi

done

popd || echo "popd failed"; exit 1

# TODO: que al final se haga el chown al user y group que toca # No está ya hecho?

#Delete the tmp dir and anything remaining
rm -rf "$TMPDIR"

#apachectl -k start -f /etc/httpd/conf/httpd.conf


echo **************************++
echo ** $SSADMINPWD
echo **************************++
