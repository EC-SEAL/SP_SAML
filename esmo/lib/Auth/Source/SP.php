<?php
/**
 * The ESMO SP microservice implementation client side, as a SSP authSource. Will contact the ACM microservice.
 *
 * @author Francisco José Aragó Monzonís, UJI <farago@uji.es>
 * @package Esmo
 */




class sspmod_esmo_Auth_Source_SP extends SimpleSAML_Auth_Source {
    
	/**
	 * The ID of this microservice.
	 *
	 * @var string
	 */
	private $msId;
    
    
	/**
	 * The metadata of this SP (the authSource config file entry content).
	 *
	 * @var SimpleSAML_Configuration.
	 */
	private $metadata;
    
    
    private $idp;
    
    
    /**
     * The metadata of the target microservice (a random config.apiClass ms).
     *
     * @var SimpleSAML_Configuration.
     */
    private $idpMetadata;
    
    
	/**
	 * Constructor for authentication source.
	 *
	 * @param array $info  Information about this authentication source (contains AuthId, the id of this auth source).
	 * @param array $config  Configuration block of this authsource in authsources.php.
	 */
    public function __construct($info, $config) {
        assert('is_array($info)');
        assert('is_array($config)');
        
        // Call the parent constructor first, as required by the interface.
        parent::__construct($info, $config);
        
        
        SimpleSAML_Logger::debug('Called sspmod_esmo_Auth_Source_SP constructor');
        //SimpleSAML_Logger::debug('info: '.print_r($info, true));
        SimpleSAML_Logger::debug('config: '.print_r($config, true));
        
        
        //Load the metadata of the authsource (from the authsources file)
        $this->metadata = SimpleSAML_Configuration::loadFromArray($config, 
        'authsources['.var_export($this->authId,true).']');

        $this->msId = $this->metadata->getString('msId');
        
        $apiClass = $this->metadata->getString("apiClass", 'ACM');
        
        //Get the microservice metadata of the config.apiClass
        try{
            $this->idpMetadata = sspmod_esmo_Tools::getMsMetadataByClass($apiClass, $this->metadata->getString("msRegistry"));
            SimpleSAML_Logger::debug('ESMO randomly chosen -'.$apiClass.'- metadata: '.print_r($this->idpMetadata,true));
        } catch (Exception $e) {
            throw new SimpleSAML_Error_Exception($e->getMessage());
        }

        
        $this->idp = $this->idpMetadata->getString('msId');
        
    }
    
    
    
	/**
	 * Retrieve the URL to the metadata of this SP
	 *
	 * @return string  The metadata URL.
	 */
	public function getMetadataURL() {
        
        //We return the microservice metadata URL at the ConfigMgr
        try{
            $cmMetadata = sspmod_esmo_Tools::getMsMetadataByClass("CM",$this->metadata->getString("msRegistry"));
            SimpleSAML_Logger::debug('ESMO randomly chosen Config Manager metadata: '.print_r($cmMetadata,true));
        } catch (Exception $e) {
            throw new SimpleSAML_Error_Exception($e->getMessage());
        }
        
        foreach($cmMetadata->getArray('publishedAPI',array()) as $api){
            if($api['apiClass'] === 'CM'
            && $api['apiCall'] === 'microservices')
                return $api['apiEndpoint'];
        }
        
        throw new SimpleSAML_Error_Exception("API call 'microservices' from class 'CM'"
        ." not found on any entry in the ".$cmMetadata->getString('msId','')." microservice metadata object: ");
    }
    
    
    
	/**
	 * Retrieve the entity id of this SP.
	 *
	 * @return string  The entity id of this SP.
	 */
	public function getEntityId() {

        return $this->msId;
	}
    
    
    
	/**
	 * Retrieve the metadata of this SP (the authSource content).
	 *
	 * @return SimpleSAML_Configuration  The metadata of this SP.
	 */
	public function getMetadata() {
        
        return $this->metadata;
	}
    
    

	/**
	 * Retrieve the metadata of the called config.apiClass.
	 *
	 * @param string $entityId  The entity id of the IdP.
	 * @return SimpleSAML_Configuration  The metadata of the IdP.
	 */
	public function getIdPMetadata($entityId) {
        
        return $this->idpMetadata;
    }
    
    
    
    
    
	/**
	 * Start login.
	 *
	 * This function saves the information about the login, and redirects to the config.apiClass.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
        assert('is_array($state)');
        
        SimpleSAML_Logger::debug('Called sspmod_esmo_Auth_Source_SP authenticate');
        
        SimpleSAML_Logger::info("state: ".print_r($state,true));
        SimpleSAML_Logger::info("metadata: ".print_r($this->metadata,true));
        
        
        //Go on with the authentication (well, pass control to the destination
        // ms specified in config.apiClass in our case)
        $this->startSSO($this->idp, $state);
        assert('FALSE');   
	}
    
    
    
    
    
	/**
	 * Send a SSO request to the config.apiClass ms.
	 *
	 * @param string $idp  The entity ID of the IdP.
	 * @param array $state  The state array for the current authentication.
	 */
	public function startSSO($idp, array $state) {
        assert('is_string($idp)');
        
        SimpleSAML_Logger::debug('Called sspmod_esmo_Auth_Source_SP startSSO');
        
        
        //Build remote SP metadata variable (entityMetadata) from the SPmetadata in the state
        $remoteSpMeta     = SimpleSAML_Configuration::loadFromArray($state['SPMetadata']);
        
        
        
        //State will be stored on the SM for the comeback, not here
        //locally. Internal state kept only for the local redirections
        //and the needs of ssphp.
        //Save information needed for the comeback on the state variable
        
        // We are going to need the authId in order to retrieve this authentication source later.
        $state['esmo:sp:AuthId']      = $this->authId;
        
        //Not necessary, as the callback url is on the microservice registry, but we store it here just in case
        $state['esmo:sp:returnPage']  = SimpleSAML_Module::getModuleURL('esmo/sp/response.php/'.$this->authId);
        
        //We use the remote SP as the issuer of the internal SP request object
        $state['esmo:req:issuer']  = $state['eidas:requestData']['issuer'];
        if($state['esmo:req:issuer'] == NULL){
            $state['esmo:req:issuer'] = $remoteSpMeta->getString('entityid','default_issuer');
        }

        //Get the api class and call to pass the request to
        $state['esmo:req:apiClass']  = $this->metadata->getString("apiClass", 'ACM');
        $state['esmo:req:apiCall']  = $this->metadata->getString("apiCall", 'acmRequest');


        //Intercept source attribute and remove from request // TODO: Works?
        $spRequestSource = '';
        if(isset($state['eidas:requestData']['requestedAttributes']))
            foreach ($state['eidas:requestData']['requestedAttributes'] as $i => $reqAttr){
                if($reqAttr['name'] == 'SealIdSource'){
                    $spRequestSource = $reqAttr['values'][0];
                    unset($state['eidas:requestData']['requestedAttributes'][$i]);
                    break;
                }
            }
        // If not, search it in IdPList
        if($spRequestSource == ''){
            if(isset($state['saml:IDPList'][0])){
                $spRequestSource = $state['saml:IDPList'][0];
            }
        }


        //Marshall state var in json
        $stateJson = json_encode($state, JSON_UNESCAPED_UNICODE);


        //Build request variable taking stuff from the state object
        $req = sspmod_esmo_Esmo::buildEsmoRequest($state);

        //Marshall request var in json
        $reqJson = json_encode($req, JSON_UNESCAPED_UNICODE);
        
        
        //Build spMetadata variable taking stuff from the ssp metadata object
        $meta = sspmod_esmo_Esmo::buildEsmoEntity($this->msId,$state['SPMetadata']); // TODO: the list of microservices will be part of the read metadata. change it (add the id of this to the list and merge)
        
        //Marshall remote SP metadata var in json
        $remoteSpMetaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
        
        
        
        //Prepare the list of session variables to write in session
        $noWriteList  = $this->metadata->getArray("noWriteParams", Array());
        $sessionVariablesAux = array(
            'spRequest'   => $reqJson,
            'spMetadata'  => $remoteSpMetaJson,
            'samlMSstate' => $stateJson,
            'spRequestEP' => $this->metadata->getString("spRequestEP", 'auth'),
            'spRequestSource' => $spRequestSource,
            // TODO: ADD the SEAL vars here // All SET now?
        );

        $sessionVariables = array();
        foreach ($sessionVariablesAux as $sessVar => $sessValue){
            if( ! in_array($sessVar, $noWriteList)){
                $sessionVariables[$sessVar] = $sessValue;
            }
        }


                
        
        sspmod_esmo_Esmo::redirect($this->metadata,$this->idpMetadata,
            $state['esmo:req:apiClass'],$state['esmo:req:apiCall'],$sessionVariables);
        
        assert('FALSE');   
    }
    
    
    
    
    
    
 	/**
	 * Handle a response from a SSO operation.
	 *
	 * @param array $state  The authentication state.
	 * @param string $idp  The entity id of the remote IdP.
	 * @param array $attributes  The attributes.
	 */
	public function handleResponse(array $state, $idp, array $attributes) {
        assert('is_string($idp)');
        
        
        //TODO: this doesn't follow the original idea, as on a HA
        //environment, the responder could be different from the
        //invoked ACM instance (here we are getting the metadata of
        //the instance we invoked). I'll pass it as is by now, as it
        //is useless, but if needed, take this into account.
        $idpMetadata      = $this->getIdpMetadata($idp);
        $idpMetadataArray = $idpMetadata->toArray();
        
        $spMetadataArray  = $this->metadata->toArray();


        //To comply with the expected format
        if(!isset($idpMetadataArray['entityid'])
        && isset($idpMetadataArray['msId']))
            $idpMetadataArray['entityid'] = $idpMetadataArray['msId'];
        
        
        //To comply with the expected format
        if(!isset($spMetadataArray['entityid'])
        && isset($spMetadataArray['msId']))
            $spMetadataArray['entityid'] = $spMetadataArray['msId'];
        
                
        //Save the state before calling the chain of AuthProcess filters
		$state['esmo:acm:invoked'] = $idp;
		$state['PersistentAuthData'][] = 'esmo:acm:invoked';
        
        $authProcState = array(
			'saml:sp:IdP' => $idp,
			'saml:sp:State' => $state,
			'ReturnCall' => array('sspmod_esmo_Auth_Source_SP', 'onProcessingCompleted'),
            //WARNING: notice that this is done to comply for standard
            //saml modules processing the attributes, specific modules
            //for ESMO should update $state['eidas:struct:assertions']
            //besides $authProcState['Attributes']
			'Attributes' => $attributes,
			'Destination' => $spMetadataArray,  //WARNING: this might not be compatible with pre-existing filters
			'Source' => $idpMetadataArray,      //WARNING: this will not be compatible with pre-existing filters
		);
        
		if (isset($state['saml:sp:NameID'])) {
			$authProcState['saml:sp:NameID'] = $state['saml:sp:NameID'];
		}
		if (isset($state['saml:sp:SessionIndex'])) {
			$authProcState['saml:sp:SessionIndex'] = $state['saml:sp:SessionIndex'];
		}
        $pc = new SimpleSAML_Auth_ProcessingChain($idpMetadataArray, $spMetadataArray, 'sp');
		$pc->processState($authProcState);
        
		self::onProcessingCompleted($authProcState);
    }



	/**
	 * Called when we have completed the processing chain.
	 *
	 * @param array $authProcState  The processing chain state.
	 */
	public static function onProcessingCompleted(array $authProcState) {
		assert('array_key_exists("saml:sp:IdP", $authProcState)');
		assert('array_key_exists("saml:sp:State", $authProcState)');
		assert('array_key_exists("Attributes", $authProcState)');
        
		$idp = $authProcState['saml:sp:IdP'];
		$state = $authProcState['saml:sp:State'];
        
		$sourceId = $state['esmo:sp:AuthId'];
		$source = SimpleSAML_Auth_Source::getById($sourceId);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $sourceId);
		}
        
        
		$state['Attributes'] = $authProcState['Attributes'];
        
        //Return control to the hosted IDP
		SimpleSAML_Auth_Source::completeAuth($state);
	}


    
	/**
	 * Start logout operation.
	 *
	 * @param array $state  The logout state.
	 */
	public function logout(&$state) {
        
    }
    

	/**
	 * Start a SAML 2 logout operation.
	 *
	 * @param array $state  The logout state.
	 */
	public function startSLO2(&$state) {
        
	}

}


assert('FALSE');
