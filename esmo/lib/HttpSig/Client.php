<?php





class sspmod_esmo_HttpSig_Client
{

    //Methods
    public static $METHOD_POST = 'POST';
    public static $METHOD_GET  = 'GET';


    //Algorithms
	public static $DIGEST_ALGO_SHA256 = 'sha256';
	public static $SIGN_ALGORITHM_RSA_SHA256 = 'rsa-sha256';


    //Constants
	public static $DATE_THRESHOLD = 300; // 5 min.
    
    
    
	private $_request_content;
	private $_request_content_type;
	private $_request_url;
	private $_request_method;	
	private $_request_id;
	private $_key_id;
	private $_priv_key_pem;
	private $_signature;
	
	private $_request_target;
	private $_request_host;
	
	private $_timestamp;
	private $_digest;
	
	private $_digest_algorithm;
	private $_sign_algorithm;
	
	private $_server_key;
	private $_server_timestamp;
	private $_server_orig_timestamp;
	private $_server_digest;
	private $_server_sign_algo;
    


    const CT_JSON = "application/json";
    const CT_FORM = "application/x-www-form-urlencoded";
    
    
    private $_trusted_certs;
	
	
	public function __construct()
	{
		$this->_timestamp = time();
		
		// Default values
		$this->_request_id = self::generateUUIDv4(); 
		$this->_request_method = $this::$METHOD_POST;
		$this->_digest_algorithm = $this::$DIGEST_ALGO_SHA256;
		$this->_sign_algorithm = $this::$SIGN_ALGORITHM_RSA_SHA256;
	}
	

    
	public function setRequestContent($request_content)
	{
		$this->_request_content = $request_content;
	}
    
    
    public function setRequestContentType($request_content_type)
	{
		$this->_request_content_type = $request_content_type;
	}
    
    
	public function setRequestUrl($request_url)
	{
		$this->_request_url = $request_url;
		
		$aux = parse_url($request_url);
		
		$host = $aux['host'];
		if (isset($aux['port']))
		{
			$host = $host.":".$aux['port'];
		}
		$this->_request_host = $host;
		
		$this->_request_target = $aux['path'];

        if (isset($aux['query']))
        {
            $this->_request_target.= '?'.$aux['query'];
        }
	}
	
    
    
    //To fix the request ID (a UUIDv4 in canonic form)
	public function setRequestId($request_id)
	{
		$this->_request_id = $request_id;
	}
    
    
    
	//KeyId of the  public key, fingerprint HEX-encoded SHA-256
	public function setKeyId($key_id)
	{
		$this->_key_id = $key_id;
	}
	
	// Private key in PEM format used to sing the request
	public function setPrivKeyPem($priv_key_pem)
	{
		$this->_priv_key_pem = $priv_key_pem;
	}
	

	public function setRequestMethod($request_method)
	{
		$this->_request_method = $request_method;
	}
    


    public function setTrustedCertList($trustedCerts)
	{
		$this->_trusted_certs = $trustedCerts;
	}
    
    
    
	/*
	private function _getDateFormat()
	{
		return gmdate('D, d M Y H:i:s', $this->_timestamp).' GMT';
	}
	*/
	
	private function _getDateFormat($time)
	{
		$currentLocal = setlocale(LC_ALL, 0);
		setlocale(LC_ALL, 'en_US');
		$date = gmdate('D, d M Y H:i:s', $time).' GMT';
		setlocale(LC_ALL, $currentLocal);
		return $date;
	}
	
	// TODO: Algoritmo SHA-256, creo que el digest es del body (consultar)
	private function _generateDigest()
	{
		//echo 'Content: '.$this->_request_content.'<br>';
		//echo 'Alg: '.$this->_digest_algorithm.'<br>';
		$this->_digest = base64_encode(hash($this->_digest_algorithm, $this->_request_content, true));
	}
	
    
    
	private function _getHttpSignaturetHeaders()
	{
		$headers = array();
		$headers[] = "Date: ".$this->_getDateFormat($this->_timestamp);
		$headers[] = "Original-Date: ".$this->_getDateFormat($this->_timestamp);
		$headers[] = "X-Request-Id: ".$this->_request_id;
		$headers[] = "Digest: ".$this->_getDigestHeader()."=".$this->_digest;
		$headers[] = "Authorization: ".$this->_generateAuthorizationHeader();
		$headers[] = "Accept-Signature: ".$this->_sign_algorithm;
		
		return $headers;
	}
    
    
    
	//Compose signing string with the headers specified on the Authorisation header.
	private function _composeSigningString()
	{
		$signingString = "(request-target): ".strtolower($this->_request_method)." $this->_request_target\n";
		$signingString .= "host: $this->_request_host\n";
		$signingString .= "date: ".$this->_getDateFormat($this->_timestamp)."\n";
		$signingString .= "original-date: ".$this->_getDateFormat($this->_timestamp)."\n";
		$signingString .= "digest: ".$this->_getDigestHeader()."=$this->_digest\n";
		$signingString .= "x-request-id: $this->_request_id";
/*		$signingString .= "\n";		// TODO: CHECK IF GET SENDS A BLANK LINE AFTER HEADERS
		
		if  ($this->_request_method = $this::$METHOD_POST) 
		{			
			$signingString .= $this->_request_content;
		}
*/
		return $signingString;
	}
	
	//Generate signature using client's private key (rsa-sha256 default)
	private function _generateSignature($signingString)
	{
		$privKey = openssl_pkey_get_private($this->_priv_key_pem);
		openssl_sign($signingString, $signature, $privKey, $this->_getSignAlgorithmOPENSSL());
		return base64_encode($signature);
	}
	
	//Authorisation header. Holds signature metadata (key ID, sig alg, signed headers, signature in base64)
	private function _generateAuthorizationHeader()
	{
		$headers = '(request-target) host date original-date digest x-request-id';
		$this->_signature = $this->_generateSignature($this->_composeSigningString());
		
		$authHeader = "Signature keyId=\"$this->_key_id\",algorithm=\"$this->_sign_algorithm\",headers=\"$headers\", signature=\"$this->_signature\"";
		return $authHeader;
	}
    
    
    
	
	public function sendRequest()
	{
		$this-> _generateDigest();
		
		$headers = array();
        //	$headers[] = strtoupper($this->_request_method).' '.$this->_request_url.' HTTP/1.1'; // TODO: this was being sent. seems a mistake. for now it works. remove when sure.
		//$headers[] = 'Content-type: application/json';
        $headers[] = 'Accept: */*';
		$headers[] = 'Content-type: '.$this->_request_content_type;
		$headers[] = 'Content-length: '.strlen($this->_request_content);		
		$headers = array_merge($headers, $this->_getHttpSignaturetHeaders());
		
		SimpleSAML_Logger::debug('Headers passed:'.print_r($headers,true));
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->_request_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLINFO_HEADER_OUT, true); // For debugging, to return the raw request on the info
		
		if  ($this->_request_method == $this::$METHOD_POST)
		{
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->_request_content);
		}
        
        
		$response = curl_exec($curl);

        
        //If error in the HTTP communication, throw it
        if(curl_errno($curl)){
            throw new SimpleSAML_Error_Exception(curl_error($curl));
        }
        
		$info = curl_getinfo($curl);

        SimpleSAML_Logger::debug('Raw sent HTTP request:'.$info['request_header']);

        SimpleSAML_Logger::debug('Raw received HTTP response:'.$response);
        
        $header_size  = $info['header_size'];
        $headerstr    = substr($response, 0, $header_size);
		$resp_headers = explode("\r\n", trim($headerstr));
		$resp_body    = substr($response, $header_size);//substr($response, -$info['download_content_length']);
        
		curl_close($curl);
		
		//$this->_validateServerResponse($resp_headers, $resp_body); // TODO: validate the response signature // TODO: make it optional
		
		if (strpos($info['http_code'], '2') !== 0)
		{
			throw new SimpleSAML_Error_Exception("Server returned error HTTP code: ".$info['http_code'].' -> '.$resp_body);
		}
        
        SimpleSAML_Logger::debug('Response body:'.$resp_body);
        
		return $resp_body;
	}
    
    



    // Response validation
    
    
	
	private function _getDigestHeader()
	{
		$digest_header;
		
		switch ($this->_digest_algorithm)
		{
			case $this::$DIGEST_ALGO_SHA256:
				$digest_header = 'SHA-256';
				break;
			default:
				$digest_header = 'SHA-256';
		}
		
		return $digest_header;
	}
	
	private function _getSignAlgorithmOPENSSL()
	{
		$sign_algoritm;
	
		switch ($this->_sign_algorithm)
		{
			case $this::$SIGN_ALGORITHM_RSA_SHA256:
				$sign_algoritm = 'sha256WithRSAEncryption';
				break;
			default:
				$sign_algoritm = 'sha256WithRSAEncryption';
		}
	
		return $sign_algoritm;
	}
	
	private function _validateResponseRequestId($headers)
	{
		$request_id = null;
		
		foreach ($headers as $header)
		{
			if (preg_match('/X-Request-Id:(.*?)\n/', $header, $matches))
			{
				$request_id = trim(array_pop($matches));
				break;
			}
		}
		
		if ($request_id != null)
		{
			if ($request_id != $this->_request_id)
			{
				throw new SimpleSAML_Error_Exception('Response X-Request-Id not matches with the requested one');	
			}
		}
		else 
		{
			throw new SimpleSAML_Error_Exception('X-Request-Id not found in the response');
		}
	}
    
    
    
	// Validate sig alg. rsa-sha256 support is mandatory (and the only one by now)
	private function _validateSignatureAlgorithm($params)
	{
		if (preg_match('/algorithm="(.*?)"/', $params, $matches))
		{
			$algorithm = trim(array_pop($matches));
			if ($algorithm != $this::$SIGN_ALGORITHM_RSA_SHA256)
			{
				throw new SimpleSAML_Error_Exception('Signature algorithm not matches with the expected one');
			}
			
			$this->_server_sign_algo = $algorithm;
		}
		else
		{
			throw new SimpleSAML_Error_Exception('Signature algorithm not found in the response');
		}
	}
    
    
    
	// Validate the headers that have to be  mandatorily signed
	private function _validateSignatureHeaders($params)
	{
		if (preg_match('/headers="(.*?)"/', $params, $matches))
		{
			$headers = ' '.trim(array_pop($matches)).' ';
			
			if (!$preg_match('/\s+original-date\s+/', $headers) and !$preg_match('/\s+date\s+/', $headers))
			{
				throw new SimpleSAML_Error_Exception('Date not found in the response signature header');
			}
			elseif (!$preg_match('/\s+digest\s+/', $headers))
			{
				throw new SimpleSAML_Error_Exception('Digest not found in the response signature header');
			}
			elseif (!$preg_match('/\s+x-request-id\s+/', $headers))
			{
				throw new SimpleSAML_Error_Exception('Request id not found in the response signature header');
			}
			elseif (!$preg_match('/\s+x-request-signature\s+/', $headers))
			{
				throw new SimpleSAML_Error_Exception('Request signature not found in the response signature header');
			}
		}
		else 
		{		
			throw new SimpleSAML_Error_Exception('Headers not found in the response signature header');
		}
	}
    
    
    
	private function _validateKeyId($params)
	{
		if (preg_match('/keyId="(.*?)"/', $params, $matches))
		{
			$keyId = trim(array_pop($matches));
            
			foreach ($this->_trusted_certs as $key)
			{
				if ($keyId == self::get_sha256_fingerprint($key))
                {
                    return $this->_server_key = $key;
                }
			}
			
			throw new SimpleSAML_Error_Exception('Server key mismatch');
		}
		else 
		{
			throw new SimpleSAML_Error_Exception('KeyId not found in the signature parameters');
		}
	}
	
		private function _composeServerSigningString($params, $headers, $body)
	{
		if (!preg_match('/headers="(.*?)"/', $params, $matches))
		{
			throw new SimpleSAML_Error_Exception('Headers not found in the signature parameters');
		}
		
		$serverHeaders = str_split(trim(array_pop($matches)));
		
		$signatureHeaders = '';
		
		//TODO: Allow more headers?
		foreach ($serverHeaders as $header)
		{
			switch ($header)
			{
				case 'date':
					$signatureHeaders.= "date: ".$this->_getDateFormat($this->_server_timestamp)."\n";
					break;
				case 'original-date':
					$signatureHeaders.= "original-date: ".$this->_getDateFormat($this->_server_orig_timestamp)."\n";
					break;
				case 'digest':
					$signatureHeaders.= "digest: $this->_digest\n";
					break;
				case 'x-request-id':
					$signatureHeaders.= "x-request-id: $this->_request_id"."\n";
					break;
				case 'x-request-signature':
					$signatureHeaders.= "x-request-signature: $this->_signature"."\n";
					break;
			}
		}
		
		$signatureString = $signatureHeaders."\n".$body;
		
		return $signatureString;		
	}
	
	private function _validateSignature($params, $headers, $body)
	{
		if (!preg_match('/signature="(.*?)"/', $params, $matches))
		{
			throw new SimpleSAML_Error_Exception('Signature not found in the signature parameters');
		}
		
		$signature = trim(array_pop($matches));
		
		$signatureString = _composeServerSigningString($params, $headers, $body);
		
		if (!openssl_verify($signatureString, $signature, openssl_pkey_get_public($this->_server_key), $this->_server_sign_algo))
		{
			throw new SimpleSAML_Error_Exception('Response signature is not valid');
		}
	}
	
	private function _validateResponseSignature($headers, $body)
	{
		foreach ($headers as $header)
		{
			if (preg_match('/Authorization: Signature:(.*?)/', $header, $matches))
			{
				$signature = trim(array_pop($matches));
				break;
			}
		}

		if ($signature != null)
		{
			$this->_validateSignatureAlgorithm($signature);
			$this->_validateSignatureHeaders($signature);
			$this->_validateKeyId($signature);
			$this->_validateSignature($signature, $headers, $body);
		}
		else
		{
			throw new SimpleSAML_Error_Exception('Signature header not found in the response');
		}
	}
	
	private function _validateResponseDates($headers)
	{
		$date = null;
		$original_date = null;
	
		foreach ($headers as $header)
		{
			if (preg_match('/Original-Date:.+,(.*?)GMT/', $header, $matches))
			{
				$original_date = trim(array_pop($matches));
			}
			elseif (preg_match('/Date:.+,(.*?)GMT/', $header, $matches))
			{
				$date = trim(array_pop($matches));
			}
				
			if ($date != null && $original_date != null)
			{
				break;
			}
		}
	
		//If there is original_date header, validate it, as the proxies won't have tampered it
		if ($original_date != null)
		{
			$time = strtotime($original_date);
			$this->_server_orig_timestamp = $time;
		}
		elseif ($date != null)
		{
			$time = strtotime($date);
			$this->_server_timestamp = $time;
		}
		else
		{
			throw new SimpleSAML_Error_Exception('Dates header not found in the response');
		}
	
		if ($time === false)
		{
			throw new SimpleSAML_Error_Exception('Dates cannot be parsed');
		}
		elseif (abs($time - time()) > $this::$DATE_THRESHOLD)
		{
			throw new SimpleSAML_Error_Exception('Date not matches the clock');
		}
	}
	
		private function _validateResponseDigest($headers, $body)
	{
		$digest = null;
		
		foreach ($headers as $header)
		{
			if (preg_match('/Digest: (.*?)/', $header, $matches))
			{
				$digest = trim(array_pop($matches));
				break;
			}
		}

		if ($digest == null)
		{
			throw new SimpleSAML_Error_Exception('Digest not found in the response');
		}
		
		$digestDataArray = explode(',', $digestData);
		$digestAlgo = null;
		$digestCalc = null;
		
		foreach ($digestDataArray as $digestData)
		{			
			try {
				$digestData = explode('=', $digestData);
				$digestAlgo = _getDigestAlgorithm(trim($digestData[0]));
				$digestCalc = base64_encode(hash($digestAlgo, $body, true));
			} catch (Exception $e) {
			}
			
		}
		
		if ($digestAlgo == null)
		{
			throw new SimpleSAML_Error_Exception('Digest algorithms not supported or expected');
		}
		
		$this->_server_digest = trim($digest[1]);
		
		if ($this->_server_digest != $digestCalc)
		{
			throw new SimpleSAML_Error_Exception('The response digest does not match with the calculate one');
		}
	}
	
	private function _validateServerResponse($headers, $body)
	{	
		$this->_validateResponseRequestId($headers);
		$this->_validateResponseDates($headers);
		$this->_validateResponseDigest($headers, $body);
		$this->_validateResponseSignature($headers, $body);
	}
	
	private function _getDigestAlgorithm($header_algo, $expected_algo=null)
	{
		$digest_algo = null;
	
		switch ($header_algo)
		{
			case 'SHA-256':
				$digest_algo = $this::$DIGEST_ALGO_SHA256;
				break;
		}
		
		if ($digest_algo == null)
		{
			throw new SimpleSAML_Error_Exception('Digest algorithm '.$header_algo.' not supported');
		}
		else if ($expected_algo != null and $header_algo!=$expected_algo)
		{
			throw new SimpleSAML_Error_Exception('Digest algorithm '.$header_algo.' not expected');
		}
	
		return $digest_header;
	}
    
    




    public static function generateUUIDv4()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	
				// 32 bits for "time_low"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff),
	
				// 16 bits for "time_mid"
				mt_rand(0, 0xffff),
	
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand(0, 0x0fff) | 0x4000,
	
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand(0, 0x3fff) | 0x8000,
	
				// 48 bits for "node"
				mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

    
    
    public static function get_sha256_fingerprint($pubKeyPem) 
    {
        $pubKeyPem = preg_replace('/\-+BEGIN PUBLIC KEY\-+/', '', $pubKeyPem);
        $pubKeyPem = preg_replace('/\-+END PUBLIC KEY\-+/', '', $pubKeyPem);
        $pubKeyPem = trim ($pubKeyPem);
        $pubKeyPem = str_replace (array (
			"\n\r",
			"\n",
			"\r" 
        ), '', $pubKeyPem);
        
        $bin = base64_decode($pubKeyPem);  //TODO: old version. The new one does not b64 decode, so the finerprint is different. are we sure it works like the new one?
        return bin2hex(hash("sha256", $bin, true));
        //return bin2hex(hash("sha256", $pubKeyPem, true));
    }
    
    
}

// sspmod_esmo_HttpSig_Error_