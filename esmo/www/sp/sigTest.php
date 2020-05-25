<?php



$LOOPS = 0;

if($LOOPS <= 0)
    die("Please, set a number of loops to test on the hardcoded variable inside this script");


$origMetadata = SimpleSAML_Configuration::loadFromArray(array(
        'esmo:SP',

        'msId' => 'SAMLms_0001',
        
        'rsaPublicKeyBinary' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCi7jZVwQFxQ2SY4lxjr05IexolQJJobwYzrvE5pk7AcQpG46kuJBzD8ziiqFFCGSNZ07cLWys+b5JmJ6kU44lKLVeGbEpgaO0OTBDLMk2fi5U83T8dezgWgaPFiy/N3sHPpcW2Y3ZePo0UbM7MLzv14TR+jxTOyrmwWwGwDJsz+wIDAQAB',


        'rsaPrivateKeyBinary' => 'MIICXQIBAAKBgQCi7jZVwQFxQ2SY4lxjr05IexolQJJobwYzrvE5pk7AcQpG46kuJBzD8ziiqFFCGSNZ07cLWys+b5JmJ6kU44lKLVeGbEpgaO0OTBDLMk2fi5U83T8dezgWgaPFiy/N3sHPpcW2Y3ZePo0UbM7MLzv14TR+jxTOyrmwWwGwDJsz+wIDAQABAoGAFVDQ7vsnMyg7+vxyVeBTf4wLaaA/B0avKwfSK1akquyfCZMzSQQUd1ZUrIMUzm73fwMByYyN5cc3AgJiTodOKJ68Gbz4OnxJ1OdpOZ2TpHGw4eZssPYRSnEblX6XlYKTATl4d/XfSs9814OPla3JIvmo7B1+3qvqy4h9/5NLmQECQQDSKZ4jPFl9mycZrU6vp9xx7kFWDQF8RDQaTxsUUVinj45nTxUCVdOZf3y1WqS4zStRveWDWsAlE3BrNw0tQku7AkEAxndnJhADj0n8LfdAWlycL4c55EZAdZmFDnkxmFZNvS6tjbcFRetQk/wgNss97zZ09uS4Q7XfRO0l+1Si9AiUwQJAepNreIGqcGgd1gwO6MSu/oRH9zh+tUvSV8XrtV38pz5DgF3Pkx0b3VtOEThc+qwvp+1p/8LebsF3wBDLzqnsIQJBAJLy4/gF0WzuHf+22/pMKgTy/kVsUtwAQMm3qKYf+M2D21Nb2Vas5mu8Oen4ULJnQvFv5pOT/W3encnbIBDKrcECQQCRPiaLykX4UPFd+CMLrXB6eo+0ikR3hhTcLWTNWjlTLKfeK9uyWVt56eO3N+dkh96sHbStvZ7CrbvQSS5+uIdy',
        
        
        //[Mandatory] Metadata set name where the msRegistry can be found
        'msRegistry' =>  'esmo-microservices',
        
        //[Mandatory] Config Manager URL where the msRegistry can be fetched
        'msRegistryUrl' =>  'https://XXXXXXX', 
));





//Get the microservice metadata of the SessionMgr
try{
    $smMetadata = sspmod_esmo_Tools::getMsMetadataByClass("SM",$origMetadata->getString("msRegistry"));
    SimpleSAML_Logger::debug('ESMO randomly chosen SessionMgr metadata: '.print_r($smMetadata,true));
} catch (Exception $e) {
    throw new SimpleSAML_Error_Exception($e->getMessage());
}
        
//Instantiate the SM handler object
$smHandler = new sspmod_esmo_SMHandler($origMetadata,$smMetadata);
        
        

echo "<html><body><pre>";

echo "Starting session...";
$smHandler->startSession();
                    

//Test all calls N times

for($i=0; $i<$LOOPS;$i++){
    echo "Iteration: $i\n";

    SimpleSAML_Logger::debug('Iteration: '.$i);

    
    echo "writing variable -test-\n";
    try{
        $smHandler->writeSessionVar('{"id":"_69a510337f266e8d0fbcbd15ab197634114f2d8108"}',"test");
    } catch (Exception $e) {
        echo "FAILED: ".$e->getMessage()."\n";
    }

    echo "requesting token\n";
    try{
        $token = $smHandler->generateToken(
            $origMetadata->getString('msId'),
            $origMetadata->getString('msId')
        );
    } catch (Exception $e) {
        echo "FAILED: ".$e->getMessage()."\n";
    }
    
    echo "validating token\n";
    try{
        $extraData = $smHandler->validateToken($token);
        $sessionID = $smHandler->getSessID();
        $smHandler->setSessID($sessionID);
    } catch (Exception $e) {
        echo "FAILED: ".$e->getMessage()."\n";
    }
    
    echo "fetching variable -test-\n";
    try{
        $test = json_decode($smHandler->getSessionVar("test"),true);
    } catch (Exception $e) {
        echo "FAILED: ".$e->getMessage()."\n";
    }
    
    //ob_flush();
    sleep(1);
}


echo "</pre></body></html>";


        