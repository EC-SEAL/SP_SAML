<?php





class sspmod_esmo_HttpSig_Fetch
{
    
    //SHA256 fingerprint of public Key, in hex
    private $keyId;
    
    //In PEM format
    private $privateKey;

    //array, list of trusted server public keys (to validate the response)
    private $trustedKeys;
    
    
    
    public function __construct($keyID, $clientKey, $serverPubKeys){
        
        $this->keyId = $keyID;
        $this->privateKey = $clientKey;

        //The PEM headers and the 64 char lines division are needed, so we check:
        if(!preg_match("/BEGIN RSA PRIVATE KEY/",$this->privateKey)){
            $this->privateKey = "-----BEGIN RSA PRIVATE KEY-----\n"
                .chunk_split($this->privateKey,64,"\n")
                ."-----END RSA PRIVATE KEY-----\n";
        }
        
        $this->trustedKeys = $serverPubKeys;
    }
    
    
    
    public function get($url){
        return $this->sendAndRetry(sspmod_esmo_HttpSig_Client::$METHOD_GET,$url,NULL,sspmod_esmo_HttpSig_Client::CT_JSON);
    }
    
    public function postForm($url,$query=NULL){
        return $this->sendAndRetry(sspmod_esmo_HttpSig_Client::$METHOD_POST,$url,$query,sspmod_esmo_HttpSig_Client::CT_FORM);
    }

    public function postJson($url,$jsonBody=NULL){
        return $this->sendAndRetry(sspmod_esmo_HttpSig_Client::$METHOD_POST,$url,$jsonBody,sspmod_esmo_HttpSig_Client::CT_JSON);
    }
        
    private function send($method,$url,$body,$contentType){

        $authClient = new sspmod_esmo_HttpSig_Client();
        
        //Set method
        $authClient->setRequestMethod($method);
                
        //To sign the request
        $authClient->setKeyId($this->keyId);
        $authClient->setPrivKeyPem($this->privateKey);
        
        //To validate the response
        $authClient->setTrustedCertList($this->trustedKeys);
        
        //Request destination and contents   // TODO: there's soemthing wrong with the GET signature calculation (maybe due to the empty body? in empty POST it does work)
        $authClient->setRequestUrl($url);
        $authClient->setRequestContentType($contentType);
        if($body !== NULL){ // TODO: leave it as this? or pass a NULL? if no body, this stays as unset. The error happens with NULL and ""
            $authClient->setRequestContent($body);
        }
        
        $res = $authClient->sendRequest();
        
        
        if($res === NULL || $res === False)
            throw new SimpleSAML_Error_Exception("Error. No response body");

        SimpleSAML_Logger::debug('Response RAW:'.$res);
        
        $res = json_decode($res, TRUE);
        if($res === False){
            SimpleSAML_Logger::debug('Error parsing JSON string:'.$res);
            throw new SimpleSAML_Error_Exception("Bad json");
        }

        if($res['code'] === "ERROR")
            throw new SimpleSAML_Error_Exception("Remote microservice returned error code: ".$res['error']);
        
        
        SimpleSAML_Logger::debug('Response JSON:'.print_r($res,true));
                
        return $res;
    }
    
    
    private function sendAndRetry($method,$url,$body,$contentType){
        
        $retries = 5;
        
        while($retries > 0){
            try{
                return $this->send($method,$url,$body,$contentType);
            } catch (Exception $e) {
                $retries--;
                SimpleSAML_Logger::warning('HTTPSig client returned error (retries left: '.$retries.'): '.$e->getMessage());
                if($retries > 0)
                    continue;
                else
                    throw $e;
            }
        }
        
    }
    

}

