FROM centos:latest
MAINTAINER UJI - farago@uji.es traverj@uji.es
RUN yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum -y install php73 httpd mod_ssl php73-php wget php73-php-mbstring php73-php-ldap php73-php-pdo php73-php-xml php73-php-pecl-redis4 php73-php-pecl-memcache crontabs

RUN sed -i -e '/pam_loginuid.so/s/^/#/' /etc/pam.d/crond
RUN chmod 0644 /etc/crontab

COPY ./data/install.sh install.sh
RUN ./install.sh
VOLUME ./data /data
EXPOSE 80 443
CMD ["sh", "-c" ,"/data/launch.sh"]
