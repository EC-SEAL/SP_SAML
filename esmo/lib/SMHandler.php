<?php


class sspmod_esmo_SMHandler {
    
    
    /**
	 * The ID of the session at the Session Manager.
	 *
	 * @var string
	 */
    private $sessId;


    /**
	 * The metadata of this microservice.
	 *
	 * @var array
	 */
    private $metadata;


    /**
	 * The metadata of the SM microservice.
	 *
	 * @var array
	 */
    private $smMetadata;

    /**
	 * The HTTPSig client instance.
	 *
	 * @var sspmod_esmo_HttpSig_Fetch
	 */
    private $httpsig;

    
    
    
    public function getSessID(){
        return $this->sessId;
    }
    
    public function setSessID($sessId){
        $this->sessId = $sessId;
    }
    
    
    
    private function getApiUrl($apiClass,$apiCall){

        foreach($this->smMetadata->getArray('publishedAPI',array()) as $api){
            if($api['apiClass'] === $apiClass
            && $api['apiCall'] === $apiCall)
                return $api['apiEndpoint'];
        }
        
        throw new SimpleSAML_Error_Exception("API call ".$apiCall." from class ".$apiClass
        ." not found on any entry in the ".$this->smMetadata->getString('msId','')." microservice metadata object: ");
    }
    
    
    
    // Metadata : the AuthSource metadata array
    public function __construct($metadata, $smMetadata){
        
        $this->metadata   = $metadata;
        $this->smMetadata = $smMetadata;
        
        //Create an HTTPSig client helper
        $this->httpsig = new sspmod_esmo_HttpSig_Fetch($metadata, $smMetadata);
    }
    
    

    //SessionManager API calls

    
    public function startSession(){
        $url = self::getApiUrl('SM','startSession');
        
        $res = $this->httpsig->postForm($url); // TODO: update, now api has changed!!
        
        if($res['code'] == 'ERROR')
            throw new SimpleSAML_Error_Exception("Session start failed: ".$res['error']);
        
        if(!isset($res['sessionData']['sessionId']))
            throw new SimpleSAML_Error_Exception("No sessionID on response"); // TODO: turn to specific exception    
        $this->sessId = $res['sessionData']['sessionId'];

        return $this->sessId;
    }
    
    
    
    //Retrieve the sessionID using an alternative session identifier stored on a session variable
    public function getSession($variable,$value){
        $url = self::getApiUrl('SM','getSession')."?varName=".urlencode($variable)."varValue=".urlencode($value);
        $body = "{}"; //An empty JSON object
        
        $res = $this->httpsig->get($url);
        
        if(!isset($res['additionalData']))
            throw new SimpleSAML_Error_Exception("No sessionID on response"); // TODO: revise when implemnting the IdP part, where I will use ti 
        $this->sessId = $res['additionalData'];
    }
        
    
    
    public function endSession(){
        $url = self::getApiUrl('SM','endSession')."?sessionId=".urlencode($this->sessId);
        $body = "{}"; //An empty JSON object

        $res = $this->httpsig->get($url); //TODO: check this.
        
        if($res['code'] == 'ERROR')
            throw new SimpleSAML_Error_Exception("Session end failed: ".$res['error']);
    }
    
    
    
    //If name=NULL, get the whole session object
    public function getSessionVar($name=NULL){
        
        SimpleSAML_Logger::debug('Requesting session variable (null means whole session object):'.$name);
        
        $varname='';
        if($name !== NULL){
            assert('is_string($name)');
            $varname = "&variableName=".urlencode($name);
        }
        
        //TODO: WARNING: building the request URL this way assumes
        //that the api url hasn't got a query url already (otherwise,
        //the ? will appear two times)
        $url = self::getApiUrl('SM','getSessionData')."?sessionId=".urlencode($this->sessId).$varname;
        
        
        $res = $this->httpsig->get($url);
        SimpleSAML_Logger::debug('Received response:'.print_r($res,true));
        
        if($res['code'] !== 'OK')
            throw new SimpleSAML_Error_Exception("Variable fetch failed: ".$res['error']);
        
        if(!isset($res['sessionData']))
            throw new SimpleSAML_Error_Exception("No sessionData on response");

        if(!isset($res['sessionData']['sessionVariables']))
            throw new SimpleSAML_Error_Exception("No sessionVariables on response");

        if($name !== NULL){
            if(!isset($res['sessionData']['sessionVariables'][$name])){
                //throw new SimpleSAML_Error_Exception("Variable '$name' not on response");
                return NULL;
            }
            
            $obj = $res['sessionData']['sessionVariables'][$name];
        }
        else{
            $obj = $res['sessionData']['sessionVariables'];
        }
        
        return $obj; // TODO: check the return is OK.
    }
    
    
    
    //If name=NULL, write the whole session object
    public function writeSessionVar($value,$name=NULL){
        
        SimpleSAML_Logger::debug('Writing session variable (null means whole session object):'.$name.' with value: '.$value);
        
        //$varname=''; //If we send an emtpy string, it will write the whole session object
        //if($name !== NULL){
        //    assert('is_string($name)');
        //    $varname = "&variableName=".urlencode($name);
        //}
        
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        if($json === NULL)
            throw new SimpleSAML_Error_Exception("Error encoding value of var $name in json: ".$value);
        
        
        
        $body = '{
          "sessionId":  "'.urlencode($this->sessId).'",
          "variableName": "'.$name.'",
          "dataObject": '.$json.'
          }';
        
        
        $url = self::getApiUrl('SM','updateSessionData');

        SimpleSAML_Logger::debug('Body sent:'.$body);
        $res = $this->httpsig->postJson($url,$body);  // TODO: does it work?
        SimpleSAML_Logger::debug('Received response:'.print_r($res,true));
        
        if($res['code'] == 'ERROR')
            throw new SimpleSAML_Error_Exception("Variable -".$name."- write failed: ".$res['error']);
    }
    
    
    
    //The msId of the destination microservice
    public function generateToken($origin,$destination,$data=NULL){
        assert('is_string($destination)');
        
        if($origin === NULL || $origin === '')
            throw new SimpleSAML_Error_Exception("Error:  origin ms not defined"); // TODO: turn to specific exception
        
        if($destination === NULL || $destination === '')
            throw new SimpleSAML_Error_Exception("Error:  destination ms not defined"); // TODO: turn to specific exception
        
        
        $sender   = "&sender=".urlencode($origin);
        $receiver = "&receiver=".urlencode($destination);

        $additionalData = "";
        if($data !== NULL)
            $additionalData = "&data=".urlencode($data);  // TODO: what is he expecting here? a string to be included as a claim? a json with list of claims? a json object to be included as is, as string or as b64?
        
        
        $url = self::getApiUrl('SM','generateToken')."?sessionId=".urlencode($this->sessId).$sender.$receiver.$additionalData;

        SimpleSAML_Logger::debug('Requesting token:'.$url);
        $res = $this->httpsig->get($url);
        SimpleSAML_Logger::debug('Received response:'.print_r($res,true));
        
        if($res['code'] == 'ERROR')
            throw new SimpleSAML_Error_Exception("Token generation failed: ".$res['error']);
        
        if(!isset($res['additionalData']))
            throw new SimpleSAML_Error_Exception("No token on response");
        return $res['additionalData'];
    }
    
    
    //If validated, session token will be returned
    public function validateToken($token){
        assert('is_string($token)');
        
        $url = self::getApiUrl('SM','validateToken')."?token=".urlencode($token);
        
        $res = $this->httpsig->get($url);
        
        if(!isset($res))
            throw new SimpleSAML_Error_Exception("Bad response for validateToken");
        
        if($res['code'] !== 'OK')
            throw new SimpleSAML_Error_Exception("Token validation failed: ".$res['error']);
        
        
        if(!isset($res['sessionData']['sessionId']))
            throw new SimpleSAML_Error_Exception("No sessionID on response"); // TODO: turn to specific exception    
        $this->sessId = $res['sessionData']['sessionId'];
        
        
        SimpleSAML_Logger::debug('sessionID retrieved from token:'.$res['sessionData']['sessionId']);
        SimpleSAML_Logger::debug('additionalData:'.$res['additionalData']);
        
        return $res['additionalData']; // TODO: where would extra data be returned?
    }
    
}