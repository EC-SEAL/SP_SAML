<?php
/**
 * The IdP interface endpoint for ESMO microservice Protocol. Will
 * call the specific supported protocol handler and direct to the
 * adequate authSource.
 *
 * @author Francisco José Aragó Monzonís, UJI <farago@uji.es>
 * @package esmo
 */



SimpleSAML_Logger::info('ESMO - IDP.authenticate');






//Hosted IdP config
$idpEntityId = "__DYNAMIC:1__";

$hostedIdpMeta = sspmod_clave_Tools::getMetadataSet($idpEntityId,"esmo-idp-hosted");
SimpleSAML_Logger::debug('ESMO IDP hosted metadata ('.$idpEntityId.'): '.print_r($hostedIdpMeta,true));






// Get the protocolID from the queried URL [Remember to register the
// microservice url with the protocolID on it]
if (!array_key_exists('PATH_INFO', $_SERVER)) {
    throw new SimpleSAML_Error_BadRequest('Missing protocol ID in microservice endpoint URL');
}
$protocolId = substr($_SERVER['PATH_INFO'], 1);
SimpleSAML_Logger::debug("Protocol ID:".$protocolId);




//Instantiate the idp class
$idp = sspmod_esmo_IdP::getById($idpEntityId,$protocolId);





//Receive ESMO microservice security token
if(!isset($_REQUEST['msToken']))
   	throw new SimpleSAML_Error_BadRequest('No msToken POST param received.');

$msToken = $_REQUEST['msToken'];
SimpleSAML_Logger::debug("Received msToken: ".$msToken);



$sessionVars = array(
    'idpRequest'  => true,
    'idpMetadata' => true,
    'spMetadata'  => true);


//Pass the received token to the SM for validation and retrieve the
//needed session variables / call parameters
//(will only go on if validation was OK and session ID was
//returned)
$ret = sspmod_esmo_Esmo::resume($msToken, $hostedIdpMeta, $sessionVars);

$sessionID = $ret['sessionID'];
$extraData = $ret['extraData'];

//Fetch the variables we stored in session from the SM
$spMetadata = $ret['variables']['spMetadata'];

//Fetch the expected input parameters from the SM
$idpRequest  = $ret['variables']['idpRequest'];
$idpMetadata = $ret['variables']['idpMetadata'];




//Build the state for the SAML2 or eIDAS authsource
$state = sspmod_esmo_Esmo::receiveEsmoAuthnRequest($idpRequest, $idpMetadata, $spMetadata);



//Store the session ID on the state, so we can have ti for the comeback
$state['esmo:sessionId'] = $sessionID;

//Store also the protocol ID
$state['esmo:IdP:protocol'] = $protocolId;

//and the request type
$state['esmo:IdP:reqtype'] = 'auth';

//Store variables in state, to avoid having to contact the SM on the comeback
$state['esmo:hostedIdP:metadata'] = $hostedIdpMeta;
$state['esmo:sp:metadata']  = $spMetadata;
$state['esmo:idp:request']  = $idpRequest;
$state['esmo:idp:metadata'] = $idpMetadata;





// Invoke the IdP Class handler.
$idp->handleAuthenticationRequest($state);


assert('FALSE');

