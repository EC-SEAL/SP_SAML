<?php

$config = array(

    //Sample Auth Source config for ESMO IdP/AP microservice (which
    //implements an SP, in fact)
    'esmo' => array(
        'esmo:SP',
        'msId' => 'SAMLms_0001',
        'rsaPublicKeyBinary' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsBAY+gi4XkMCE9l5/CRhwi465e8qmXOMrSOHDdSWV01BDvicu+uwIkMLdwFA8/ca/0zols74r0qP+fZeO1LjVa9mk1Y/oxpkHVxZRL8FK9wJ0BoEf/rg5p3L5zfML+BLyXZNbw7FOIbae+V9odVdQDmX0g3XfUmwHE53scSgZ7vR6O3WblnAa055RIksrfOMM4qjGcsI0AJF+FQsONl+8BcLYfYNoH9krutqxK3bdS/ecfKiuyO3Qdr1m+86hRA4UVK8X2Rg0lb7bfAq78O7gSJ/iQRxor5FilRe1Qb40e5Cljz/2cdNMgtxiUNRLC7cYZN60GLcClICtSkoABJIrwIDAQAB',
        'rsaPrivateKeyBinary' => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCwEBj6CLheQwIT2Xn8JGHCLjrl7yqZc4ytI4cN1JZXTUEO+Jy767AiQwt3AUDz9xr/TOiWzvivSo/59l47UuNVr2aTVj+jGmQdXFlEvwUr3AnQGgR/+uDmncvnN8wv4EvJdk1vDsU4htp75X2h1V1AOZfSDdd9SbAcTnexxKBnu9Ho7dZuWcBrTnlEiSyt84wziqMZywjQAkX4VCw42X7wFwth9g2gf2Su62rErdt1L95x8qK7I7dB2vWb7zqFEDhRUrxfZGDSVvtt8Crvw7uBIn+JBHGivkWKVF7VBvjR7kKWPP/Zx00yC3GJQ1EsLtxhk3rQYtwKUgK1KSgAEkivAgMBAAECggEAKqfbxUgzkvLdH/4CpeoSnT8iGe81/POX06LV563ntsMKzhvBrW3OwJ5Jus1c9T6bFduGRnNioOWJuF/OOMU+OtZCHdQ5msScGNj078jv8c5fukFzcaZQss7sRdqo57iJ5Ad7fzqu4aEacgYJyBmvfA34EHY2DS67MkB4k2M2Eatysl+cDFCwE6vxZ4Bacd1aQkvdEXA5eIrdI+6TxgFoNjwvW8jZ7ZCaEl5sT5rDqphik7nIzaYU4/SSbvH6J0iF5To+I0399Vdrpt8kWVNH85QcyUwcRLoJ3SMANIuuSoo4IPSEyfibzjCjbLMgEs6AMjFGgsJwNAlFin68kcVnkQKBgQDU89SFMWrdBXCcZEjvTNreSi0exIcvsxrZFB5Wj50/W7N3hrN/4HIavnWeTxW0GvzoX+/th4YEiu0batwDfWsJzrIwsg0TkY2Fd1B0UySt5MJDwkXUwK7RDOtqAlmy1xO9juS9SDNU/DgHvfoUV3IfgXaGyK/KbgdkNTPdWZpdmwKBgQDTp0CeRk4KHpIZkllLsWRTf/OZdyJ1fjd2+LklAj9GpbLYL5ye/rq2hxratipeRwMOo9ISkAS3CPBkuk6P2NA1/s3Duk1AwJ6fTEGRZHy5FJ6n1397frz15+d3rxyKVmx0JSlpMPqk38Ei1OLw9SKWGs8h8phrDYwmSn1y4or8fQKBgQCJzggW8TXANYb8DYGNKeTwuHueT3tUCMk09On1BhayK3tlu8to0yvD1sByY6cd1+EV1w+CXJMDWYu6lFov//dGb3WsQWxo32X/moh73ln8Fe8Ivi1GUjJOodet0DuPmdLydgfb3V8qfdUcXXn5s+TsMnErI69uhelOlYcslJFqRQKBgQDMiRPMIHroEpTzu1cp69rdSog5pUSasIefJEufdSV6+0Py4UgE6nu7SqLr+yDEjPFqY2vuXlkAHNZbMSQcpNTJaVylqlNfoQVpQgMXIznYjhGod3uN93NaXGp2YbY+Bbi3IPZ83kVJsaXuKDbLzslGr8+9qbgbtDLdCh1jOYRA7QKBgEsF2c8ggSx49NOU16IGewgeG2h6pXiwqm3AC4b1JhEuEs9qelER6vr2SBfTN8UMsnY4349oY+HIDSDpVIz4QG3dziSeFRnAMKOHNLIVivQhUrazuBXEcZHEZ+7fQlxjYc02wUt2J7k4E7k2lB2TjOw2g7Q79DeG835EspaWYl5m',
        //[Mandatory] Metadata set name where the msRegistry are be found (to be written by the CMHandler)
        'msRegistry' =>  'esmo-microservices',

        'apiClass' => 'RM',
        'apiCall' => 'rmRequest',
        //'noWriteParams' => [''],
        'spRequestEP' => 'query',
    ),

    //Base configuration of an eIDAS Auth Source
    'clave' => array(
        'clave:SP',
        
        //[Mandatory] The url of the country selector (either absolute URL, or
        //relative URL starting from module.php)
        'discoURL' =>  'clave/sp/countryselector.php',

        //[Mandatory] Which local eIDAS hosted SP metadata entry will
        //we be used to connect to the eIDAS remote IdP (from
        //clave-sp-hosted.php)
        'hostedSP' => 'preprod',
        
    ),

   	// An authentication source which can authenticate against both SAML 2.0
    'saml' => array(
        'saml:SP',
        
        // The entity ID of this SP.
        // Can be NULL/unset, in which case an entity ID is generated based on the metadata URL.
        'entityID' => NULL,
        
        // The entity ID of the IdP this should SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => "https://idp.entity.id/idp/saml2/idp/metadata.php",
        
        // The URL to the discovery service.
        // Can be NULL/unset, in which case a builtin discovery service will be used.
        'discoURL' => NULL,
        
        'host' => 'my.host.name',
        
        'certificate' => 'sp.crt',
        'privatekey' => 'sp.key',
        
        'redirect.sign' => TRUE,
        'redirect.validate' => TRUE,
        //'assertion.encryption' => FALSE,
    ),
);
