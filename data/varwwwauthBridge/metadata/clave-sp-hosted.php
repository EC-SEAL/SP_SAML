<?php
/**
 * SAML 2.0 eIDAS SP configuration for simpleSAMLphp.
 *
 * All possible options included in this example in comments
 */



$claveMeta['preprod'] = array(
    
    // [Mandatory] The unique ID of this SP
    'entityid' => 'https://sp.hosted/module.php/saml/sp/saml2-acs.php/q2891006e_ea0002678',
    
    
    // [Mandatory] In STORK, this identifier must match the
    // friendlyName of the request signing certificate authorised on
    // the IdP trust store.
    'providerName' => 'q2891006e_ea0009994',
    
    
    // [Mandatory] Identifier of the remote IdP this SP will query,
    // from the list on clave-idp-remote.php
    'idpEntityID' => 'redirisClave2',  //'serviciosEstablesClave2',
    
    
    // [Optional] The issuer field of the SP request to be sent. If
    // not set, the issuer field on the remoteSP metadata will be
    // used, and if neither set, the issuer field on tyhe original
    // request will be passed
    'issuer' => 'SIR2',
    
    
    // [Mandatory] List of the post parameters that will be
    // retransmitted along with the response (if not set, none will
    // be)
    'sp.post.allowed' => array('isLegalPerson', 'oid'),
    
    
    // [Mandatory] Dialect to be used by the SP on the request
    //Possible values: 'stork','eidas'
    'dialect' => 'eidas',
    
    
    //[Mandatory] Details relative to the specific implementation of
    //the dialect
    //Possible values: 'stork','clave-1.0','eidas','clave-2.0'
    'subdialect' => 'clave-2.0',
    
    
    // [Optional] STORK minimum accepted level of quality on the
    // authentication (1: username+pwd <-> 4:smartcard). Automatically
    // converted to eIDAS LoA values (<=2,3,>=4)
    'QAA' => 1,
    
    
    // [Mandatory] SP AuthnReq Signing Certificate and key (it must be
    // authorised at the Clave IdP)
    'certificate' => 'sp.crt',
    'privatekey'  => 'sp.key',
    
    
    //Expect encrypted assertions (and decrypt them with the
    //privatekey)
    'assertions.encrypted' => true,
    
    
    //Expect encrypted assertions only (plain ones will be discarded)
    'assertions.encrypted.only' => false,
    
    
    // [Optional] STORK parameters. If not set, request values will be
    // retransmitted (or defaulted if not present). Will be overriden
    // by the remote SP metadata values
    'spCountry'     => 'ES',
    'spSector'      => 'EDU',
    'spInstitution' => 'AAA',
    'spApplication' => 'BBB',
    'spID'          => 'ES-EDU-AAA-BBB',
    
    'citizenCountryCode' => 'ES',
    
    'eIDSectorShare'      => true,
    'eIDCrossSectorShare' => true,
    'eIDCrossBorderShare' => true,
    
    
    
    // [Optional] eIDAS parameters. If not set, request values will be
    // used if any, else, default values (LoA default value will be
    // the QAA).
    'SPType'        => 'public',
    'NameIDFormat'  => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    //'LoA'  =>  'http://eidas.europa.eu/LoA/substantial',
    
    
    
    
    //[Optional] If enabled, if the remote SP does not provide a
    //'country' code as a POST param, we will prompt with a country
    //selector
    'showCountrySelector' => false,
    
    
    //[Optional] The list of countries to be shown on the
    //country selector
    'countries' => array('ES' => 'España'), 
    
    
    
    // ---Clave 1.0 IdP selector configuration---
    
    //Possible values: 'Stork' 'aFirma' 'SS' 'AEAT'
    //(Values set on each remote SP metadata override these)

    
    //[Optional] List of IdPs that will be shown on the IdP selector
    //'idpList' => array('Stork', 'aFirma', 'AEAT'),
    
    //[Optional] List of IdPs that won't be shown on the IdP selector
    //'idpExcludedList' => array('SS'),
    
    //[Optional] Force a specific IdP [bypass selector]
    //'force' => 'aFirma',
    
);

