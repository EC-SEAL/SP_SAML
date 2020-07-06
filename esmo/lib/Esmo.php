<?php
/**
 * The ESMO microservice transfer objects building support class.
 *
 * @author Francisco José Aragó Monzonís, UJI <farago@uji.es>
 * @package esmo
 */





class sspmod_esmo_Esmo
{


    // ----------- SP methods  --------------

    
    /**
	 * Build the ESMO attributeSet object representing the generic
	 * request from the state.
	 *
	 * @param array $state  The current state array.
	 * @return array in the form of an attributeSet ESMO object.
	 */
    public static function buildEsmoRequest(array $state){
        
        $now = time();
        $req = array();
        
        
        //Generate the request ID (I do it like this to keep the
        //proper way of doing so, but the stored state will be ignored,
        //as we will use the SM as the storage).
        $id = SimpleSAML_Auth_State::saveState($state, 'esmo:sp:req', true);
        
        
        $req['id']           = $id;
        $req['type']         = "Request";
        
        $req['issuer']       = $state['esmo:req:issuer'];
        $req['recipient']    = NULL;
        
        $req['inResponseTo'] = NULL;
        $req['notBefore']    = gmdate('Y-m-d\TH:i:s\Z',$now);      //Now
        $req['notAfter']     = gmdate('Y-m-d\TH:i:s\Z',$now+300);  //Now+5 min
        
        $req['status'] = array(
            'code'    => null,
            'subcode' => null,
            'message' => null,
        );


        $req['loa'] = null;
        if(isset($state['eidas:requestData']['LoA']))
            $req['loa'] = $state['eidas:requestData']['LoA'];
        else
            $req['loa'] = $state['SPMetadata']['LoA'];
        
        
        $req['attributes']   = array();
        
        //If the remote SP request carried attributes (it was an eIDAS request)
        if(array_key_exists('requestedAttributes', $state['eidas:requestData'])
        && is_array($state['eidas:requestData']['requestedAttributes'])){
            
            foreach($state['eidas:requestData']['requestedAttributes'] as $attr){

                $friendlyName = NULL;
                $name         = NULL;
                $isMandatory  = false;
                
                if(array_key_exists('friendlyName', $attr))
                    $friendlyName = $attr['friendlyName'];

                if(array_key_exists('name', $attr))
                    $name = $attr['name'];

                if(array_key_exists('isRequired', $attr))
                    $isMandatory = $attr['isRequired'];
                
                
                // TODO: add support to read the values of the attributes // TODO: does it work?
                //$values = NULL;
                $values = $attr['values'];
                //$values = array();

                $req['attributes'][] = array (
                    'name' => $name,
                    'friendlyName' => $friendlyName,
                    'encoding' => NULL,
                    'language' => NULL,
                    'isMandatory' => $isMandatory,
                    'values' => $values,
                );
                
            }
            
        }
        else{ //No attributes came on the remote SP request, so we get them from the remote SP metadata
           
            foreach($state['SPMetadata']['attributes']as $attr){
                $req['attributes'][] = array (
                    'name' => $attr,
                    'friendlyName' => $attr,
                    'encoding' => NULL,
                    'language' => NULL,
                    'isMandatory' => false,
                    'values' => NULL,
                );
            }
        }
        
        
        // Add additional data that might be useful for the ACM, RM or the IDPms
        $req['properties'] = array();
        
        $req['properties']['SAML_RelayState']         = $state['saml:RelayState'];
        $req['properties']['SAML_RemoteSP_RequestId'] = $state['saml:RequestId'];
        $req['properties']['SAML_ForceAuthn']         = $state['ForceAuthn'];      
        $req['properties']['SAML_isPassive']          = $state['isPassive'];
        $req['properties']['SAML_NameIDFormat']       = $state['saml:NameIDFormat'];
        $req['properties']['SAML_AllowCreate']        = $state['saml:AllowCreate'];
        $req['properties']['SAML_ConsumerURL']        = $state['saml:ConsumerURL'];
        $req['properties']['SAML_Binding']            = $state['saml:Binding'];
        
        $req['properties']['EIDAS_ProviderName'] = $state['eidas:requestData']['ProviderName'];
        $req['properties']['EIDAS_IdFormat']     = $state['eidas:requestData']['IdFormat'];
        $req['properties']['EIDAS_SPType']       = $state['eidas:requestData']['SPType'];
        $req['properties']['EIDAS_Comparison']   = $state['eidas:requestData']['Comparison'];
        $req['properties']['EIDAS_LoA']          = $state['eidas:requestData']['LoA'];
        $req['properties']['EIDAS_country']      = $state['country'];
/*
  Other possible properties:
    'saml:IDPList'
    'saml:ProxyCount'
    'saml:RequesterID'
    'saml:ConsumerURL'
    'saml:Binding'    
    'saml:Extensions'  
    'saml:AuthnRequestReceivedAt'
    'saml:RequestedAuthnContext' 
*/
        return $req;
    }
    
    
    
      
	/**
	 * Build the ESMO entityMetadata object representing an external
	 * entity from a ssp-like entity object.
	 *
	 * @param string/array $msIDs  The unique ID (or array of) of the microservice that can handle this entity.
	 * @param array $state  The ssp-like metadata object array.
	 * @return array in the form of an entityMetadata ESMO object.  // TODO:  extend to support the conversion of IdP entities. I think it works. Just test
	 */
    public static function buildEsmoEntity($msIDs, array $entityMetadata){
        
        $endpointTypes = array('AssertionConsumerService','SingleLogoutService','SingleLogoutServiceResponse','SingleSignOnService');
        $endpoints = array();
        foreach($endpointTypes as $endpointType){
            if(isset($entityMetadata[$endpointType])){
                $endpointSet = $entityMetadata[$endpointType];
                
                if(!is_array($endpointSet))
                    $endpointSet = array($endpointSet);
                
                foreach($endpointSet as $ep){
                    //If endpoints not in array form, just URLs and default method is used
                    if(!is_array($ep)){
                        $method = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'; // TODO: should be GET ? probably. change it at the end
                        $url    = $ep;
                    }
                    //endpoint is an array
                    else{
                        $method = $ep['Binding'];
                        $url    = $ep['Location'];
                    }
                    
                    //Store endpoint
                    $endpoints []= array(
                        'type'   => $endpointType,
                        'method' => $method,
                        'url'    => $url,
                    );
                }
            }
        }
        
        
        $keys = array();
        if(isset($entityMetadata['keys'])){
            
            foreach($entityMetadata['keys'] as $key){
                if(isset($key['encryption']))
                    $keys []= array(
                        'keyType' => $key['type'],
                        'usage'   => 'encryption',
                        'key'     => $key[$key['type']],
                    );
                if(isset($key['signing']))
                    $keys []= array(
                        'keyType' => $key['type'],
                        'usage'   => 'signing',
                        'key'     => $key[$key['type']],
                    );
            }
        }
        
        //'certData' can be used alternatively to 'keys'. If available, we add it.
        if(isset($entityMetadata['certData'])){
            $keys []= array(
                'keyType' => 'X509Certificate',
                'usage'   => 'signing',
                'key'     => $entityMetadata['certData'],
            );
            $keys []= array(
                'keyType' => 'X509Certificate',
                'usage'   => 'encryption',
                'key'     => $entityMetadata['certData'],
            );
        }
        

        //Any other unclassified protocol or implementation specific piece of data // TODO: add more? allow any?
        $otherdata = array(
            'dialect'       => $entityMetadata['dialect'],
            'subdialect'    => $entityMetadata['subdialect'],
            'spApplication' => $entityMetadata['spApplication'],
            'issuer'        => $entityMetadata['issuer'],
            'sign.logout'       => $entityMetadata['sign.logout'], // TODO: IDP metadata extra data. uncomment and test when required
            'redirect.sign'     => $entityMetadata['redirect.sign'],
            'redirect.validate' => $entityMetadata['redirect.validate'],
        );
        
        
        $displayNames = null;
        if(is_array($entityMetadata['name']))
            $displayNames = $entityMetadata['name'];
        
        
        $entityId = NULL;
        if(isset($entityMetadata['entityid']))
            $entityId = $entityMetadata['entityid'];
        if(isset($entityMetadata['entityId']))
            $entityId = $entityMetadata['entityId'];
        if(isset($entityMetadata['entityID']))
            $entityId = $entityMetadata['entityID'];
        
        
        $microservices = $msIDs;
        if(!is_array($microservices))
            $microservices = array($msIDs);
        
        //Entity metadata might already contain a list of handling microservices
        if(isset($entityMetadata['microservice']))
            array_merge($microservices,$entityMetadata['microservice']);
        
        $esmoMeta = array (
            'entityId'           => $entityId,
            'defaultDisplayName' => $entityMetadata['OrganizationDisplayName'],
            'displayNames'       => $displayNames,
            'logo'               => $entityMetadata['OrganizationLogo'],  // TODO: added by ESMO
            'location'           => $entityMetadata['OrganizationLocation'],  // TODO: added by ESMO
            
            'protocol'           => $entityMetadata['FederationProtocol'],  // TODO: added by ESMO
            'claims'             => $entityMetadata['attributes'],
            'microservice'       => $microservices,
            
            'encryptResponses'       => $entityMetadata['assertion.encryption'],
            'supportedEncryptionAlg' => array($entityMetadata['assertion.encryption.keyAlgorith']),
            'signResponses'          => $entityMetadata['saml20.sign.response'],
            'supportedSigningAlg'    => array($entityMetadata['signature.algorithm']),
            
            'endpoints'    => $endpoints,
            'securityKeys' => $keys,
            
            'otherData' => $otherdata,
        );
        
        return $esmoMeta;
    }
    





    // ----------- IdP/AP methods  --------------
    
    
    
    
    
    /**
	 * Process the ESMO attributeSet object, representing the generic
	 * request and put into the state so it is ready for the SAML2 and
	 * eIDAs authsources.
	 *
	 * @param array $esmoRequest:  an array representing an Esmo attributeSet object for a request.
	 * @param array $targetMetadata  The ESMO entityMetadata object of the remote IdP to call.
	 * @param array $spMetadata  The ESMO entityMetadata object of the remote SP that initiated the request.
	 * @return $state array to be passed to the ESMO IdP.
	 */
    public static function receiveEsmoAuthnRequest($esmoRequest, $targetMetadata, $spMetadata){
        
        return self::receiveEsmoDsaRequest($esmoRequest, $targetMetadata, $spMetadata, NULL);
    }
    
    
    
    
    
    /**
	 * Process the ESMO attributeSet object, representing the generic
	 * request and put into the state so it is ready for the SAML2 and
	 * eIDAs authsources.
	 * 
	 * @param array $esmoRequest:  an array representing an Esmo attributeSet object for a request.
	 * @param array $targetMetadata  The metadata of the remote AP to query.
	 * @param array $spMetadata  The ESMO entityMetadata object of the remote SP that initiated the request.
	 * @param array $authSet An array representing an Esmo
	 *        attributeSet object including the attributes and values
	 *        of a valid authentication.
	 * @return $state array to be passed to the ESMO IdP.
	 */
    public static function receiveEsmoDsaRequest($esmoRequest, $targetMetadata, $spMetadata, $authSet=NULL){

                
        $spMetadataArr = self::buildSspEntity($spMetadata);
        
        
        //Build the reqData object to mimic eIDAs IdP module
        $reqData = array();
        $reqData['id']            = $esmoRequest['id'];
        $reqData['LoA']           = $esmoRequest['loa'];
        $reqData['Comparison']    = $esmoRequest['properties']['EIDAS_Comparison'];
        $reqData['IdFormat']      = $esmoRequest['properties']['EIDAS_IdFormat'];
        $reqData['IdAllowCreate'] = $esmoRequest['properties']['SAML_AllowCreate'];
        $reqData['RelayState']    = $esmoRequest['properties']['SAML_RelayState'];
        $reqData['forceAuthn']    = $esmoRequest['properties']['SAML_ForceAuthn'];
        $reqData['isPassive']     = $esmoRequest['properties']['SAML_isPassive'];
        
        $reqData['assertionConsumerService'] = $esmoRequest['properties']['SAML_ConsumerURL'];
        $reqData['protocolBinding']          = $esmoRequest['properties']['SAML_Binding'];
        
        $reqData['issuer']       = NULL;
        $reqData['SPType']       = $esmoRequest['properties']['EIDAS_SPType'];
        $reqData['ProviderName'] = $esmoRequest['properties']['EIDAS_ProviderName'];
        
        
        $reqData['requestedAttributes'] = array();
        foreach($esmoRequest['attributes'] as $attribute){
            
            $reqData['requestedAttributes'] []= array(
                'name'         => $attribute['name'],
                'friendlyName' => $attribute['friendlyName'],
                'isRequired'   => $attribute['isMandatory'],
                'values'       => $attribute['values'],
            );
        }
        
        
                
        //If any authn attributes (with value) exist, add them to the
        //list of requested attributes. for the SAML authnSource, this
        //will be just ignored. For the eIDAS one, they will be passed
        //along
        if($authSet !== NULL){
            
            //If there is an authnSet, LoA is of the authnSet
            $reqData['LoA'] = $authSet['loa'];
            
            foreach($authSet['attributes'] as $authAttr){
                
                $reqData['requestedAttributes'] []= array(
                    'name'         => $authAttr['name'],
                    'friendlyName' => $authAttr['friendlyName'],
                    'isRequired'   => $authAttr['isMandatory'],
                    'values'       => $authAttr['values'],
                );
            }
        }
        
        
        //Just in case, if after everything, there is no LoA, as it is a must, we set it to Low
        if($reqData['LoA'] == NULL || $reqData['LoA'] == "")
            $reqData['LoA'] = sspmod_clave_SPlib::LOA_LOW;
        
        
        //Build the state object
        $authnContext = null;
        if(isset($reqData['LoA']))
            $authnContext = array(
                'AuthnContextClassRef' => array($reqData['LoA']),
                'Comparison'           => $reqData['Comparison'],
            );
        
        $idFormat = sspmod_clave_SPlib::NAMEID_FORMAT_UNSPECIFIED;
        if(isset($reqData['IdFormat']))
            $idFormat = $reqData['IdFormat'];
        
        $idAllowCreate = FALSE;
        if(isset($reqData['IdAllowCreate']))
            $idAllowCreate = $reqData['IdAllowCreate'];
        
        
        //Set the state to be kept during the procedure
        $state = array(

            //Standard entries used by SSPHP SAML2 authsource
            'Responder'                                   => array('sspmod_esmo_Esmo', 'sendResponse'), //The callback to send the response for this request
            SimpleSAML_Auth_State::EXCEPTION_HANDLER_FUNC => array('sspmod_esmo_Esmo', 'handleAuthError'),
            SimpleSAML_Auth_State::RESTART                => SimpleSAML\Utils\HTTP::getSelfURLNoQuery(),

            'SPMetadata'                  => $spMetadataArr,
            'saml:RelayState'             => $reqData['RelayState'],
            'saml:RequestId'              => $reqData['id'],
            'saml:IDPList'                => array(),
            'saml:ProxyCount'             => null,
            'saml:RequesterID'            => array(),
            'ForceAuthn'                  => $reqData['forceAuthn'],
            'isPassive'                   => $reqData['isPassive'],
            'saml:ConsumerURL'            => $reqData['assertionConsumerService'],
            'saml:Binding'                => $reqData['protocolBinding'],
            'saml:NameIDFormat'           => $idFormat,
            'saml:AllowCreate'            => $idAllowCreate,
            'saml:Extensions'             => NULL,//$reqData, // TODO: commented out, caused crash
            'saml:AuthnRequestReceivedAt' => microtime(true),
            'saml:RequestedAuthnContext'  => $authnContext,

            //eIDAS AuthSource additions
            'sp:postParams'        =>   array(),
            'idp:postParams:mode'  =>   'forward',
            'eidas:request'        =>   NULL,
            'eidas:requestData'    =>   $reqData,

            //ESMO additions
            'esmo:targetms:metadata'    =>   $targetMetadata,
        );
        
        
        
        //Set the remote IdP/AP to query on the authnSource
        $state['saml:idp'] = $targetMetadata['entityId'];
        
        
        
        return $state;
    }    
    
    
    
    
    
    
	/**
	 * Build the SSP compatible entity metadata array representing an
	 * external entity from a ESMO entityMetadata entity object.
	 *
	 * @param array $entityMetadata  The esmo-like entityMetadata object array.
	 * @return array in the form of an ssp metadata array.
	 */
    public static function buildSspEntity(array $entityMetadata){
        
        //Map the basic values
        $sspMeta = array (
            'entityId'                => $entityMetadata['entityId'],
            'OrganizationDisplayName' => $entityMetadata['defaultDisplayName'],
            'attributes'              => $entityMetadata['claims'],
            
            'assertion.encryption'   => $entityMetadata['encryptResponses'],
            'assertion.encryption.keyAlgorith' => array($entityMetadata['supportedEncryptionAlg']),
            'saml20.sign.response'   => $entityMetadata['signResponses'],
            'signature.algorithm'    => array($entityMetadata['supportedSigningAlg']),
            
            'displayNames'         => $entityMetadata['displayNames'], // TODO: added by ESMO
            'OrganizationLogo'     => $entityMetadata['logo'],         // TODO: added by ESMO
            'OrganizationLocation' => $entityMetadata['location'],     // TODO: added by ESMO
            'FederationProtocol'   => $entityMetadata['protocol'],     // TODO: added by ESMO
            'microservice'         => $entityMetadata['microservice'], // TODO: added by ESMO
        );
        
        
        //Now add any other specific purpose properties the entity
        //object might present (Please, notice that any field here
        //with the same nime as one above will override the value set
        //before. This is intentional).
        foreach($entityMetadata['otherData'] as $field => $value){
            $sspMeta[$field] = $value;
        }
        
        
        //Unroll the endpoint list to the diferent endpoint groups and add them to the object      
        $endpointTypes = array('AssertionConsumerService','SingleLogoutService','SingleLogoutServiceResponse','SingleSignOnService');
        $endpoints = array();
        $indexes = array();
        foreach($endpointTypes as $endpointType){
            $endpoints[$endpointType] = array();
            $indexes[$endpointType] = 0;
        }
        if(isset($entityMetadata['endpoints'])){
            foreach($entityMetadata['endpoints'] as $endpoint){
                
                $endpoints[$endpoint['type']] []= array(
                    'index'    => $indexes[$endpointType]++,
                    'Binding'  => $endpoint['method'],
                    'Location' => $endpoint['url'],
                );
            }
            foreach ($endpoints as $endpointType => $values){
                if(sizeof($values)>0)
                    $sspMeta[$endpointType] = $values;
            }
        }
        //Translate the keys array and add them
        $keys = array();
        foreach($entityMetadata['securityKeys'] as $key){
            if($key['usage'] == 'encryption')
                $keys []= array(
                    'encryption'    => true,
                    'signing'       => false,
                    'type'          => $key['keyType'],
                    $key['keyType'] => $key['key'],
                );
            if($key['usage'] == 'signing')
                $keys []= array(
                    'encryption'    => false,
                    'signing'       => true,
                    'type'          => $key['keyType'],
                    $key['keyType'] => $key['key'],
                );
        }
        if(sizeof($keys)>0)
            $sspMeta['keys'] = $keys;
        
        
        //If a key matches the expected type, add it on the legacy field
        if(sizeof($keys)>0
        && $keys[0]['type'] == 'X509Certificate'){
            $sspMeta['certData'] = $keys[0]['X509Certificate'];
        }
        
        
        return $sspMeta;
    }
    
    
    
    
    /**
     * Handle the SM interaction to store state and then redirect to
     * another microservice
     * 
     * @param SimpleSAML_Configuration $origMetadata  Metadata object of the origin microservice (the one that is calling this).
     * 
     * @param SimpleSAML_Configuration $destMetadata  Metadata object of the destination microservice.
     *  
     * @param String $apiClass  Which interface class must we contact on the
     *                   redirection (will be taken from the list of supported enpoints
     *                   of the destination microservice).
     *
     * @param String $apiCall  Which endpoint name on the interface class must we contact on the
     *                  redirection (will be taken from the list of supported enpoints
     *                  of the destination microservice).
     *
     * @param array $sessionVariables  List of variable names and values (json
     *                          already as a string, please) to be stored in the session.
     *
     * @param string $sessionID  The ID of the session at the SM. If null, a new session is started.
     *
     */
    public static function redirect(SimpleSAML_Configuration $origMetadata, SimpleSAML_Configuration $destMetadata,
                                    $apiClass,                     $apiCall,
                                    array $sessionVariables,       $sessionID=NULL)
    {
        
        //Get the microservice metadata of the SessionMgr
        try{
            $smMetadata = sspmod_esmo_Tools::getMsMetadataByClass("SM",$origMetadata->getString("msRegistry"));
            SimpleSAML_Logger::debug('ESMO randomly chosen SessionMgr metadata: '.print_r($smMetadata,true));
        } catch (Exception $e) {
            throw new SimpleSAML_Error_Exception($e->getMessage());
        }
        
        //Instantiate the SM handler object
        $smHandler = new sspmod_esmo_SMHandler($origMetadata,$smMetadata);
        
        
        
        //Start SM session, if no sessionId is passed
        if($sessionID === NULL)
            $smHandler->startSession();
        else
            $smHandler->setSessID($sessionID);
            
        
        
        //Write all the passed state variables (values are in json format), in SM session
        foreach($sessionVariables as $varName => $jsonValue)
            $smHandler->writeSessionVar($jsonValue,$varName);
        
        
        
        //Request a token to the SM (addressed to the selected destination microservice)
        $token = $smHandler->generateToken(
            $origMetadata->getString('msId'),
            $destMetadata->getString('msId')
        );
        
        
        
        //Get the url of the api to call, on the destination microservice
        $redirectUrl = NULL;
        $endpoints = $destMetadata->getArray('publishedAPI');
        foreach($endpoints as $endpoint){
            if($endpoint['apiClass'] === $apiClass
            && $endpoint['apiCall'] === $apiCall){
                $redirectUrl = $endpoint['apiEndpoint'];
                break;
            }
        }
        if ($redirectUrl === NULL)
            throw new Exception('Could not find an -'.$apiClass.'/'.$apiCall.' endpoint in microservice ' . $destMetadata->getString('msId'));
        
        
        //POST params to send
        $post = array('msToken'  => $token);
        
        //Redirecting to destination microservice (with HTTP-POST)
        SimpleSAML_Utilities::postRedirect($redirectUrl, $post);
        
    }


    /**
     * Handle the SM interaction to validate an incomig redirection
     * call and then retrieve the required session variables and call
     * parameters
     * 
     * @param string $msToken  The ESMO security token received on the call.
     * 
     * @param SimpleSAML_Configuration $metadata  Metadata object of this microservice that is being called.
     *  
     *
     * @param array $sessionVars List of variable names, to be
     *                           retrieved from the session (if value is true, means they are
     *                           mandatory, if false optional, default:optional).
     * 
     * @return array  Contains the session ID, other data that might
     *                have come in the token, and the list of requested variables
     *                with their value.
     *
     */
    public static function resume($msToken, $metadata, $sessionVars)
    {
        //Get the microservice metadata of the SessionMgr
        $smMetadata = sspmod_esmo_Tools::getMsMetadataByClass("SM",$metadata->getString("msRegistry"));
        SimpleSAML_Logger::debug('ESMO randomly chosen SessionMgr metadata: '.print_r($smMetadata,true));
        
        
        //Instantiate the SM handler object
        $smHandler = new sspmod_esmo_SMHandler($metadata, $smMetadata);
        
        
        //Pass the received token to the SM for validation
        //(will only go on if validation was OK and session ID was returned)
        $extraData = $smHandler->validateToken($msToken);
        $sessionID = $smHandler->getSessID();
        
        
        //Fetch the variables we stored in session / input parameters from the SM
        $fetchedVariables = array();
        foreach($sessionVars as $variable=>$mandatory){
            
            $fetchedVariables[$variable] = json_decode($smHandler->getSessionVar($variable),true);
            if($fetchedVariables[$variable] === NULL && $mandatory)
                throw new SimpleSAML_Error_Exception("Error restoring variable -".$variable."- retrieved from the Session Manager");
        }
        
        return array(
            'sessionID' => $sessionID,
            'extraData' => $extraData,
            'variables' => $fetchedVariables,
        );
    }
    
    
    
    

    
    
    
    
    
    
    /**
	 * Build the ESMO attributeSet object representing the generic
	 * response from the state.
	 *
	 * @param array $state  The current state array.
	 * @return array in the form of an attributeSet ESMO object.
	 */
    private static function buildEsmoResponse(array $state){
        
        $now = time();
        
        //Recover ESMO state
        $sessionID  = $state['esmo:sessionId'];
        $protocolId = $state['esmo:IdP:protocol'];
        
        $hostedIdpMeta = $state['esmo:hostedIdP:metadata'];
        $spMetadata    = $state['esmo:sp:metadata'];
        $idpRequest    = $state['esmo:idp:request'];
        $idpMetadata   = $state['esmo:idp:metadata'];


        //Create a set of all the requested attributes' name and friendly name
        $requestedAttributes = array();
        foreach($idpRequest['attributes'] as $attribute){
            $requestedAttributes [] = $attribute['name'];
            $requestedAttributes [] = $attribute['friendlyName'];
        }
        SimpleSAML_Logger::debug('List of requested attributes: '.print_r($requestedAttributes,true));
        
        
        
        //Find the assertion data to build the response:
        
        //Special structure to build the assertions from scratch if a
        //multi-assertion response is required. No standard
        //AuthFilters may apply here
        $structassertions = null;
        if(isset($state['eidas:struct:assertions']))
            $structassertions = $state['eidas:struct:assertions'];
        
        //The standard ssp attributes. These may have gone through any
        //standard AuthFilter modification
        $singleassertion = null;
        if(isset($state['Attributes']))
            $singleassertion = $state['Attributes'];
        
        
        
        
        
        //Build ESMO attributeList response object
        $resp = array();
        
        
        $resp['id'] = sspmod_esmo_HttpSig_Client::generateUUIDv4();
        
        $resp['issuer']       = $idpMetadata['entityId'];
        $resp['recipient']    = $idpRequest['issuer'];
        $resp['inResponseTo'] = $idpRequest['id'];
        $resp['notBefore']    = gmdate('Y-m-d\TH:i:s\Z',$now);      //Now
        $resp['notAfter']     = gmdate('Y-m-d\TH:i:s\Z',$now+300);  //Now+5 min
        
        
        //Set the object type
        $resp['type'] = "Response";
        if($state['esmo:IdP:reqtype'] === 'auth')
            $resp['type'] = "AuthResponse";
        
        
        //Set the response LoA
        if($structassertions !== NULL)
            $resp['loa'] = $structassertions[0]['AuthnContextClassRef'];
        else if(isset($state['saml:AuthnContextClassRef']))
            $resp['loa'] = $state['saml:AuthnContextClassRef'];
        else
            $resp['loa'] = $idpRequest['loa'];
        
        
        //Build the attribute section of the ESMO response. If
        //multiassertion response was received, just flat it out
        $resp['attributes'] = array();
        if($structassertions !== NULL){
            
            foreach($structassertions as $assertionData){
                foreach($assertionData['attributes'] as $attribute){
                    
                    //Remove non-requested response attributes     // TO DO: make it configurable per SP and on esmo-idp-hosted, whether to remove or not
                    if(!in_array($attribute['name'],$requestedAttributes)
                    &&!in_array($attribute['friendlyName'],$requestedAttributes))
                        continue;
                        
                    $resp['attributes'] []= array(
                        'friendlyName' => $attribute['friendlyName'],
                        'name'         => $attribute['name'],
                        'encoding'     => NULL,
                        'language'     => NULL,
                        'isMandatory'  => false,
                        'values'       => $values,
                    );
                }
            }
        }
        else if($singleassertion !== NULL){
            
            foreach($singleassertion as $attributename => $values){
                
                //In some cases, I might have stored the full names here:
                $attributefullname = $attributename;
                if(isset($state['eidas:attr:names']))
                    if(isset($state['eidas:attr:names'][$attributename]))
                        $attributefullname = $state['eidas:attr:names'][$attributename];

                //Remove non-requested response attributes     // TO DO: make it configurable per SP and on esmo-idp-hosted, whether to remove or not
                if(!in_array($attributename,$requestedAttributes)
                &&!in_array($attributefullname,$requestedAttributes))
                    continue;
                
                $resp['attributes'] []= array(
                     'friendlyName' => $attributename,
                     'name'         => $attributefullname,
                     'encoding'     => NULL,
                     'language'     => NULL,
                     'isMandatory'  => false,
                     'values'       => $values,
                 );
            }
        }
                

        //Build the status of the response:
        $resp['status'] = array(
            'code'    => $state['esmo:status']['code'], // ERROR  OK
            'subcode' => $state['esmo:status']['subcode'],
            'message' => $state['esmo:status']['message'],
        );
        
        
        // Add additional data that might be useful for the ACM, RM or the IDPms
        $resp['properties'] = array();
        
        if(isset($state['AuthnInstant']))
            $resp['properties']['AuthnInstant'] = $state['AuthnInstant'];
        
        if(isset($state['saml:NameIDFormat']))
            $resp['properties']['NameIDFormat'] = $state['saml:NameIDFormat'];
        
        if(isset($state['saml:NameIDFormat'])
        && isset($state['saml:NameID'][$state['saml:NameIDFormat']]))
            $resp['properties']['NameID'] = $state['saml:NameID'][$state['saml:NameIDFormat']];
        else if(isset($state['saml:sp:NameID']))
            $resp['properties']['NameID'] = $state['saml:sp:NameID'];
        
        
        //$resp['properties']['NameQualifier'] = ;
        //$resp['properties']['IssueInstant'] = ;
        $resp['properties']['Binding'] = $state['saml:Binding'];
        
        
        if(isset($state['saml:RelayState']))
            $resp['properties']['RelayState'] = $state['saml:RelayState'];
        
        
        
        return $resp;
    }
    
    
    
    
    
    /**
     * Handle a succressful response. To be called by the
     * authSource on the way back if error
     *
     * SimpleSAML_Error_Exception $exception  The exception.
     *
     * @param array $state The error state.
     */
    public static function sendResponse(array $state)
    {


        //Deduce the status and write itinto the state (search for
        //eIDAS authsource status, if not, this is a success-only
        //call, so it must be that)
        if (isset($state['eidas:status'])){
            $status = array(
                'code'    => $state['eidas:status']['MainStatusCode'],
                'subcode' => $state['eidas:status']['SecondaryStatusCode'],
                'message' => $state['eidas:status']['StatusMessage'],
            );
        }else{ //The AuthSource was standard, so a call here can only happen on success
            $status = array(
                'code'    => 'OK',
                'subcode' => NULL,
                'message' => NULL,
            );
        }
        $state['esmo:status'] = $status;

        
        $hostedIdpMeta = $state['esmo:hostedIdP:metadata'];
        
        //Build the ESMO attributeList object containing the response
        $resp = self::buildEsmoResponse($state);
        
        
        
        
        
        //Find here the ESMO sessionID
        if(!isset($state['esmo:sessionId']) || $state['esmo:sessionId'] == "")
            throw new SimpleSAML_Error_Exception('No ESMO session ID found on comeback.');
        $sessionID = $state['esmo:sessionId'];
        
        
        
        //Get the metadata of a random ms that implements the destination API to contact
        try{
            $destMetadata = sspmod_esmo_Tools::getMsMetadataByClass("ACM",$hostedIdpMeta->getString("msRegistry"));
            SimpleSAML_Logger::debug('ESMO randomly chosen ACM metadata: '.print_r($destMetadata,true));
        } catch (Exception $e) {
            throw new SimpleSAML_Error_Exception($e->getMessage());
        }
        
        
        
        $sessionVariables = array(
            'dsResponse' => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'dsMetadata' => json_encode($state['esmo:targetms:metadata'], JSON_UNESCAPED_UNICODE),
        );
        
        
        
        sspmod_esmo_Esmo::redirect(
            $hostedIdpMeta, $destMetadata,
            'ACM', 'acmResponse',
            $sessionVariables,$sessionID); // TODO: verify the apiCall name is 'acmResponse'
        
    }
    
    
    
    
    
    /**
     * Handle authentication/query error. To be called by the
     * authSource on the way back if error
     *
     * SimpleSAML_Error_Exception $exception  The exception.
     *
     * @param array $state The error state.
     */
    public static function handleAuthError(SimpleSAML_Error_Exception $exception, array $state)
    {
        
        //Build an error state
        $state['eidas:status'] = array(
            'MainStatusCode'      => 'ERROR',
            'SecondaryStatusCode' => $exception->getCode(),
            'StatusMessage'       => $exception->getMessage(),
        );
        
        //Call the send function
        self::sendResponse($state);
        
    }
    
    
}