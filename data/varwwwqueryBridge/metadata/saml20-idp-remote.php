<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */



$metadata['https://idp.entity.id/ujiidp/saml2/idp/metadata.php'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'https://idp.entity.id/ujiidp/saml2/idp/metadata.php',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.entity.id/ujiidp/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.entity.id/ujiidp/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'certData' => 'MIIDeDCCAmCgAwIBAgIJAIe5c+ZlFQ+tMA0GCSqGSIb3DQEBCwUAMFExCzAJBgNVBAYTAkVTMRIwEAYDVQQIDAlDYXN0ZWxsb24xEjAQBgNVBAcMCUNhc3RlbGxvbjEMMAoGA1UECgwDVUpJMQwwCgYDVQQDDANVSkkwHhcNMTkwMjI1MTM1NzA3WhcNMjkwMjI0MTM1NzA3WjBRMQswCQYDVQQGEwJFUzESMBAGA1UECAwJQ2FzdGVsbG9uMRIwEAYDVQQHDAlDYXN0ZWxsb24xDDAKBgNVBAoMA1VKSTEMMAoGA1UEAwwDVUpJMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw1HNqk+S3JjYrlAOY5InLt2bNV0+8L0UI9FwyNOS8brKvzUrwF+GrlqvWKwu2S9l2MClKyZYR2cC1WUQXys8hdJe9lgq62IuV0Ra6s/YlOJPM8Epa+nemkgqR7gm6HSpMfQfzkIblr+RMSrkmBTkWrYSUP69Px009ItpGeknlHYPTIJ0e7ktMJ3f2tCxjr1e2iSfw64Q79A2ZO5F6CxTpoHgUopC9uwedcfuUkY9uPHPsE8aMN6srhPRhfPfoDe4DET8TZm9/GI10SasgcVGis8qeeGebC2UU/J2EuuALVWRqvHbgr1GEqI6eIoW55Na45kfzcx0R0vPQsx0Y342twIDAQABo1MwUTAdBgNVHQ4EFgQUCuDk0dk6eS2yTvJCVScOG1mEggIwHwYDVR0jBBgwFoAUCuDk0dk6eS2yTvJCVScOG1mEggIwDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEAnGluBRR8884Bysz45tM5OUcuYKKclJYpEbnbA7E3SyaAMEoXF1Dv40owMnMLQKN3a48CEhXfJMs8P2xPm6Y7E7pgCzo//eibZ/EO0lxh0ihi8dS6oie/uENqwqEAAG/pWIppaqwbtCuMlceqtWsmHoeA2aEWxjN+yu9pCP9djWqTkU3Q3gtCNCrmVlK7fRIPDoNuJ9DpAKksgXwz58BmVrgqE3s5A6AWL6N02uzLNVD1ZuGzS9X3sIlH/1RLCxoBorC7Fd2UflPU39JMKxIDLGjz+diGeFMYM2r5DHmT8BekAyKwdVMN+IkU3w7THAOSdWtNN+V06tRX46y8gCs2WA==',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
);
