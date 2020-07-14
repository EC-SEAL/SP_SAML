<?php
/**
 * SAML 2.0 eIDAS remote SP metadata for simpleSAMLphp.
 *
 * Compatible with the saml20-sp-remote format. In fact,
 * saml20-sp-remote can be used as the source of remote SP metadata
 * for eIDAS through a config parameter in clave-idp-hosted
 */



/************************************************
*
*
  -=== EIDAS SPECIFIC METADATA ===-


  // [Optional] List of attributes to be requested for this remote SP
  // (and that will be returned), if NULL, minimum eIDAS data set will
  // be requested and all IdP returned attributes will be
  // delivered. Empty array won't allow anything back.
  
  'attributes' => array('PersonIdentifier', 'FirstName', 'FamilyName','DateOfBirth'),



  // [Optional] The issuer of the SP request our hosted SP will
  // perform. This value overrides the clave-sp-hosted value. If
  // neither are set, the requester's entityID will be used as issuer
  // of this one.
  
  'issuer' => 'test-issuer',
  
  
  
  // [Optional] Dialect of SAML 2.0 to be expected by the IdP on the
  // request, and used on the response. This will override the value
  // in hosted IdP metadata.
  // Possible values: 'stork','eidas'
  'dialect' => 'eidas',
  
  // [Optional] Details relative to the specific implementation of
  //the dialect
  // Possible values: 'stork','clave-1.0','eidas','clave-2.0'
  'subdialect' => 'eidas',
    
  
  
  // [Optional] Set, for this SP, if the IdP must encrypt the outbound
  // assertions using the SP's certificate, and the specific key
  // algorithm to use (default AES-256)
  // NOTICE that 'keyAlgorith' is only valid for eIDAS SPs, for websso
  // SPs, see
  // https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote#section_2_1
  'assertion.encryption' => true,
  'assertion.encryption.keyAlgorith' => 'http://www.w3.org/2001/04/xmlenc#aes256-cbc',
  
  
  
  
  -=== STORK SPECIFIC METADATA ===-

  // [Optional] True if the assertions must be parsed to add the STORK
  // extensions or false to keep the assertions as received (not
  // altering any possible existing signature)
  'assertion.storkize' => true,
  
  
  
  
  -=== CLAVE-1.0 SPECIFIC METADATA ===-
  
  //Possible values: 'Stork' 'aFirma' 'SS' 'AEAT'
  //(Values set here override values set at hosted SP metadata)
  
  
  //[Optional] List of IdPs that will be shown on the IdP selector
  'idpList' => array('Stork', 'aFirma', 'AEAT'),
  
  
  //[Optional] List of IdPs that won't be shown on the IdP selector
  'idpExcludedList' => array('SS'),
  
  
  //[Optional] Force a specific IdP [bypass selector]
  'force' => 'aFirma',
  
  
  
  //[Optional] Default:false. Whether the user is authorised to
  //authenticate using a legal person certificate instead of a cotizen
  //certificate (not valid for all auth sources)
  'allowLegalPerson' => true,
  
  
  // [Optional] STORK parameters. If not set, request values will be
  // retransmitted (or defaulted if not present). Will override
  // the hosted SP metadata values
  'spCountry'     => 'ES',
  'spSector'      => 'EDU',
  'spInstitution' => 'RedIris',
  'spApplication' => 'SIR2',
  'spID'          => 'ES-EDU-RedIris-SIR2',
    
  'citizenCountryCode' => 'ES',
    
  'eIDSectorShare'      => true,
  'eIDCrossSectorShare' => true,
  'eIDCrossBorderShare' => true,
  
*
*
************************************************/

//Test SP
$claveMeta['https://remote.sp/metadata.php'] = array (
    'entityid' => 'https://remote.sp/metadata.php',

    'keys' =>
    array (
        0 =>
        array (
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIICGzCCAYQCCQDoPIlUtpzgHDANBgkqhkiG9w0JAQsFADBSMQswCQYDVQQGEwJFUzERMA8GA1UECAwIQ2FzdGVsbG8xETAPBgNVBAcMCENhc3RlbGxvMQwwCgYDVQQKDANVSkkxDzANBgNVBAMMBnVqaS5lczAeFw0xNjA0MjEwODU4MDBaFw0xOTAxMTYwODU4MDBaMFIxCzAJBgNVBAYTAkVTMREwDwYDVQQIDAhDYXN0ZWxsbzERMA8GA1UEBwwIQ2FzdGVsbG8xDDAKBgNVBAoMA1VKSTEPMA0GA1UEAwwGdWppLmVzMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDQKgYvJee9RuR/1rCHitqvYDXCapgUGoT4Q8OQ7eM/wxJFxPfyZVXa4ExML0FvK9ZZ6FYGgJfYEUSpkBNUmFkd3kdJX073hgtYGa5qwOh55kWdm4CtcgCEquVX4rtSchgHhhNjdUABJIv6ZX7whQfIxF1b7cO896SuqDLigcE60wIDAQABMA0GCSqGSIb3DQEBCwUAA4GBACHgYUSUxXbhpbfbw0i9648j7rlDVpEtIC8LNiwzgumZn1Udq44R6PReYB8UAXFScN3abxCM8E+Rdq9o6lJuioQlmTm8DrBgYx+lgVvfC6FseG9bCr9pvexwJGv1EvVwrE5qE8D8lR7MYdvSsiPJiRekgGNR3rK88NvjF6ogqIzi',
        ),              
    ),
);
