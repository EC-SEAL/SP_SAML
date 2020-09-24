# SEAL SAML SP Module

##Run the container

To run the container `faragom/sealsaml:latest`, you need to set up a folder with the contents of the `data/` folder
 in the repository, but adapted to your own needs.

```[shell script]
docker run -d -p HOST_PORT_HTTP:80 -p HOST_PORT_HTTPS:443 \
  -v "LOCAL_PATH_data:/data"  --name seal-sp-saml faragom/sealsaml:latest
```

* `-p HOST_PORT_HTTP:80`  Substitute **HOST_PORT_HTTP** for the port you want to expose the microservice plain HTTP on
* `-p HOST_PORT_HTTPS:443`  Substitute **HOST_PORT_HTTPS** for the port you want to expose the microservice HTTP SSL on
* `-v "LOCAL_PATH_data:/data"`  Substitute **LOCAL_PATH_data** for the path to your volume folder

* `--name seal-sp-saml`  The name of the container. Use this or change at will.


## Expected volume contents
Your volume needs to contain a number of configuration files for the microservice to be operational.
You can find an example of those files in the `data/` directory in the repository


* `httpdssl.crt` (**Mandatory**. *Name is fixed*) In PEM. SSL Certificate of the web server. Might include 
as well the certificates of any intermediate CAs.
* `httpdssl.key` (**Mandatory**. *Name is fixed*) In PEM. RSA Private key of the SSL Certificate
* `varwwwqueryBridge` and `varwwwauthBridge` These two directories keep the configuration of 
* |-- `cert`: certificates referenced on the metadat and config files
* |-- `config`: main configuration files. Need to be edited to change passwords at least.
* |-- `metadata`: metadata of the internal entities, but also the external ones. Base or static versions can be 
provided, but the cron job will update them from the Config Manager.
* `ca_dir` In this directory you can optionally include certificates of additional CAs to be 
trusted (PEM files)

Generally, these files must not be changed:
* `esmo.cron` (**Mandatory**. *Name is fixed*) cron file with periodic tasks. Usually, leave as is
* `launch.sh` internal file. **Do NOT change** 
* `install.sh` internal file. **Do NOT change**
* `esmo` (*optional*) this directory allows to load a newer version of the ESMO/SEAL module on the container. 
Leave empty if you don't intend to change it.
* `clave` (*optional*) this directory allows to load a newer version of the eIDAS module on the container. 
Leave empty if you don't intend to change it.




