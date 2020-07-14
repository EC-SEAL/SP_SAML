<?php

$esmoMicroservices = array (
  5 => 
  array (
    'msId' => 'SAMLIDPms001',
    'authorisedMicroservices' => array (
        0 => 'ACMms001',
    ),
    'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCi7jZVwQFxQ2SY4lxjr05IexolQJJobwYzrvE5pk7AcQpG46kuJBzD8ziiqFFCGSNZ07cLWys+b5JmJ6kU44lKLVeGbEpgaO0OTBDLMk2fi5U83T8dezgWgaPFiy/N3sHPpcW2Y3ZePo0UbM7MLzv14TR+jxTOyrmwWwGwDJsz+wIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'IDP',
        'apiCall' => 'authenticate',
        'apiConnectionType' => 'post',
        'apiEndpoint' => 'http://esmo.srv/ESMO/module.php/esmo/idp/authenticate.php/saml2',
      ),
    ),
  ),
  0 => 
  array (
    'msId' => 'SAMLms001',
    'authorisedMicroservices' => array (
        0 => 'ACMms001',
    ),
    'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCi7jZVwQFxQ2SY4lxjr05IexolQJJobwYzrvE5pk7AcQpG46kuJBzD8ziiqFFCGSNZ07cLWys+b5JmJ6kU44lKLVeGbEpgaO0OTBDLMk2fi5U83T8dezgWgaPFiy/N3sHPpcW2Y3ZePo0UbM7MLzv14TR+jxTOyrmwWwGwDJsz+wIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'AP',
        'apiCall' => 'query',
        'apiConnectionType' => 'post',
        'apiEndpoint' => 'http://esmo.srv/ESMO/module.php/esmo/ap/query.php/saml2',
      ),
      1 => 
      array (
        'apiClass' => 'SP',
        'apiCall' => 'handleResponse',
        'apiConnectionType' => 'post',
        'apiEndpoint' => 'http://esmo.srv/ESMO/module.php/esmo/sp/response.php/esmo',
      ),
      2 => 
      array (
        'apiClass' => 'IDP',
        'apiCall' => 'authenticate',
        'apiConnectionType' => 'post',
        'apiEndpoint' => 'http://esmo.srv/ESMO/module.php/esmo/idp/authenticate.php/eidas',
      ),
    ),
  ),
  1 => 
  array (
    'msId' => 'ACMms001',
    'authorisedMicroservices' => 
    array (
      0 => 'SAMLms001',
      1 => 'GW2GWms001',
    ),
    'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCi7jZVwQFxQ2SY4lxjr05IexolQJJobwYzrvE5pk7AcQpG46kuJBzD8ziiqFFCGSNZ07cLWys+b5JmJ6kU44lKLVeGbEpgaO0OTBDLMk2fi5U83T8dezgWgaPFiy/N3sHPpcW2Y3ZePo0UbM7MLzv14TR+jxTOyrmwWwGwDJsz+wIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'ACM',
        'apiCall' => 'acmRequest',
        'apiConnectionType' => 'post',
        //'apiEndpoint' => 'http://esmo.srv:8070/acm/request',
        'apiEndpoint' => 'https://esmo.srv:8073/acm/request',
      ),
      1 => 
      array (
        'apiClass' => 'ACM',
        'apiCall' => 'acmResponse',
        'apiConnectionType' => 'post',
        //'apiEndpoint' => 'http://esmo.srv:8070/acm/response',
        'apiEndpoint' => 'https://esmo.srv:8073/acm/response',
      ),
    ),
  ),
  2 => 
  array (
    'msId' => 'GW2GWms001',
    'authorisedMicroservices' => 
    array (
      0 => 'GW2GWms001',
    ),
    'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCTl2+rG9Iq4Cm90QoT/2gPQ4txzuqPqiaBscvYMJ1M2AIcyidgBR5pXLQrt/91vsJcpbHjUFiui3DH9JneZOFBwwD6TG5CL2ILyCMxOmeDv+LwLLrRu4bIwMNTvjsqIVNaf97BOvH2BDE6DQ4OOYE13X5y5FGYSgEEPVAtdPO3TwIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'GW',
        'apiCall' => 'query',
        'apiConnectionType' => 'post',
        //'apiEndpoint' => 'http://esmo.srv:8050/gw/query',
        'apiEndpoint' => 'https://esmo.srv:8053/gw/query',
      ),
      1 => 
      array (
        'apiClass' => 'GW',
        'apiCall' => 'responseAssertions',
        'apiConnectionType' => 'post',
        //'apiEndpoint' => 'http://esmo.srv:8050/gw/responseAssertions',
        'apiEndpoint' => 'https://esmo.srv:8053/gw/responseAssertions',
      ),
    ),
  ),
  3 => 
  array (
    'msId' => 'SMms001',
    'rsaPublicKeyBinary' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkvZf4Lm7dqp17tk/ICI+cCilI3yLfQraHy4pxFYDNn29l9eHnYRFnN9jBKKvOzSxf2zQkigNcHhIi96s7G4/xPL3rVaYepp/xfCKn5vkZeqg1PFOE0HqDKCnIbLxNdnHYDLICQrd1PRTdFHnwRpLouF6B3PCZpQL5XxX3WFzg2KZ2U1NIdVLJjWb3AY1SJ4kIYAOIwn6AZQPum4i5G4M9QQj3KGl164007TUx27rxzBVILpm+knxYjUiipqZ/5kiDdTxYBPR0qDVIhSl3hk9RhSI95s7unrll8rb3E8w1ORrfTQNg1UlpGgww3jZi3GLScLEK3ghwg5H5gL/2SSiEwIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'SM',
        'apiCall' => 'startSession',
        'apiConnectionType' => 'direct',
        //'apiEndpoint' => 'http://esmo.srv:8090/sm/startSession',
        'apiEndpoint' => 'http://SessionManager:8090/sm/startSession',
      ),
      1 => 
      array (
        'apiClass' => 'SM',
        'apiCall' => 'endSession',
        'apiConnectionType' => 'direct',
        //'apiEndpoint' => 'http://esmo.srv:8090/sm/endSession',
        'apiEndpoint' => 'http://SessionManager:8090/sm/endSession',
      ),
      2 => 
      array (
        'apiClass' => 'SM',
        'apiCall' => 'updateSessionData',
        'apiConnectionType' => 'direct',
        //'apiEndpoint' => 'http://esmo.srv:8090/sm/updateSessionData',
        'apiEndpoint' => 'http://SessionManager:8090/sm/updateSessionData',
     ),
      3 => 
      array (
        'apiClass' => 'SM',
        'apiCall' => 'getSessionData',
        'apiConnectionType' => 'direct',
        //'apiEndpoint' => 'http://esmo.srv:8090/sm/getSessionData',
        'apiEndpoint' => 'http://SessionManager:8090/sm/getSessionData',
      ),
      4 => 
      array (
        'apiClass' => 'SM',
        'apiCall' => 'generateToken',
        'apiConnectionType' => 'direct',
        //'apiEndpoint' => 'http://esmo.srv:8090/sm/generateToken',
        'apiEndpoint' => 'http://SessionManager:8090/sm/generateToken',
      ),
      5 => 
      array (
          'apiClass' => 'SM',
          'apiCall' => 'validateToken',
          'apiConnectionType' => 'direct',
          //'apiEndpoint' => 'http://esmo.srv:8090/sm/validateToken',
          'apiEndpoint' => 'http://SessionManager:8090/sm/validateToken',
      ),
    ),
  ),
  4 => 
  array (
    'msId' => 'CMms001',
    'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCTl2+rG9Iq4Cm90QoT/2gPQ4txzuqPqiaBscvYMJ1M2AIcyidgBR5pXLQrt/91vsJcpbHjUFiui3DH9JneZOFBwwD6TG5CL2ILyCMxOmeDv+LwLLrRu4bIwMNTvjsqIVNaf97BOvH2BDE6DQ4OOYE13X5y5FGYSgEEPVAtdPO3TwIDAQAB',
    'publishedAPI' => 
    array (
      0 => 
      array (
        'apiClass' => 'CM',
        'apiCall' => 'microservices',
        'apiConnectionType' => 'get',
        'apiEndpoint' => 'http://esmo.srv:8080/cm/metadata/microservices',
      ),
      1 => 
      array (
        'apiClass' => 'CM',
        'apiCall' => 'externalEntities',
        'apiConnectionType' => 'get',
        'apiEndpoint' => 'http://esmo.srv:8080/cm/metadata/externalEntities',
      ),
      2 => 
      array (
        'apiClass' => 'CM',
        'apiCall' => 'attributes',
        'apiConnectionType' => 'get',
        'apiEndpoint' => 'http://esmo.srv:8080/cm/metadata/attributes',
      ),
      3 => 
      array (
        'apiClass' => 'CM',
        'apiCall' => 'internal',
        'apiConnectionType' => 'get',
        'apiEndpoint' => 'http://esmo.srv:8080/cm/metadata/internal',
      ),
    ),
  ),
);