# Dockerfile version to be run on the server, with all dependencies properly resolved
# Make sure the owner and permissions for the files on the volume are correct
#  before running build (all are readable and all folders aaccessible by the
#  docker user, and by the root user inside the image/container)
# docker build -t sealsaml .
# docker run -d -p 9080:80 -p 9443:443 -v /home/farago/seal/data:/data  --name SealSamlMSPre1 sealsaml
# docker stop SealSamlMSPre1; docker rm SealSamlMSPre1; docker rmi sealsaml

#FROM centos:latest
FROM centos:centos7
MAINTAINER UJI - farago@uji.es traverj@uji.es
RUN yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum -y install php73 httpd mod_ssl php73-php wget php73-php-mbstring php73-php-ldap php73-php-pdo php73-php-xml php73-php-pecl-redis4 php73-php-pecl-memcache crontabs unzip

RUN sed -i -e '/pam_loginuid.so/s/^/#/' /etc/pam.d/crond
RUN chmod 0644 /etc/crontab

#RUN $(setenforce 0; exit 0)

COPY ./data/install.sh install.sh
RUN  chmod u+x install.sh

#COPY ./data/launch.sh /data/launch.sh
COPY ./data/launch.sh launch.sh
RUN  chmod u+x launch.sh

COPY ./data/clave /data/clave
COPY ./data/esmo  /data/esmo
RUN  chown -R root:apache /data/clave
RUN  chown -R root:apache /data/esmo
RUN  find /data/clave -type f -exec chmod 640 {} \;
RUN  find /data/esmo  -type f -exec chmod 640 {} \;
RUN  find /data/clave -type d -exec chmod 750 {} \;
RUN  find /data/esmo  -type d -exec chmod 750 {} \;



RUN ./install.sh

VOLUME ./data /data
EXPOSE 80 443
#CMD ["sh", "-c" ,"/data/launch.sh"]
CMD ["sh", "-c" ,"/launch.sh"]
