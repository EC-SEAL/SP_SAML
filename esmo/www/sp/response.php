<?php

/**
 * The ESMO microservice SP response endpoint implementation
 *
 */

SimpleSAML_Logger::info('ESMO - SP.response');


// Get the ID of the AuthSource from the queried URL [Remember to
// register the microservice url with the authsource ID on it]  // TODO: remember!
if (!array_key_exists('PATH_INFO', $_SERVER)) {
    throw new SimpleSAML_Error_BadRequest('Missing authentication source ID in assertion consumer service URL');
}
$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = SimpleSAML_Auth_Source::getById($sourceId, 'sspmod_esmo_Auth_Source_SP');


//Get the AuthSource config
$metadata = $source->getMetadata();
SimpleSAML_Logger::debug('Metadata on response (acs) service:'.print_r($metadata,true));



//Get remote IdP metadata
$remoteIdPMeta = $source->getIdPMetadata("");




//Receive ESMO microservice security token
if(!isset($_REQUEST['msToken']))
   	throw new SimpleSAML_Error_BadRequest('No msToken POST param received.');

$msToken = $_REQUEST['msToken'];
SimpleSAML_Logger::debug("Received msToken: ".$msToken);



$sessionVars = array(
    'samlMSstate'        => true,
    'spRequest'          => true,
    'spMetadata'         => true,
    'responseAssertions' => true);


//Pass the received token to the SM for validation and retrieve the
//needed session variables / call parameters
//(will only go on if validation was OK and session ID was
//returned)
$ret = sspmod_esmo_Esmo::resume($msToken, $metadata, $sessionVars);


// TODO: verify it works

//Get the expected return url on the session
$returnPage = "";
if (isset($ret['variables']['samlMSstate']['esmo:sp:returnPage']))
    $returnPage = $ret['variables']['samlMSstate']['esmo:sp:returnPage'];
$returnBase = sspmod_esmo_Tools::getBasePath($returnPage);

//Get the actual URL that has been called (to guess which instance we are in)
$currUrlPath = $_SERVER['REQUEST_URI'];
$currBase = sspmod_esmo_Tools::getBasePath($currUrlPath);

//If destination was not the expected one (and we have a destination)
if($currBase != $returnBase && $returnPage != ""){
    SimpleSAML_Logger::debug('*********Wrong destination instance detected. Redirecting.');

    $sessionID = $ret['sessionID'];

    //Get the microservice metadata of the SessionMgr
    $smMetadata = sspmod_esmo_Tools::getMsMetadataByClass("SM",$metadata->getString("msRegistry"));
    SimpleSAML_Logger::debug('ESMO randomly chosen SessionMgr metadata: '.print_r($smMetadata,true));


    //Instantiate the SM handler object
    $smHandler = new sspmod_esmo_SMHandler($metadata, $smMetadata);
    $smHandler->setSessID($sessionID);


    //Generate a new msToken (from this to self)
    $token = $smHandler->generateToken($metadata->getString('msId', NULL),
                                       $metadata->getString('msId', NULL));

    //POST params to send
    $post = array('msToken'  => $token);

    //Redirecting to destination microservice (with HTTP-POST)
    SimpleSAML_Utilities::postRedirect($returnPage, $post);
}






$sessionID = $ret['sessionID'];
$extraData = $ret['extraData'];

//Fetch the variables we stored in session from the SM
$samlMSstate = $ret['variables']['samlMSstate'];
$spRequest = $ret['variables']['spRequest'];
$spMetadata = $ret['variables']['spMetadata'];

//Fetch the expected input parameters from the SM (responseAssertions)
$responseAssertions = $ret['variables']['responseAssertions'];





// --- Build the internal state to go back to the remote SP ---


//Here we will accumulate the attributes to be returned to the remote
//SP [for compatibility with the standard SAML IdP, the eIDAS SAML IdP
//support multi-assertion responses]
$attributes = array();

//Here we will accumulate the same attributes to be returned to the
//remote SP, but separated in assertions based on the source
$assertions = array();


//Restore the state we left after passing on the request
$state = $samlMSstate;
SimpleSAML_Logger::debug('Recovered State on response SP interface:'.print_r($state,true));



//Check that the indicated AuthSource matches the one stored in the
//state associated to the request
assert('array_key_exists("esmo:sp:AuthId", $state)');
if ($state['esmo:sp:AuthId'] !== $sourceId) {
    throw new SimpleSAML_Error_Exception(
        'The authentication source id in the URL does not match the authentication source which sent the request '.$sourceId.' !== '.$state['esmo:sp:AuthId']
    );
}



//Check the status code of the authentication statement (if any) in
//the received response (if none, success is assumed)
$authnStatement = NULL;
foreach($responseAssertions as $responseAssertion){
    if($responseAssertion['type'] === 'AuthResponse') // TODO: still under discussion. review later
        $authnStatement = $responseAssertion;
}



//There was an authentication error
if($authnStatement !== NULL
&& $authnStatement['status']['code'] === 'ERROR'){
    
    //Forward the IdP error to the remote SP
    SimpleSAML_Auth_State::throwException($state,
                                      new sspmod_saml_Error($authnStatement['status']['code'],
                                                            $authnStatement['status']['subcode'],
                                                            $authnStatement['status']['message']));
}



//Authentication status code was 'OK' or none
SimpleSAML_Logger::info("Authentication Successful");



//Process the data sets
foreach($responseAssertions as $responseAssertion){
    SimpleSAML_Logger::info("Assertion: ".print_r($responseAssertion,true));
    
    //Process each attribute in the set
    $attribs = array();
    foreach($responseAssertion['attributes'] as $attribute){
        
        //Build the transfer object of the attribute
        $attrib = array(
            'name'         => $attribute['name'],
            'friendlyName' => $attribute['friendlyName'],
            'mandatory'  => $attribute['mandatory'],
            'values'       => $attribute['values'],
        );
        
        //Add the attribute to the list for this assertion
        $attribs []= $attrib;
        
        
        //Add the attribute also to the standard list [as there might
        //be some attributes with the same name, we merge the values
        //if a previous array of values exists]
        if(isset($attribute['friendlyName']) && $attribute['friendlyName'] != "")
            $name   = $attribute['friendlyName'];
        else
            $name   = $attribute['name'];
        $values = $attribute['values'];
        if(isset($attributes[$name]))
            $values = array_merge($values, $attributes[$name]);
        $attributes[$name] = $values;
    }
    
    
    //Build the transfer object of the data set as an assertion block,
    //to be used by the eIDAS IdP
    $assertionData = array(
        'Issuer'               => $responseAssertion['issuer'],
        'AuthnContextClassRef' => $responseAssertion['loa'],  // TODO: Add more details
        'attributes'           => $attribs,
    );
    
    if(isset($responseAssertion['inResponseTo']))
        $assertionData['InResponseTo'] = $responseAssertion['inResponseTo'];
    if(isset($responseAssertion['properties']['IssueInstant']))
        $assertionData['IssueInstant']  = $responseAssertion['properties']['IssueInstant'];
    if(isset($responseAssertion['properties']['AuthnInstant']))
        $assertionData['AuthnInstant']  = $responseAssertion['properties']['AuthnInstant'];
    
    if(isset($responseAssertion['properties']['NameIDFormat']))
        $assertionData['NameIDFormat']  = $responseAssertion['properties']['NameIDFormat'];
    if(isset($responseAssertion['properties']['NameID'])){
        $assertionData['NameID']        = $responseAssertion['properties']['NameID'];
        $state['saml:sp:NameID']        = $responseAssertion['properties']['NameID'];
    }
    if(isset($responseAssertion['properties']['NameQualifier']))
        $assertionData['NameQualifier'] = $responseAssertion['properties']['NameQualifier']; // TODO: remember to add these properties to the response on the IdP part
    
    //$assertionData['ID'];
    //$assertionData['NotBefore'];
    //$assertionData['NotOnOrAfter'];
    //$assertionData['Address'];
    //$assertionData['Recipient'];          
    //$assertionData['Audience'];  

    SimpleSAML_Logger::info("Assertion Data: ".print_r($assertionData,true));
    
    $assertions []= $assertionData;
    
}



//Store the assertions structure on the state
$state['eidas:struct:assertions'] = $assertions;


SimpleSAML_Logger::info("Standard list Attributes: ".print_r($attributes,true));

/*
 * This solution is commented out because it had holes in it: it broke SAML-eIDAS
 * requests (where the ID of the IDP was not a url, so it added weird suffix); it
 * also didn't ork for the end SP, because the issuer of the response the SP
 * received was not the expected issuer, but this one. So the solution needs to redirect
 * to the proper IdP.
 *


// On SEAL we needed to deploy multiple coordinated instances, but we are limited
// to a single comeback point. It's not a problem to come back to an instance that
// does not match the original one because the state is on the SM, so we don't care
// which instance gets it back, but the SAML IDP tries to load the IDP instance that
// handled the request and fails to find it on the other instance. In the eIDAS IDP,
// it works because we don't support multiple IDP instances, but on the standard IdP
// it's a problem. In any case, we patch this so, we define a default return instance,
// and if the state points to another instance, we replace the basename of the instance
// to the current one, which is the comeback one.

// Get the name of the instance the response will have arrived to
$defaultInstance = $metadata->getString("defaultInstance");

//On eIDAS idp requests, it seems not to be set
if(isset($state['core:IdP'])) {
    $idpID = $state['core:IdP'];

    //We remove the class identifier of the IDP (will always be 'saml2:' or similar)
    $idpID = explode(':', $idpID, 2);

    $classID = $idpID[0];
    $idpURL = $idpID[1];

    //Break the url of the entityID of the actual idp it was used
    $idpURL = parse_url($idpURL);

    //If the base path of the idp ID does not match the base url of
    // the default instance, change it to the proper value
    $urlPath = $idpURL['path'];
    $urlDict = explode('/', $urlPath, 3);
    $basePath = $urlDict[1];
    if ($basePath != $defaultInstance) {
        $urlDict[1] = $defaultInstance;
        $urlPath = implode("/", $urlDict);
    }

    //Rebuild IdP identifier
    $rebuiltURL = "";
    if (isset($idpURL['scheme']))
        $rebuiltURL .= $idpURL['scheme'] . "://";
    if (isset($idpURL['host']))
        $rebuiltURL .= $idpURL['host'];
    if (isset($idpURL['port']))
        $rebuiltURL .= ":" . $idpURL['port'];
    $rebuiltURL .= $urlPath;

    $state['core:IdP'] = $classID . ":" . $rebuiltURL;
}
*/


//Pass the response state to the ESMO SP
$source->handleResponse($state, $remoteIdPMeta->getString('msId', NULL), $attributes);


assert('FALSE');
