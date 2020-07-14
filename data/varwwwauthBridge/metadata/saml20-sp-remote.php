<?php
/**
 * SAML 2.0 remote SP metadata for simpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */


//WebSSO Test SP Mine
$metadata['https://sp.entity.id/simplesaml/module.php/saml/sp/metadata.php/default-sp'] = array (
    'entityid' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/metadata.php/default-sp',
    'attributes' => array('eIdentifier', 'givenName', 'surname', 'eMail', 'inheritedFamilyName', 'adoptedFamilyName', 'afirmaResponse', 'isdnie', 'registerType', 'citizenQAAlevel'),
    'contacts' => array (
    ),
    
    'SingleLogoutService' => array (
        0 => array (
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp',
        ),
    ),
    'AssertionConsumerService' => array (
        0 => array (
            'index' => 0,
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
        ),
        1 => array (
            'index' => 1,
            'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post',
            'Location' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/saml1-acs.php/default-sp',
        ),
        2 => array (
            'index' => 2,
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
            'Location' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp',
        ),
        3 => 
        array (
            'index' => 3,
            'Binding' => 'urn:oasis:names:tc:SAML:1.0:profiles:artifact-01',
            'Location' => 'https://sp.entity.id/simplesaml/module.php/saml/sp/saml1-acs.php/default-sp/artifact',
        ),
    ),

    'metadata-set' => 'shib13-sp-remote',

    'keys' => array (
        0 => array (
            'encryption' => false,
            'signing' => true,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIICDjCCAXegAwIBAgIJAJS1PRRI6sJdMA0GCSqGSIb3DQEBBQUAMCAxCzAJBgNVBAYTAmVzMREwDwYDVQQDDAhzdG9yay5ldTAeFw0xMzA5MDMxMTMxMzZaFw0xNjA2MjMxMTMxMzZaMCAxCzAJBgNVBAYTAmVzMREwDwYDVQQDDAhzdG9yay5ldTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAzm9lJLmUVcXhGxA7ZgjHMVeOqYXnbzndJhuQBJQmcmRtiu6tabYS5wGSblHKi6rJNr+hbyR2xi6hFOXLwtOQ2LtTz80y/lNnmeMvKrFNiPbemVNHhteGYgWPSPMcJ++Fb/vbVK1JSJnJIXyUtNrFE5riLh94wXeYS5uVb3YB1kkCAwEAAaNQME4wHQYDVR0OBBYEFCQDzs88jLfSWIHY5+L8HALmqEFjMB8GA1UdIwQYMBaAFCQDzs88jLfSWIHY5+L8HALmqEFjMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEASI++zaBJVc/Qsz/2GhwPlR/Hrhxok7cbaFEmt5hMabiSvHKNYKHUO1JndJ+fV//Y9V/UXb6NasRc210iKilxKNFibnHPe2b1GRuUufFM4VpqybsHphhebDvtsPtBbsdiW7zIcr6kaO0ofZoMHzuoxixpBLt1+S+vXmu34nJaz4I=',
        ),
        1 => array (
            'encryption' => true,
            'signing' => false,
            'type' => 'X509Certificate',
            'X509Certificate' => 'MIICDjCCAXegAwIBAgIJAJS1PRRI6sJdMA0GCSqGSIb3DQEBBQUAMCAxCzAJBgNVBAYTAmVzMREwDwYDVQQDDAhzdG9yay5ldTAeFw0xMzA5MDMxMTMxMzZaFw0xNjA2MjMxMTMxMzZaMCAxCzAJBgNVBAYTAmVzMREwDwYDVQQDDAhzdG9yay5ldTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAzm9lJLmUVcXhGxA7ZgjHMVeOqYXnbzndJhuQBJQmcmRtiu6tabYS5wGSblHKi6rJNr+hbyR2xi6hFOXLwtOQ2LtTz80y/lNnmeMvKrFNiPbemVNHhteGYgWPSPMcJ++Fb/vbVK1JSJnJIXyUtNrFE5riLh94wXeYS5uVb3YB1kkCAwEAAaNQME4wHQYDVR0OBBYEFCQDzs88jLfSWIHY5+L8HALmqEFjMB8GA1UdIwQYMBaAFCQDzs88jLfSWIHY5+L8HALmqEFjMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEASI++zaBJVc/Qsz/2GhwPlR/Hrhxok7cbaFEmt5hMabiSvHKNYKHUO1JndJ+fV//Y9V/UXb6NasRc210iKilxKNFibnHPe2b1GRuUufFM4VpqybsHphhebDvtsPtBbsdiW7zIcr6kaO0ofZoMHzuoxixpBLt1+S+vXmu34nJaz4I=',
        ),
    ),
);
