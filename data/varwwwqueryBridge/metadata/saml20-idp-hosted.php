<?php
/**
 * SAML 2.0 IdP configuration for simpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted
 */

$metadata['__DYNAMIC:1__'] = array(
	/*
	 * The hostname of the server (VHOST) that will use this SAML entity.
	 *
	 * Can be '__DEFAULT__', to use this entry by default.
	 */
	'host' => '__DEFAULT__',

    /* X.509 key and certificate. Relative to the cert directory. */
    'privatekey'  => 'idp.key',
    'certificate' => 'idp.crt',

    
    /*                                                                                                                                                                                                             
     * Authentication source to use. Must be one that is configured in                                                                                                                                             
     * 'config/authsources.php'.                                                                                                                                                                                   
     */
    //'auth' => 'example-userpass', //This authenticates with the enabled user:pwd test authsource
    //'auth' => 'saml', //This bridges to the SP part
    //'auth' => 'clave',    //This bridges to the SP part (clave)
    'auth' => 'esmo',    //This bridges to the SP part (esmo microservice)
 
    'saml20.sign.response' => TRUE,
    'saml20.sign.assertion' => TRUE,
    'redirect.sign' => TRUE,
    'redirect.validate' => TRUE,
    
    //TODO set as TRUE when all SPs are adapted (or when just some explicitly require unencrypted assertions)
    'assertion.encryption' => FALSE,
    
    
	/*
	 * WARNING: SHA-1 is disallowed starting January the 1st, 2014.
	 *
	 * Uncomment the following option to start using SHA-256 for your signatures.
	 * Currently, simpleSAMLphp defaults to SHA-1, which has been deprecated since
	 * 2011, and will be disallowed by NIST as of 2014. Please refer to the following
	 * document for more information:
	 * 
	 * http://csrc.nist.gov/publications/nistpubs/800-131A/sp800-131A.pdf
	 *
	 * If you are uncertain about service providers supporting SHA-256 or other
	 * algorithms of the SHA-2 family, you can configure it individually in the
	 * SP-remote metadata set for those that support it. Once you are certain that
	 * all your configured SPs support SHA-2, you can safely remove the configuration
	 * options in the SP-remote metadata set and uncomment the following option.
	 *
	 * Please refer to the IdP hosted reference for more information.
	 */
	//'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

	/* Uncomment the following to use the uri NameFormat on attributes. */
	/*
	'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
	'authproc' => array(
		// Convert LDAP names to oids.
		100 => array('class' => 'core:AttributeMap', 'name2oid'),
	),
	*/

);
